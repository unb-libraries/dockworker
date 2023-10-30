<?php

namespace Dockworker\Cli;

use Dockworker\Cli\CliCommandTrait;
use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to interact with the ngrok CLI application.
 *
 * @INTERNAL This trait is intended only to be used by Dockworker commands. It
 * references user properties which are not in its own scope.
 */
trait NgrokCliTrait
{
    use CliToolTrait;
    use CliCommandTrait;

    /**
     * Registers ngrok as a required CLI tool.
     */
    protected function registerNgrokCliTool(DockworkerIO $io): void
    {
        $file_path = "$this->applicationRoot/vendor/unb-libraries/dockworker/data/cli-tools/ngrok.yml";
        $this->registerCliToolFromYaml($file_path, $io);
    }

    /**
     * Runs a ngrok command.
     *
     * @param array $command
     *   The arguments to the ngrok command.
     * @param string $description
     *   A description of the command.
     *
     * @return \Dockworker\Cli\CliCommand
     */
    protected function ngrokRun(
        array $command,
        string $description,
        DockworkerIO $io
    ): CliCommand {
        array_unshift(
            $command,
            $this->cliTools['ngrok']
        );
        return $this->executeCliCommand(
            $command,
            $io,
            null,
            '',
            $description,
            true
        );
    }
}
