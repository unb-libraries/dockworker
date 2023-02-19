<?php

namespace Dockworker\Cli;

use Dockworker\IO\DockworkerIOTrait;

/**
 * Provides methods to check CLI tools for existence and functionality.
 */
trait CliToolCheckerTrait
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
     * Checks all registered CLI tools.
     *
     * @hook validate
     *
     * @throws \Dockworker\DockworkerException
     */
    public function checkRegisteredCliTools(): void
    {
        $this->checkRegisteredCliToolCommands();
    }

    /**
     * Executes the registered CLI tool commands and checks their output.
     *
     * @param bool $quiet
     *   True to suppress any output, including errors.
     *
     * @throws \Dockworker\DockworkerException
     */
    protected function checkRegisteredCliToolCommands(
        bool $quiet = false
    ): void {
        $this->checkIsSilent = $quiet;
        foreach ($this->registeredCliCheckCommands as $command) {
            $this->checkRegisteredCliTool(
                $command['command'],
                $command['expect_output'],
                $command['label'],
                $command['quiet']
            );
        }
    }

    /**
     * Executes a CLI tool command and checks its output.
     *
     * @param CliCommand $command
     *   The command to execute.
     * @param string $expected_output
     *   A string that is expected to appear within the command's output.
     * @param bool $quiet
     *   True to suppress any output, including errors.
     *
     * @throws \Dockworker\DockworkerException
     */
    protected function checkRegisteredCliTool(
        CliCommand $command,
        string $expected_output,
        string $testing_label,
        bool $quiet = false
    ): void {
        if (!$this->checkIsSilent) {
            $this->dockworkerIO->say("$testing_label...");
        }
        $command->execTest($expected_output, $this->dockworkerIO, $quiet);
    }

    /**
     * Adds a CLI tool command to the list of commands to check.
     *
     * @param CliCommand $command
     *   The command to execute.
     * @param string $expected_output
     *   A string that is expected to appear within the command's output.
     * @param bool $quiet
     *   True to suppress any output from this command, including errors.
     */
    protected function registerCliToolCheck(
        CliCommand $command,
        string $expected_output,
        string $label,
        bool $quiet = false
    ): void {
        $this->registeredCliCheckCommands[] = [
            'command' => $command,
            'label' => $label,
            'expect_output' => $expected_output,
            'quiet' => $quiet,
        ];
    }
}
