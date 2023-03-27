<?php

namespace Dockworker\Cli;

use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to interact with the dart sass CLI application.
 *
 * @INTERNAL This trait is intended only to be used by Dockworker commands. It
 * references user properties which are not in its own scope.
 */
trait SassCliTrait
{
    use CliToolTrait;

    /**
     * Registers dart sass as a required CLI tool.
     */
    protected function registerSassCliTool(DockworkerIO $io): void
    {
        $file_path = "$this->applicationRoot/vendor/unb-libraries/dockworker/data/cli-tools/sass.yml";
        $this->registerCliToolFromYaml($file_path, $io);
    }

    /**
     * Runs a dart sass command.
     *
     * @param array $command
     *   The full CLI command to execute.
     * @param string $description
     *   A description of the command.
     * @param ?float $timeout
     *   The timeout in seconds or null to disable
     * @param bool $use_tty
     *   Whether to use a TTY for the command. Defaults to TRUE.
     *
     * @return \Dockworker\Cli\CliCommand
     */
    protected function sassRun(
        array $command,
        string $description = '',
        ?float $timeout = null,
        bool $use_tty = true
    ): CliCommand {
        $cmd = $this->sassCli($command, $description, $timeout)
            ->setWorkingDirectory($this->applicationRoot);
        if ($use_tty) {
            $cmd->runTty(
                $this->dockworkerIO,
                false
            );
        } else {
            $cmd->mustRun();
        }
        return $cmd;
    }

    /**
     * Constructs a dart sass command object.
     *
     * @param array $command
     *   The full CLI command to execute.
     * @param string $description
     *   A description of the command.
     * @param ?float $timeout
     *   The timeout in seconds or null to disable
     *
     * @return \Dockworker\Cli\CliCommand
     */
    protected function sassCli(
        array $command,
        string $description = '',
        ?float $timeout = null
    ): CliCommand {
        array_unshift(
            $command,
            $this->cliTools['sass']
        );
        return new CliCommand(
            $command,
            $description,
            null,
            [],
            null,
            $timeout
        );
    }
}
