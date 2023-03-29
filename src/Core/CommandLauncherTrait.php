<?php

namespace Dockworker\Core;

use Dockworker\Cli\CliCommandTrait;
use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to launch another dockworker command from another.
 *
 * @INTERNAL This trait is intended only to be used by Dockworker commands. It
 * references user properties which are not in its own scope.
 */
trait CommandLauncherTrait
{
    use CliCommandTrait;

    /**
     * Runs another Dockworker command.
     *
     * This is necessary until the annotated-command feature request:
     * https://github.com/consolidation/annotated-command/issues/64 is merged
     * or solved. Otherwise, hooks do not fire as expected.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param array $command
     */
    public function setRunOtherCommand(
        DockworkerIO $io,
        array $command,
    ): void {
        $cmd_launch = [
           $this->applicationRoot . '/vendor/bin/dockworker',
            '--ansi',
        ];
        $cmd = array_merge($cmd_launch, $command);
        $this->executeCliCommand(
            $cmd,
            $io,
            $this->applicationRoot,
            '',
            ''
        );
    }
}
