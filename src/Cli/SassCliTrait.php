<?php

namespace Dockworker\Cli;

use Dockworker\Cli\CliCommandTrait;
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
    use CliCommandTrait;

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
     *
     * @return \Dockworker\Cli\CliCommand
     */
    protected function sassRun(
        array $command,
        string $description,
        DockworkerIO $io
    ): CliCommand {
        array_unshift(
            $command,
            $this->cliTools['sass']
        );
        return $this->executeCliCommand(
            $command,
            $io,
            null,
            '',
            $description,
            false
        );
    }
}
