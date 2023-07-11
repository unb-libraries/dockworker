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
                $unique_matches = array_unique($error_matches[0]);
                foreach ($unique_matches as $key => $unique_match) {
                    if (
                        preg_match(
                            "/(.*($exception_strings).*)/$preg_operators",
                            $unique_match
                        )
                    ) {
                        unset($unique_matches[$key]);
                    }
                }
                if (empty($unique_matches)) {
                    return false;
                }
            }
            $matched_errors = $error_matches[0];
            return true;
        }
        return false;
    }

    protected function reportErrorsInLogs(DockworkerIO $io, $matches)
    {
        $io->error('Errors Found in Logs!');
        $io->listing($matches);
    }
}
