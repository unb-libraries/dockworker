<?php

namespace Dockworker\Cli;

use Dockworker\Cli\CliCommandTrait;
use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to interact with the GitHub CLI (gh) application.
 *
 * @INTERNAL This trait is intended only to be used by Dockworker commands. It
 * references user properties which are not in its own scope.
 */
trait GhCliTrait
{
    use CliCommandTrait;
    use CliToolTrait;

    /**
     * Registers the GitHub CLI (gh) as a required CLI tool.
     */
    protected function registerGhCliTool(DockworkerIO $io): void
    {
        $file_path = "$this->applicationRoot/vendor/unb-libraries/dockworker/data/cli-tools/gh.yml";
        $this->registerCliToolFromYaml($file_path, $io);
    }

    /**
     * Registers a preflight check verifying the user is authenticated to gh.
     *
     * Relies on the exit code of 'gh auth status', which is non-zero when the
     * user is not authenticated (via 'gh auth login' or the GH_TOKEN env var).
     * Must be called after registerGhCliTool(), which resolves the binary path.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     */
    protected function registerGhAuthPreflightCheck(DockworkerIO $io): void
    {
        $this->registerNewPreflightCheck(
            'Checking GitHub CLI (gh) authentication',
            new CliCommand(
                [$this->cliTools['gh'], 'auth', 'status'],
                'gh auth status',
                null,
                null,
                null,
                10
            ),
            'mustRun',
            [],
            '',
            [],
            '',
            'You are not authenticated to the GitHub CLI. Please run: gh auth login'
        );
    }

    /**
     * Executes a GitHub CLI (gh) command.
     *
     * @param array $command
     *   The full CLI command to execute.
     * @param string $description
     *   A description of the command.
     * @param \Dockworker\IO\DockworkerIO|null $io
     *   The IO object to use for the command.
     * @param ?float $timeout
     *   The timeout in seconds or null to disable
     * @param bool $use_tty
     *   Whether to use a TTY for the command. Defaults to TRUE.
     *
     * @return \Dockworker\Cli\CliCommand
     *   The executed command object.
     */
    protected function ghRun(
        array $command,
        string $description,
        ?DockworkerIO $io,
        ?float $timeout = null,
        bool $use_tty = true
    ): CliCommand {
        array_unshift(
            $command,
            $this->cliTools['gh']
        );
        return $this->executeCliCommand(
            $command,
            $io,
            $this->applicationRoot,
            '',
            $description,
            $use_tty,
            $timeout
        );
    }
}
