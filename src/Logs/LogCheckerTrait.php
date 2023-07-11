<?php

namespace Dockworker\Logs;

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
     * @param string $matched_error
     *   An optional empty scalar variable that will contain be filled with the
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
        string &$matched_error = '',
        string $preg_operators = 'i'
    ): bool {
        $error_matches = [];
        if (
            preg_match(
                "/(.*($error_strings).*)/$preg_operators",
                $output,
                $error_matches
            )
        ) {
            if (!empty($exception_strings)) {
                $unique_error_matches = array_unique($error_matches);
                foreach ($unique_error_matches as $key => $error_match) {
                    if (
                        preg_match(
                            "/(.*($exception_strings).*)/$preg_operators",
                            $error_match
                        )
                    ) {
                        unset($unique_error_matches[$key]);
                    }
                }
                if (empty($unique_error_matches)) {
                    return false;
                }
            }
            $matched_error = implode("\n", $error_matches);
            return true;
        }
        return false;
    }
}
