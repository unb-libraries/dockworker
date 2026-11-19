<?php

namespace Dockworker\Cli;

use Dockworker\Cli\CliCommandTrait;
use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to interact with the docker CLI application.
 *
 * @INTERNAL This trait is intended only to be used by Dockworker commands. It
 * references user properties which are not in its own scope.
 */
trait DockerCliTrait
{
    use CliCommandTrait;
    use CliToolTrait;

    /**
     * Registers docker as a required CLI tool.
     */
    protected function registerDockerCliTool(DockworkerIO $io): void
    {
        $file_path = "$this->applicationRoot/vendor/unb-libraries/dockworker/data/cli-tools/docker.yml";
        $this->registerCliToolFromYaml($file_path, $io);
    }

    /**
     * Executes a docker command.
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
     * @param array<string, string> $env
     *   Environment variables to set for the docker client, in addition to
     *   those inherited from the current process. Pair with a name-only
     *   '-e NAME' argument to pass a value into the container without it
     *   appearing in the process list.
     *
     * @return \Dockworker\Cli\CliCommand
     *   The executed command object.
     */
    protected function dockerRun(
        array $command,
        string $description,
        ?DockworkerIO $io,
        ?float $timeout = null,
        bool $use_tty = true,
        array $env = []
    ): CliCommand {
        array_unshift(
            $command,
            $this->cliTools['docker']
        );
        return $this->executeCliCommand(
            $command,
            $io,
            $this->applicationRoot,
            '',
            $description,
            $use_tty,
            $timeout,
            null,
            $env
        );
    }

    /**
     * Runs a 'docker compose' command.
     *
     * @param array $command
     *   The full CLI command to execute.
     * @param string $description
     *   A description of the command.
     * @param \Dockworker\IO\DockworkerIO|null $io
     *   The IO object to use for the command.
     * @param ?float $timeout
     *   The timeout in seconds or null to disable
     * @param string[] $profiles
     *   The docker compose profiles to target with this command.
     * @param bool $use_tty
     *   Whether to use a TTY for the command. Defaults to TRUE.
     *
     * @return \Dockworker\Cli\CliCommand
     */
    protected function dockerComposeRun(
        array $command,
        string $description,
        ?DockworkerIO $io,
        ?float $timeout = null,
        array $profiles = [],
        bool $use_tty = true
    ): CliCommand {
        array_unshift(
            $command,
            $this->cliTools['docker'],
            'compose'
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
