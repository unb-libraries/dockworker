<?php

namespace Dockworker\Cli;

use Dockworker\IO\DockworkerIOTrait;
use Dockworker\Storage\DockworkerPersistentDataStorageTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides methods to check CLI tools for existence and functionality.
 */
trait CliToolTrait
{
    use CliCommandCheckerTrait;
    use DockworkerIOTrait;
    use DockworkerPersistentDataStorageTrait;

    /**
     * The tools to check.
     *
     * @var array
     */
    protected array $cliTools = [];

    /**
     * Registers a CLI tool from a YAML file source.
     *
     * @param string $filepath
     *   The path to the YAML file containing the tool definition.
     *
     * @return void
     */
    protected function registerCliToolFromYaml(
        string $filepath
    ): void {
        $tool = Yaml::parseFile($filepath);
        $this->registerCliTool(
            $tool['tool']['name'],
            $tool['tool']['description'],
            $tool['tool']['default_path'],
            $tool['tool']['reference_uris'],
            $tool['tool']['healthcheck']['command'],
            $tool['tool']['healthcheck']['output-contains'],
            $tool['tool']['healthcheck']['label'],
            $tool['tool']['healthcheck']['timeout'],
            $tool['tool']['healthcheck']['fail-message'],
        );
    }

    /**
     * Registers a CLI tool.
     *
     * @param string $name
     *   The name of the tool.
     * @param string $description
     *   The description of the tool.
     * @param string $default_binpath
     *   The default path to the tool.
     * @param array $reference_uris
     *   The URI to the tool's installation instructions.
     * @param string[] $command
     *   The arguments to pass to the tool to test it.
     * @param string $expected_test_output
     *   A string that is expected to appear within the command's output.
     * @param string $testing_label
     *   The label to use when testing the tool.
     * @param ?float $timeout
     *   The timeout in seconds or null to disable
     * @param array|string $fail_message
     *   The message to display if the command fails.
     */
    protected function registerCliTool(
        string $name,
        string $description,
        string $default_binpath,
        array $reference_uris,
        array $command,
        string $expected_test_output,
        string $testing_label,
        ?float $timeout,
        array|string $fail_message,
    ): void {
        $found_tool = false;
        while ($found_tool == false) {
            $bin = $this->getSetDockworkerPersistentDataConfigurationItem(
                'cli_tools',
                "$name.bin",
                "Enter the full path to the installed $name binary on this PC",
                $default_binpath,
                $this->getWarningMessageFromTool($name, $description),
                $reference_uris
            );
            if (!file_exists($bin) || !is_executable($bin)) {
                $this->dockworkerIO->warning(
                    "$bin does not exist or is not executable"
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
            $name,
            null,
            null,
            null,
            $timeout
        );

        $this->registerCliCommandCheck(
            $cmd,
            $expected_test_output,
            $testing_label,
            $fail_message
        );

        $this->cliTools[$name] = $bin;
    }

    private function getWarningMessageFromTool(
        string $name,
        string $description
    ): string {
        return "The cli application '$name' has not been configured for use in Dockworker. $description";
    }

}
