<?php

namespace Dockworker\Cli;

use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to interact with the kubectl CLI application.
 *
 * @INTERNAL This trait is intended only to be used by Dockworker commands. It
 * references user properties which are not in its own scope.
 */
trait KubectlCliTrait
{
    use CliToolTrait;

    /**
     * Registers kubectl as a required CLI tool.
     */
    protected function registerKubectlCliTool(DockworkerIO $io): void
    {
        $file_path = "$this->applicationRoot/vendor/unb-libraries/dockworker/data/cli-tools/kubectl.yml";
        $this->registerCliToolFromYaml($file_path, $io);
    }

    /**
    * Runs a kubectl command.
    *
    * @param array $command
    *   The full CLI command to execute.
    * @param string $description
    *   A description of the command.
    * @param ?\Dockworker\IO\DockworkerIO $io
    *   The IO to use for input and output. Null for no IO.
    * @param ?float $timeout
    *   The timeout in seconds or null to disable
    * @param bool $use_tty
    *   Whether to use a TTY for the command. Defaults to TRUE.
    *
    * @return \Dockworker\Cli\CliCommand
    */
    protected function kubeCtlRun(
        array $command,
        string $description,
        ?DockworkerIO $io,
        ?float $timeout = null,
        bool $use_tty = true
    ): CliCommand {
        array_unshift(
            $command,
            $this->cliTools['kubectl']
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
