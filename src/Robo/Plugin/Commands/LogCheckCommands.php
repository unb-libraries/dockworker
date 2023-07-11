<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerCommands;
use Dockworker\IO\DockworkerIOTrait;
use Dockworker\Logs\LogCheckerTrait;
use Dockworker\Logs\LogErrorStringsTrait;

/**
 * Provides commands to check a log file for errors.
 */
class LogCheckCommands extends DockworkerCommands
{
    use DockworkerIOTrait;
    use LogCheckerTrait;
    use LogErrorStringsTrait;

    /**
     * Checks a log file for errors.
     *
     * @param string $file_path
     *   The path to the log file.
     *
     * @command logs:check-file
     * @hidden
     */
    public function setupGitHooks(string $file_path): void
    {
        [$errors_pattern, $exceptions_pattern] = $this->getAllLogErrorStrings();
        $logs = file_get_contents($file_path);
        $matched_error = '';
        if (
                $this->logsHaveErrors(
                    $logs,
                    $errors_pattern,
                    $exceptions_pattern,
                    $matched_error
                )
            ) {
                $this->dockworkerIO->error(
                    sprintf(
                        'Errors detected in logs. [%s] found in output.',
                        trim($matched_error)
                    )
                );
                exit(1);
            }
            else {
                $this->dockworkerIO->writeln('No errors detected in logs.');
            }
    }
}
