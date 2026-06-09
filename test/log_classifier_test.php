<?php

/**
 * Standalone regression test for the deploy-log severity classifier.
 *
 * Drives Dockworker\Logs\LogCheckerTrait::partitionLogLines() +
 * logsHaveErrors() against verbatim log fixtures captured from real Drush
 * runs. The classifier:
 *
 *   - collects '[warning]' lines (non-fatal) for an end-of-deploy summary;
 *   - drops other non-error severities ('[notice]/[success]/...');
 *   - suppresses the 'drush updatedb' pending-updates PREVIEW table (its
 *     hook descriptions routinely contain "error"/"fail"), bounded by Drush's
 *     own header -> "Do you wish to run..." confirm;
 *   - leaves markerless lines and '[error]' for the error scanner, so a real
 *     error is NEVER hidden.
 *
 * Run:
 *   php vendor/unb-libraries/dockworker/test/log_classifier_test.php
 *   (or, from this repo: php test/log_classifier_test.php)
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Logs/LogCheckerTrait.php';

/**
 * Minimal harness exposing the trait's classification + scan.
 */
final class LogClassifierTestHarness
{
    use Dockworker\Logs\LogCheckerTrait;

    /**
     * Classifies a complete log and reports whether it is fatal.
     *
     * @param string[] $warnings
     *   Filled with collected '[warning]' lines.
     */
    public function isFatal(string $log, array &$warnings = []): bool
    {
        $state = [];
        ['scan' => $scan, 'warnings' => $warnings] = $this->partitionLogLines(
            explode("\n", $log),
            $state
        );
        $matched = [];
        return $this->logsHaveErrors($scan, ERRORS_PATTERN, EXCEPTIONS_PATTERN, $matched);
    }

    /**
     * Classifies a log delivered in chunks, sharing block state across them
     * (mirrors the streaming local-deploy monitor).
     *
     * @param string[][] $chunks
     *   Each chunk is an array of complete lines.
     */
    public function isFatalStreaming(array $chunks): bool
    {
        $state = [];
        foreach ($chunks as $lines) {
            ['scan' => $scan] = $this->partitionLogLines($lines, $state);
            $matched = [];
            if ($scan !== '' && $this->logsHaveErrors($scan, ERRORS_PATTERN, EXCEPTIONS_PATTERN, $matched)) {
                return true;
            }
        }
        return false;
    }
}

// LogCheckerTrait's base error pattern.
const ERRORS_PATTERN = 'error|fail|fatal|unable|unavailable|unrecognized|unresolved|unsuccessful|unsupported';

// Mirror of DrupalDeployCommands::provideErrorLogConfiguration() exceptions.
// Keep in sync with that hook (dockworker-drupal).
const EXCEPTIONS_PATTERN =
    "Access denied for user 'drupal'" . '|'
    . 'inline_form_errors' . '|'
    . 'Config language.entity.en does not exist' . '|'
    . ' 0 failed' . '|'
    . 'Operation CREATE USER failed' . '|'
    . 'failure: 0' . '|'
    . 'error-handler instead' . '|'
    . '.well-known' . '|'
    . 'HoursCalendarUnavailableTemplate' . '|'
    . 'Do you wish to continue\?: yes' . '|'
    . 'following errors:\s*$' . '|'
    . 'These errors mean there' . '|'
    . 'is configuration that does not comply with its schema' . '|'
    . 'does not comply with its schema' . '|'
    . 'not a fatal error, but it is' . '|'
    . 'recommended to fix these issues' . '|'
    . 'missing schema';

// -----------------------------------------------------------------------
// Fixtures: verbatim docker-compose captures including the "<slug>  | "
// prefix the classifier sees.
// -----------------------------------------------------------------------

$FIXTURE_SCHEMA_SINGLE_LINE = 'loyalist-lib-unb-ca  |  [warning] Schema errors for views.settings with the following errors: views.settings:skip_cache missing schema. These errors mean there is configuration that does not comply with its schema. This is not a fatal error, but it is recommended to fix these issues. For more information on configuration schemas, check out <a href="https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-schemametadata">the documentation</a>.';

$FIXTURE_SCHEMA_WRAPPED = <<<'LOG'
loyalist-lib-unb-ca  | >  [warning] Message: Schema errors for views.settings with the following errors:
loyalist-lib-unb-ca  | > views.settings:skip_cache missing schema. These errors mean there is
loyalist-lib-unb-ca  | > configuration that does not comply with its schema. This is not a fatal
loyalist-lib-unb-ca  | > error, but it is recommended to fix these issues. For more information on
loyalist-lib-unb-ca  | > configuration schemas, check out the documentation [1].
loyalist-lib-unb-ca  | >
loyalist-lib-unb-ca  | > [1] https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-schemametadata
LOG;

$FIXTURE_REQUIREMENTS = 'loyalist-lib-unb-ca  |  // Requirements check reports errors. Do you wish to continue?: yes.';

