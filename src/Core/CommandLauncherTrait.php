<?php

namespace Dockworker\Core;

use Dockworker\DockworkerException;
use Dockworker\IO\DockworkerIOTrait;

/**
 * Provides methods to launch another dockworker command from the application.
 */
trait CommandLauncherTrait
{
    use DockworkerIOTrait;

    /**
     * Runs another Dockworker command.
     *
     * This is necessary until the annotated-command feature request:
     * https://github.com/consolidation/annotated-command/issues/64 is merged
     * or solved. Otherwise, hooks do not fire as expected.
     *
     * @param string $command_string
     *   The Dockworker command to run.
     * @param string $exception_message
     *   The message to display if a non-zero code is returned.
     *
     * @throws DockworkerException
     *
     * @return int
     *   The return code of the command.
     */
    public function setRunOtherCommand(
        string $command_string,
        string $exception_message = ''
    ): int {
        $this->dockworkerIO->note(
            ["Spawning new command thread: $command_string"]
        );
        $bin = $_SERVER['argv'][0];
        $command = "$bin --ansi $command_string";
        passthru($command, $return);

        if ($return > 0) {
            throw new DockworkerException($exception_message);
        }
        return $return;
    }
}
