<?php

namespace Dockworker;

use Consolidation\AnnotatedCommand\CommandData;
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
     * Check all registered CLI tools.
     *
     * @hook validate
     *
     * @throws \Dockworker\DockworkerException
     */
    public function checkRegisteredCliTools(CommandData $commandData): void
    {
        $io = new ConsoleIO($commandData->input(), $commandData->output());
        $this->checkRegisteredCliToolCommands($io);
    }

    /**
     * Executes the registered CLI tool commands and checks their output.
     *
     * @param ConsoleIO $io
     *   The console IO.
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
                $io,
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
     * @param ConsoleIO $io
     *   The console IO.
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
        ConsoleIO $io,
        CliCommand $command,
        string $expected_output,
        string $testing_label,
        bool $quiet = false
    ): void {
        if (!$this->checkIsSilent) {
            $this->dockworkerNotice(
                $io,
                "$testing_label..."
            );
        }
        $command->execTest($io, $expected_output, $quiet);
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
