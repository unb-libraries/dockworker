<?php

namespace Dockworker\Logs;

/**
 * Provides methods to assemble error string collections for log parsing.
 *
 * @internal This trait is intended only to be used by Dockworker commands. It
 * references Robo command hooks which are not in its own scope.
 */
trait LogErrorStringsTrait
{
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
}
