<?php

namespace Dockworker\Logs;

use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to work with generic logfiles.
 */
trait LogCheckerTrait
{
    /**
     * Checks if some output contains error-indicating strings.
     *
     * @param string $output
     *   The output to check.
     * @param string $error_strings
     *   A pipe-delimited list of strings to check for.
     * @param string $exception_strings
     *   An optional pipe-delimited list of strings that, if they exist in the
     *   line of the output that matched as an error indicates it is not
     *   actually an error.
     * @param string[] $matched_errors
     *   An optional empty array variable that will contain be filled with the
     *   line of output that matched as an error if one is found.
     * @param string $preg_operators
     *   The operators to use when matching. Defaults to 'i'.
     *
     * @return bool
     *   TRUE if the output has errors, FALSE otherwise.
     */
    private function logsHaveErrors(
        string $output,
        string $error_strings,
        string $exception_strings = '',
        array &$matched_errors = [],
        string $preg_operators = 'i'
    ): bool {
        $error_matches = [];
        if (
            preg_match_all(
                "/.*($error_strings).*/$preg_operators",
                $output,
                $error_matches
            )
        ) {
            if (!empty($exception_strings)) {
                // The 0 keyed value in preg_match_all returns an array of values that matched in the logs.
                foreach ($error_matches[0] as $key => $match) {
                    if (
                        preg_match(
                            "/(.*($exception_strings).*)/$preg_operators",
                            $match
                        )
                    ) {
                        unset($error_matches[0][$key]);
                    }
                }
                if (empty($error_matches[0])) {
                    return false;
                }
            }
            $matched_errors = $error_matches[0];
            return true;
        }
        return false;
    }

    /**
     * Gets the error strings to check for in logs.
     *
     * Calls the custom event handler dockworker-logs-errors-exceptions.
     * Implementing functions should return an array of two arrays, the first
     * containing error strings, and the second containing exception strings.
     *
     * Implementations wishing to describe the error strings in code should
     * define an associative array with the key being the description and the
     * value being the error string. Then, the array can be cast to a
     * non-associative array using array_values().
     *
     * @return string[]
     *   An array of error strings and exception strings.
     */
    public function getAllLogErrorStrings(): array
    {
        $errors = [
                'error',
                'fail',
                'fatal',
                'unable',
                'unavailable',
                'unrecognized',
                'unresolved',
                'unsuccessful',
                'unsupported',
        ];
        $exceptions = [];

        $handlers = $this->getCustomEventHandlers('dockworker-logs-errors-exceptions');
        foreach ($handlers as $handler) {
            [$new_errors, $new_exceptions] = $handler();
            $errors = array_merge(
                $errors,
                $new_errors
            );
            $exceptions = array_merge(
                $exceptions,
                $new_exceptions
            );
        }
        return [
            implode('|', $errors),
            implode('|', $exceptions),
        ];
    }

    /**
     * Reports errors found in logs.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param string[] $matches
     *   An array of strings that matched as errors.
     */
    protected function reportErrorsInLogs(
        DockworkerIO $io,
        array $matches
    ): void {
        $io->error('Errors Found in Logs!');
        $io->listing($matches);
    }

    /**
     * Maximum lines the Drush update-preview suppressor will consume before
     * giving up and resuming scanning, in case the closing confirm line never
     * arrives (e.g. a Drush format change). The preview scales with the number
     * of pending updates, so this is generous; it is only a safety backstop.
     */
    private int $updbPreviewLineCap = 2000;

