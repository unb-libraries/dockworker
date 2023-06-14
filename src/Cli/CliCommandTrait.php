<?php

namespace Dockworker\Cli;

use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to interact with the local CLI.
 *
 * @INTERNAL This trait is intended only to be used by Dockworker commands. It
 * references user properties which are not in its own scope.
 */
trait CliCommandTrait
{
    /**
     * Executes a set of CLI commands.
     *
     * @param array $commands
     *   The commands to execute.
     * @param \Dockworker\IO\DockworkerIO|null $io
     *   The IO to use for input and output. Null for no io, tty.
     * @param string $title
     *   The title to display before executing the commands.
     */
    protected function executeCliCommandSet(
        array $commands,
        DockworkerIO|null $io,
        string $title = '',
    ): void {
        $first_command = true;
        foreach ($commands as $command) {
            if ($first_command) {
                $title_string = $title;
                $needs_init = true;
                $first_command = false;
            } else {
                $title_string = '';
                $needs_init = false;
            }
            $this->executeCliCommand(
                $command['command'],
                $io,
                $command['cwd'] ?? null,
                $title_string,
                $command['message'] ?? '',
                $command['tty'] ?? true,
            );
        }
    }

    /**
     * Executes a CLI command.
     *
     * @param array $command
     *   The command to execute.
     * @param \Dockworker\IO\DockworkerIO|null $io
     *   The IO to use for input and output. Null for no io.
     * @param string|null $cwd
     *   The working directory to use for the command.
     * @param string $title
     *   The title to display before executing the command.
     * @param string $message
     *   The message to display before executing the command.
     * @param bool $use_tty
     *   Whether to use a TTY for the command. Defaults to TRUE.
     */
    protected function executeCliCommand(
        array $command,
        DockworkerIO|null $io,
        string|null $cwd,
        string $title = '',
        string $message = '',
        bool $use_tty = true
    ): CliCommand|null {
        if ($io !== null) {
            if (!empty($title)) {
                $io->title($title);
            }
            if (!empty($message)) {
                $io->say($message);
            }
        }
        $tty = $use_tty && $this->ttySupported;
        $cmd = new CliCommand(
            $command,
            $message,
            $cwd,
            [],
            null,
            null
        );
        // If null $io is passed, this supercedes the tty setting.
        if ($tty && $io !== null) {
            $cmd->runTty($io);
        } else {
            $cmd->mustRun();
            if ($io !== null) {
                $io->block($cmd->getOutput());
            }
        }
        return $cmd;
    }
}
