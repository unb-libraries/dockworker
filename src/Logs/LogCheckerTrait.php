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
     * @param array $matches
     *   An optional empty array that will contain the line of output that
     *   matched as an error if one is found.
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
        array &$matches = [],
        string $preg_operators = 'i'
    ): bool {
        if (
            preg_match(
                "/(.*($error_strings).*)/$preg_operators",
                $output,
                $matches
            )
        ) {
            if (!empty($exception_strings)) {
                return !preg_match(
                    "/(.*($exception_strings).*)/$preg_operators",
                    $output
                );
            }
            return true;
        }
        return false;
    }
}
