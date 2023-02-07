<?php

namespace Dockworker;

use Dockworker\DockworkerIOTrait;
use Dockworker\CliToolCommand;
use Robo\Symfony\ConsoleIO;

/**
 * Provides methods to check CLI tools for existence and functionality.
 */
trait CliToolCheckerTrait
{
    use DockworkerIOTrait;

    /**
     * @var array
     */
    protected array $registeredCliCheckCommands = [];

    protected bool $checkIsSilent = false;

    /**
     * Executes a series of CLI tool commands and checks their output.
     *
     * @param bool $quiet
     *   True to suppress any output, including errors.
     *
     * @throws \Dockworker\DockworkerException
     */
    protected function checkRegisteredCliToolCommands(
        ConsoleIO $io,
        bool $quiet = false
    ): void {
        $this->checkIsSilent = $quiet;
        foreach ($this->registeredCliCheckCommands as $command) {
            $this->checkRegisteredCliTool(
                $command['command'],
                $command['expect_output'],
                $command['label'],
                $io,
                $command['quiet']
            );
        }
    }

    /**
     * Adds a CLI tool command to the list of commands to check.
     *
     * @param CliToolCommand $command
     *   The command to execute.
     * @param string $expected_output
     *   The output expected from the command.
     * @param bool $quiet
     *   True to suppress any output from this command, including errors.
     */
    protected function registerCliToolCheck(
        CliToolCommand $command,
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

    /**
     * Executes a CLI tool command and checks its output.
     *
     * @param CliToolCommand $command
     *   The command to execute.
     * @param string $expected_output
     *   The output expected from the command.
     * @param bool $quiet
     *   True to suppress any output, including errors.
     *
     * @throws \Dockworker\DockworkerException
     */
    protected function checkRegisteredCliTool(
        CliToolCommand $command,
        string $expected_output,
        string $testing_label,
        ConsoleIO $io,
        bool $quiet = false
    ): void {
        if (!$this->checkIsSilent) {
            $this->dockworkerNotice(
                $io,
                "$testing_label..."
            );
        }
        $command->execTest($expected_output, $io, $quiet);
    }
}
