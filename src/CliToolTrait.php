<?php

namespace Dockworker;

use Dockworker\CliToolCheckerTrait;
use Dockworker\CliToolCommand;
use Dockworker\DockworkerIOTrait;
use Dockworker\PersistentConfigurationTrait;
use Robo\Symfony\ConsoleIO;

/**
 * Provides methods to check CLI tools for existence and functionality.
 */
trait CliToolTrait
{
    use CliToolCheckerTrait;
    use PersistentConfigurationTrait;
    use DockworkerIOTrait;

    protected array $cliTools = [];

    protected function registerCliTool(
        $binary_file,
        $description,
        $default_binpath,
        $install_uri,
        $test_command,
        $test_command_expected_output,
        ConsoleIO $io
    ): void {

        $found_tool = false;
        while ($found_tool == false) {
            $binpath = $this->getSetDockworkerPersistentDataConfigurationItem(
                'cli_tools',
                "$binary_file.bin",
                "Enter the full path to your installed $binary_file binary",
                $io,
                $default_binpath,
                $description,
                $install_uri
            );
            if (!file_exists($binpath) || !is_executable($binpath)) {
                $this->dockworkerWarn(
                    $io,
                    ["$binpath does not exist or is not executable"]
                );
                $this->setDockworkerPersistentDataConfigurationItem(
                    'cli_tools',
                    "$binary_file.bin",
                    null
                );
            }
            else {
                $found_tool = true;
            }
        }

        $command = new CliToolCommand(
            "$binpath $test_command",
            $binary_file
        );

        $this->registerCliToolCheck(
            $command,
            $test_command_expected_output
        );

    }
}