$FIXTURE_NOTICE_ERRORS = 'loyalist-lib-unb-ca  |  [notice] Module jquery_scrollup has an entry in the system.schema key/value storage, but is missing. <a href="https://www.drupal.org/node/3137656">More information about this error</a>.';

$FIXTURE_UPDB_PREVIEW = <<<'LOG'
loyalist-lib-unb-ca  |  // Requirements check reports errors. Do you wish to continue?: yes.
loyalist-lib-unb-ca  |
loyalist-lib-unb-ca  |  ------------------------ ----------- ------- ----------------
loyalist-lib-unb-ca  |   Module                   Update ID   Type    Description
loyalist-lib-unb-ca  |  ------------------------ ----------- ------- ----------------
loyalist-lib-unb-ca  |   file   add_playsinline   post-update   Adds a value for playsinline. This was rolled back due to a
loyalist-lib-unb-ca  |                                                  bug and is kept only to prevent errors when updating.   @see
loyalist-lib-unb-ca  |   views   table_css_class   post-update   Adds a default table CSS class.
loyalist-lib-unb-ca  |  ------------------------ ----------- ------- ----------------
loyalist-lib-unb-ca  |
loyalist-lib-unb-ca  |  // Do you wish to run the specified pending updates?: yes.
loyalist-lib-unb-ca  | >  [notice] Update started: file_post_update_add_playsinline
loyalist-lib-unb-ca  | >  [notice] Update completed: file_post_update_add_playsinline
LOG;

$FIXTURE_REAL_ERROR = 'loyalist-lib-unb-ca  | >  [error] Drupal\Core\Database\DatabaseExceptionWrapper: boom';
$FIXTURE_MARKERLESS_ERROR = 'loyalist-lib-unb-ca  | ERROR 1064 (42000): You have a syntax error';
$FIXTURE_FAILED_WITH_ERRORS = 'loyalist-lib-unb-ca  | Deployment failed with errors:';
$FIXTURE_PHP_FATAL = 'loyalist-lib-unb-ca  | PHP Fatal error:  Uncaught Error: boom';

// -----------------------------------------------------------------------
// Cases: [name, fixture, expected-fatal].
// -----------------------------------------------------------------------

$harness = new LogClassifierTestHarness();
$pass = 0;
$fail = 0;

$assertFatal = static function (string $name, string $log, bool $expected) use ($harness, &$pass, &$fail): void {
    $got = $harness->isFatal($log);
    $ok = ($got === $expected);
    printf("[%s] %-50s fatal=%s (expected %s)\n", $ok ? 'PASS' : 'FAIL', $name, $got ? 'true' : 'false', $expected ? 'true' : 'false');
    $ok ? $pass++ : $fail++;
};

$assertFatal('schema warning, single line', $FIXTURE_SCHEMA_SINGLE_LINE, false);
$assertFatal('schema warning, wrapped Message block', $FIXTURE_SCHEMA_WRAPPED, false);
$assertFatal('drush requirements auto-continue', $FIXTURE_REQUIREMENTS, false);
$assertFatal('[notice] mentioning "error"', $FIXTURE_NOTICE_ERRORS, false);
$assertFatal('updb preview table (incl scary description)', $FIXTURE_UPDB_PREVIEW, false);
$assertFatal('updb preview + real error after confirm', $FIXTURE_UPDB_PREVIEW . "\nloyalist-lib-unb-ca  | >  [error] Update failed: kaboom", true);
$assertFatal('real [error] line', $FIXTURE_REAL_ERROR, true);
$assertFatal('markerless DB error', $FIXTURE_MARKERLESS_ERROR, true);
$assertFatal('markerless "failed with errors:"', $FIXTURE_FAILED_WITH_ERRORS, true);
$assertFatal('markerless PHP fatal', $FIXTURE_PHP_FATAL, true);

// Streaming: updb preview split across chunks (header / scary description /
// confirm / post-confirm error) must still suppress the preview yet flag the
// real error after the confirm.
$lines = explode("\n", $FIXTURE_UPDB_PREVIEW);
$streamFatal = $harness->isFatalStreaming([
    array_slice($lines, 0, 5),
    array_slice($lines, 5, 3),
    array_slice($lines, 8),
    ['loyalist-lib-unb-ca  | >  [error] boom failed'],
]);
$ok = ($streamFatal === true);
printf("[%s] %-50s fatal=%s (expected true)\n", $ok ? 'PASS' : 'FAIL', 'streaming: preview suppressed, post error fatal', $streamFatal ? 'true' : 'false');
$ok ? $pass++ : $fail++;

// Warning collection: the wrapped schema block contributes exactly one
// collected '[warning]' line (the header).
$warnings = [];
$harness->isFatal($FIXTURE_SCHEMA_WRAPPED, $warnings);
$ok = (count($warnings) === 1);
printf("[%s] %-50s got %d warning(s) (expected 1)\n", $ok ? 'PASS' : 'FAIL', 'warning collection (wrapped block header)', count($warnings));
$ok ? $pass++ : $fail++;

echo "\nRESULT: $pass passed, $fail failed\n";
exit($fail === 0 ? 0 : 1);
