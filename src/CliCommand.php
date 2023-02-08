<?php

namespace Dockworker;

use Dockworker\DockworkerIOTrait;
use Robo\Symfony\ConsoleIO;

class CliCommand
{
    use DockworkerIOTrait;

    protected string $command;
    protected array $commandOutput;
    protected int $commandReturnCode;
    public string $description;
    protected bool $quiet;
    protected bool $silent;
    protected int $timeout;

    /**
     * CliCommand constructor.
     *
     * @param string $command
     *   The full command to execute.
     * @param string $description
     *   A description of the command.
     * @param int $timeout
     *   The timeout for the command.
     * @param bool $silent
     *   True to suppress any output from this command, including errors.
     */
    public function __construct(
        string $command,
        string $description,
        int $timeout = 10,
        bool $silent = false
    ) {
        $this->command = $command;
        $this->description = $description;
        $this->timeout = $timeout;
        $this->silent = $silent;
    }

    /**
     * Executes the command.
     */
    public function exec(): void
    {
        exec(
            $this->command,
            $cmd_output,
            $cmd_return
        );
        $this->commandOutput = $cmd_output;
        $this->commandReturnCode = $cmd_return;
    }

    /**
     * Execute the command and check for expected output and return code.
     *
     * @param string $expected_output
     *   The output expected from the command.
     * @param ConsoleIO $io
     *   The IO object to use.
     * @param bool $quiet
     *   True to suppress any output, including errors.
     *
     * @return void
     * @throws \Dockworker\DockworkerException
     */
    public function execTest(
        string $expected_output,
        ConsoleIO $io,
        bool $quiet = false
    ): void {
        $this->quiet = $quiet;
        $this->exec();
        if ($this->commandReturnCode != 0) {
            if (!$this->quiet) {
                $this->dockworkerOutputBlock($io, $this->commandOutput);
            }
            throw new DockworkerException("Command [$this->command] returned error code $this->commandReturnCode.");
        }
        if (!$this->outputContainsExpectedOutput($expected_output)) {
            if (!$this->quiet) {
                $this->dockworkerOutputBlock($io, $this->commandOutput);
            }
            throw new DockworkerException("Command [$this->command] returned unexpected output.");
        }
    }

    /**
     * Checks if the command's output contains the expected string.
     *
     * @param string $expected_output
     *   The output expected from the command.
     *
     * @return bool
     *   True if the output contains the expected output. False otherwise.
     */
    protected function outputContainsExpectedOutput(string $expected_output): bool
    {
        foreach ($this->commandOutput as $line) {
            if (str_contains($line, $expected_output)) {
                return true;
            }
        }
        return false;
    }
}
