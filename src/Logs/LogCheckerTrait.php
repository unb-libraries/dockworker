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
     * @param array $matched_errors
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
}
