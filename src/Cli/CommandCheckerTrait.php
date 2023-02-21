<?php

namespace Dockworker\Cli;

use Dockworker\IO\DockworkerIOTrait;

/**
 * Provides methods to check CLI tools for existence and functionality.
 */
trait CommandCheckerTrait
{
    use DockworkerIOTrait;

    /**
     * The currently registered CLI tool commands to check.
     *
     * @var array
     */
    protected array $registeredCliCheckCommands = [];

    /**
     * Whether to suppress output in the check.
     *
     * @var bool
     */
    protected bool $checkIsSilent = false;

    /**
     * Executes the registered CLI commands and checks their output.
     *
     * @param bool $quiet
     *   True to suppress any output, including errors.
     *
     * @throws \Dockworker\DockworkerException
     */
    protected function checkRegisteredCommands(
        bool $quiet = false
    ): void {
        $this->checkIsSilent = $quiet;
        foreach ($this->registeredCliCheckCommands as $command) {
            $this->checkRegisteredCommand(
                $command['command'],
                $command['expect_output'],
                $command['label'],
                $command['fail_message'],
            );
        }
    }

    /**
     * Executes a CLI command and checks its output.
     *
     * @param CliCommand $command
     *   The command to execute.
     * @param string $expected_output
     *   A string that is expected to appear within the command's output.
     * @param string $testing_label
     *   A label to use when checking the command's output.
     * @param array|string $fail_message
     *   A message to display if the command fails.
     *
     * @throws \Dockworker\DockworkerException
     */
    protected function checkRegisteredCommand(
        CliCommand $command,
        string $expected_output,
        string $testing_label,
        array|string $fail_message,
    ): void {
        if (!$this->checkIsSilent) {
            $this->dockworkerIO->say("$testing_label...");
        }
        $command->execTest(
            $expected_output,
            $this->dockworkerIO,
            $fail_message,
        );
    }

    /**
     * Adds a CLI command to the list of commands to check.
     *
     * @param CliCommand $command
     *   The command to execute.
     * @param string $expected_output
     *   A string that is expected to appear within the command's output.
     * @param string $label
     *   A label to use when checking the command's output.
     * @param array|string $fail_message
     *   A message to display if the command fails.
     */
    protected function registerNewCommandCheck(
        CliCommand $command,
        string $expected_output,
        string $label,
        array|string $fail_message,
    ): void {
        $this->registeredCliCheckCommands[] = [
            'command' => $command,
            'label' => $label,
            'expect_output' => $expected_output,
            'fail_message' => $fail_message,
        ];
    }
}
