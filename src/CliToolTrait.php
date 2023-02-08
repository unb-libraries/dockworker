<?php

namespace Dockworker;

use Dockworker\CliToolCheckerTrait;
use Dockworker\CliCommand;
use Dockworker\DockworkerIOTrait;
use Dockworker\PersistentConfigurationTrait;
use Robo\Symfony\ConsoleIO;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides methods to check CLI tools for existence and functionality.
 */
trait CliToolTrait
{
    use CliToolCheckerTrait;
    use PersistentConfigurationTrait;
    use DockworkerIOTrait;

    protected array $cliTools = [];

    /**
     * Registers a CLI tool from a YAML file source.
     *
     * @param string $filepath
     *   The path to the YAML file containing the tool definition.
     * @param $io
     *   The IO object to use.
     *
     * @return void
     */
    protected function registerCliToolFromYaml(
        string $filepath,
        ConsoleIO $io
    ): void {
        $tool = Yaml::parseFile($filepath);
        $this->registerCliTool(
            $tool['tool']['name'],
            $tool['tool']['description'],
            $tool['tool']['default_path'],
            $tool['tool']['reference_uri'],
            $tool['tool']['healthcheck']['command'],
            $tool['tool']['healthcheck']['output-contains'],
            $tool['tool']['healthcheck']['label'],
            $io
        );
    }

    /**
     * @param string $basename
     * @param string $description
     * @param string $default_bin_path
     * @param string $install_uri
     * @param string $test_args
     * @param string $expected_test_output
     * @param string $testing_label
     * @param \Robo\Symfony\ConsoleIO $io
     *
     * @return void
     */
    protected function registerCliTool(
        string $basename,
        string $description,
        string $default_bin_path,
        string $install_uri,
        string $test_args,
        string $expected_test_output,
        string $testing_label,
        ConsoleIO $io
    ): void {
        $found_tool = false;
        while ($found_tool == false) {
            $bin_path = $this->getSetDockworkerPersistentDataConfigurationItem(
                'cli_tools',
                "$basename.bin",
                "Enter the full path to your installed $basename binary",
                $io,
                $default_bin_path,
                $description,
                $install_uri
            );
            if (!file_exists($bin_path) || !is_executable($bin_path)) {
                $this->dockworkerWarn(
                    $io,
                    ["$bin_path does not exist or is not executable"]
                );
                $this->setDockworkerPersistentDataConfigurationItem(
                    'cli_tools',
                    "$basename.bin",
                    null
                );
            }
            else {
                $found_tool = true;
            }
        }

        $command = new CliCommand(
            "$bin_path $test_args",
            $basename
        );

        $this->registerCliToolCheck(
            $command,
            $expected_test_output,
            $testing_label
        );

        $this->cliTools[$basename] = $bin_path;
    }

}
