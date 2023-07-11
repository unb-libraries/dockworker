<?php

namespace Dockworker\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Dockworker\DockworkerCommands;
use Dockworker\IO\DockworkerIOTrait;
use Dockworker\Logs\LogCheckerTrait;

/**
 * Provides commands to check a log file for errors.
 */
class LogCheckCommands extends DockworkerCommands implements CustomEventAwareInterface
{
    use CustomEventAwareTrait;
    use DockworkerIOTrait;
    use LogCheckerTrait;

    /**
     * Checks a log file for errors.
     *
     * @param string $file_path
     *   The path to the log file.
     *
     * @command logs:check-file
     * @hidden
     */
    public function checkLogFileForErrors(string $file_path): void
    {
        if (!is_readable($file_path)) {
            $this->dockworkerIO->error(
                sprintf(
                    'Log file [%s] is not readable.',
                    $file_path
                )
            );
            exit(1);
        }
        [$errors_pattern, $exceptions_pattern] = $this->getAllLogErrorStrings();
        $logs = file_get_contents($file_path);
        $matched_errors = [];
        if (
                $this->logsHaveErrors(
                    $logs,
                    $errors_pattern,
                    $exceptions_pattern,
                    $matched_errors
                )
        ) {
                $this->reportErrorsInLogs($this->dockworkerIO, $matched_errors);
                exit(1);
        } else {
            $this->dockworkerIO->writeln('No errors detected in logs.');
        }
    }
}