    /**
     * Partitions log lines by Drupal/Drush severity for error scanning.
     *
     * Lines carrying a non-error severity marker are kept out of the fatal
     * scan: '[warning]' lines are collected for reporting, and
     * '[notice]/[success]/[ok]/[info]/[debug]/[cancelled]' lines are dropped
     * (non-fatal by Drupal severity, e.g. a '[notice] ... 0 errors' line).
     * Everything else — markerless lines and '[error]'/'[ERROR]' — is returned
     * in the 'scan' partition for logsHaveErrors() to judge, so genuine errors
     * are never hidden.
     *
     * The 'drush updatedb' pending-updates PREVIEW table is also suppressed: its
     * rows print each update hook's docblock description, which routinely
     * contains "error"/"fail"/etc. The preview is bounded by Drush's own stable
     * strings (the "Update ID ... Description" column header through the
     * "Do you wish to run the specified pending updates?" confirm), and is safe
     * to suppress because the ACTUAL update execution — where real errors would
     * appear — happens after that confirm and is scanned normally.
     *
     * @param string[] $lines
     *   The log lines to partition.
     * @param array<string, mixed> $state
     *   Carried state for streaming callers (block flags persist across
     *   incremental chunks). Pass the same variable by reference each call; omit
     *   for a one-shot scan of a complete log.
     *
     * @return array{scan: string, warnings: string[]}
     *   'scan' is the newline-joined lines to run error detection against;
     *   'warnings' is the collected '[warning]' lines.
     */
    protected function partitionLogLines(array $lines, array &$state = []): array
    {
        $scan = [];
        $warnings = [];
        $in_updb_preview = $state['in_updb_preview'] ?? false;
        $updb_preview_lines = $state['updb_preview_lines'] ?? 0;
        foreach ($lines as $line) {
            $bare = $this->stripAnsiCodes($line);

            if ($in_updb_preview) {
                $updb_preview_lines++;
                if (str_contains($bare, 'Do you wish to run the specified pending updates')) {
                    // Drush auto-confirms and the scannable execution begins.
                    $in_updb_preview = false;
                    $updb_preview_lines = 0;
                } elseif ($updb_preview_lines > $this->updbPreviewLineCap) {
                    // Safety net: never suppress unbounded.
                    $in_updb_preview = false;
                    $updb_preview_lines = 0;
                    $scan[] = $line;
                }
                // Preview rows/descriptions are informational; drop them.
                continue;
            }

            if ($this->isUpdbPreviewHeader($bare)) {
                $in_updb_preview = true;
                $updb_preview_lines = 0;
                continue;
            }

            $severity = $this->detectLogLineSeverity($bare);
            if ($severity === 'warning') {
                $warnings[] = $line;
                continue;
            }
            if ($severity !== null) {
                // Non-error, non-warning severity: informational, never fatal.
                continue;
            }
            // Markerless line or an [error] line: let the error scanner judge.
            $scan[] = $line;
        }
        $state['in_updb_preview'] = $in_updb_preview;
        $state['updb_preview_lines'] = $updb_preview_lines;
        return [
            'scan' => implode("\n", $scan),
            'warnings' => $warnings,
        ];
    }

    /**
     * Detects the Drush 'updatedb' pending-updates table column header.
     *
     * @param string $line
     *   The (ANSI-stripped) line to inspect.
     *
     * @return bool
     *   TRUE if the line is the update-preview table header.
     */
    protected function isUpdbPreviewHeader(string $line): bool
    {
        return str_contains($line, 'Update ID') && str_contains($line, 'Description');
    }

    /**
     * Detects a non-fatal Drupal/Drush severity marker in a log line.
     *
     * Matches the bracketed level emitted by Drush anywhere in the line (the
     * docker-compose '<slug>  | ' prefix is ignored). '[error]' is deliberately
     * NOT matched: error lines must fall through to the error scanner.
     *
     * @param string $line
     *   The (ANSI-stripped) line to inspect.
     *
     * @return string|null
     *   The lowercased severity (e.g. 'warning', 'notice'), or null if the line
     *   carries no recognized non-fatal severity marker.
     */
    protected function detectLogLineSeverity(string $line): ?string
    {
        if (
            preg_match(
                '/\[(warning|notice|success|ok|info|debug|cancelled)\]/i',
                $line,
                $matches
            ) === 1
        ) {
            return strtolower($matches[1]);
        }
        return null;
    }

    /**
     * Strips ANSI SGR escape codes from a single line.
     *
     * @param string $line
     *   The line to clean.
     *
     * @return string
     *   The line without ANSI colour codes.
     */
    protected function stripAnsiCodes(string $line): string
    {
        return preg_replace('/\x1b\[[\d;]*m/', '', $line) ?? $line;
    }

    /**
     * Reports non-fatal warnings collected from logs.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param string[] $warnings
     *   The collected warning lines.
     */
    protected function reportWarningsInLogs(
        DockworkerIO $io,
        array $warnings
    ): void {
        $unique = [];
        foreach ($warnings as $warning) {
            $trimmed = trim($warning);
            if ($trimmed !== '' && !in_array($trimmed, $unique, true)) {
                $unique[] = $trimmed;
            }
        }
        if (empty($unique)) {
            return;
        }
        $io->warning(
            sprintf(
                '%d warning(s) were emitted during startup (non-fatal):',
                count($unique)
            )
        );
        $io->listing($unique);
    }
}
