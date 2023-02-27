<?php

namespace Dockworker\Cli;

use Dockworker\Core\PreFlightCheckTrait;
use Dockworker\IO\DockworkerIO;
use Dockworker\Storage\DockworkerPersistentDataStorageTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides methods to check CLI tools for existence and functionality.
 */
trait CliToolTrait
{
    use DockworkerPersistentDataStorageTrait;
    use PreFlightCheckTrait;

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
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     *
     * @return void
     */
    protected function registerCliToolFromYaml(
        string $filepath,
        DockworkerIO $io
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
            $io
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
     * @param float|null $timeout
     *   The timeout in seconds or null to disable
     * @param array|string $fail_message
     *   The message to display if the command fails.
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
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
        DockworkerIO $io
    ): void {
        $tool_bin_path = $this->getCliToolBinaryPath(
            $name,
            $description,
            $default_binpath,
            $reference_uris,
            $io
        );

        $this->registerNewPreflightCheck(
            $testing_label,
            $this->getCliToolPreflightCheckCommand(
                $tool_bin_path,
                $command,
                $name,
                $timeout
            ),
            'mustRun',
            [],
            'getOutput',
            [],
            $expected_test_output,
            $fail_message
        );

        $this->cliTools[$name] = $tool_bin_path;
    }

    /**
     * Creates the CLI tool's command object to be used in the preflight check.
     *
     * @param string $tool_bin_path
     *   The full path to the
     * @param string[] $command
     *   The arguments to pass to the tool to test it.
     * @param string $name
     *   The name of the tool.
     * @param float|null $timeout
     *   The timeout in seconds or null to disable
     *
     * @return \Dockworker\Cli\CliCommand
     */
    private function getCliToolPreflightCheckCommand(
        string $tool_bin_path,
        array $command,
        string $name,
        ?float $timeout
    ): CliCommand {
        return new CliCommand(
            array_merge(
                [$tool_bin_path],
                $command
            ),
            $name,
            null,
            null,
            null,
            $timeout
        );
    }

    /**
     * Determines the binary path to the CLI tool on this PC.
     *
     * @param string $name
     *   The name of the tool.
     * @param string $description
     *   The description of the tool.
     * @param string $default_binpath
     *   The default path to the tool.
     * @param array $reference_uris
     *   The URI to the tool's installation instructions.
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     *
     * @return string
     *   The binary path to the CLI tool on this PC.
     */
    private function getCliToolBinaryPath(
        string $name,
        string $description,
        string $default_binpath,
        array $reference_uris,
        DockworkerIO $io
    ): string {
        $bin = '';
        $tool_bin_exists = false;
        while ($tool_bin_exists == false) {
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
                $tool_bin_exists = true;
            }
        }
        return $bin;
    }

    /**
     * Generates a warning message for an unconfigured CLI tool.
     *
     * @param string $name
     *   The name of the tool.
     * @param string $description
     *   The description of the tool.
     *
     * @return string
     *   The complete warning message.
     */
    private function getWarningMessageFromTool(
        string $name,
        string $description
    ): string {
        return "The cli application '$name' has not been configured for use in Dockworker. $description";
    }
}
