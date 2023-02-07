<?php

namespace Dockworker;

use Dockworker\DockworkerIOTrait;
use Robo\Symfony\ConsoleIO;

class CliToolCommand
{
    use DockworkerIOTrait;

    protected array $cmdOutput;
    protected bool $quiet;
    protected int $cmdReturnCode;
    protected int $timeout;
    protected string $command;
    public string $description;

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
        $this->cmdOutput = $cmd_output;
        $this->cmdReturnCode = $cmd_return;
    }

    /**
     * Execute the command and check for expected output and return code.
     *
     * @param string $expected_output
     *   The output expected from the command.
     * @param bool $quiet
     *   True to suppress any output, including errors.
     *
     * @return void
     * @throws \Dockworker\DockworkerException
     */
    public function execTest(
        string $expected_output,
        ConsoleIO $io,
        $quiet = false
    ): void {
        $this->quiet = $quiet;
        $this->exec();
        if ($this->cmdReturnCode != 0) {
            if (!$this->quiet) {
                $this->dockworkerOutputBlock($io, $this->cmdOutput);
            }
            throw new DockworkerException("Command [$this->command] returned error code $this->cmdReturnCode.");
        }
        if (!$this->outputContainsExpectedOutput($expected_output)) {
            if (!$this->quiet) {
                $this->dockworkerOutputBlock($io, $this->cmdOutput);
            }
            throw new DockworkerException("Command [$this->command] returned unexpected output.");
        }
    }

    /**
     * Checks if the command output contains the expected output.
     *
     * @param string $expected_output
     *   The output expected from the command.
     *
     * @return bool
     *   True if the output contains the expected output. False otherwise.
     */
    protected function outputContainsExpectedOutput(string $expected_output): bool
    {
        foreach ($this->cmdOutput as $line) {
            if (str_contains($line, $expected_output)) {
                return true;
            }
        }
        return false;
    }
}
