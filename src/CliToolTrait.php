<?php

namespace Dockworker;

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

    /**
     * The tools to check.
     *
     * @var array
     */
    protected array $cliTools = [];

    /**
     * Registers a CLI tool from a YAML file source.
     *
     * @param ConsoleIO $io
     *   The console IO.
     * @param string $filepath
     *   The path to the YAML file containing the tool definition.
     *
     * @return void
     */
    protected function registerCliToolFromYaml(
        ConsoleIO $io,
        string $filepath
    ): void {
        $tool = Yaml::parseFile($filepath);
        $this->registerCliTool(
            $io,
            $tool['tool']['name'],
            $tool['tool']['description'],
            $tool['tool']['default_path'],
            $tool['tool']['reference_uri'],
            $tool['tool']['healthcheck']['command'],
            $tool['tool']['healthcheck']['output-contains'],
            $tool['tool']['healthcheck']['label']
        );
    }

    /**
     * Registers a CLI tool.
     *
     * @param ConsoleIO $io
     *   The console IO.
     * @param string $name
     *   The name of the tool.
     * @param string $description
     *   The description of the tool.
     * @param string $default_binpath
     *   The default path to the tool.
     * @param string $install_uri
     *   The URI to the tool's installation instructions.
     * @param string[] $command
     *   The arguments to pass to the tool to test it.
     * @param string $expected_test_output
     *   A string that is expected to appear within the command's output.
     * @param string $testing_label
     *   The label to use when testing the tool.
     */
    protected function registerCliTool(
        ConsoleIO $io,
        string $name,
        string $description,
        string $default_binpath,
        string $install_uri,
        array $command,
        string $expected_test_output,
        string $testing_label
    ): void {
        $found_tool = false;
        while ($found_tool == false) {
            $bin = $this->getSetDockworkerPersistentDataConfigurationItem(
                $io,
                'cli_tools',
                "$name.bin",
                "Enter the full path to your installed $name binary",
                $default_binpath,
                $description,
                $install_uri
            );
            if (!file_exists($bin) || !is_executable($bin)) {
                $this->dockworkerWarn(
                    $io,
                    ["$bin does not exist or is not executable"]
                );
                $this->setDockworkerPersistentDataConfigurationItem(
                    'cli_tools',
                    "$name.bin",
                    null
                );
            } else {
                $found_tool = true;
            }
        }

        $cmd = new CliCommand(
            array_merge(
                [$bin],
                $command
            ),
            $name
        );

        $this->registerCliToolCheck(
            $cmd,
            $expected_test_output,
            $testing_label
        );

        $this->cliTools[$name] = $bin;
    }

}
