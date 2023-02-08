<?php

namespace Dockworker;

use Robo\Symfony\ConsoleIO;

/**
 * Provides methods to execute and manage CLI commands within Dockworker.
 */
class CliCommand
{
    use DockworkerIOTrait;

    /**
     * The full CLI command to execute.
     *
     * @var string
     */
    protected string $command;

    /**
     * The output of the command.
     *
     * @var string[]
     */
    protected array $commandOutput;

    /**
     * The return code of the command.
     *
     * @var int
     */
    protected int $commandReturnCode;

    /**
     * A description of the command.
     *
     * @var string
     */
    public string $description;

    /**
     * True to suppress any output from this command, including errors.
     *
     * @var bool
     */
    protected bool $quiet;

    /**
     * The timeout for the command.
     *
     * @var int
     */
    protected int $timeout;

    /**
     * Constructor.
     *
     * @param string $command
     *   The full CLI command to execute.
     * @param string $description
     *   A description of the command.
     * @param int $timeout
     *   The timeout for the command.
     * @param bool $quiet
     *   True to suppress any output from this command, including errors.
     */
    public function __construct(
        string $command,
        string $description,
        int $timeout = 10,
        bool $quiet = false
    ) {
        $this->command = $command;
        $this->description = $description;
        $this->timeout = $timeout;
        $this->quiet = $quiet;
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
     * Executes the command and checks for an expected output and return code.
     *
     * @param ConsoleIO $io
     *   The console IO.
     * @param string $output
     *   A string that is expected to appear within the command's output.
     * @param bool $quiet
     *   Optional. True to suppress any output, including errors.
     * @param int $return_code
     *   Optional. The expected return code. Defaults to 0.
     *
     * @return void
     * @throws \Dockworker\DockworkerException
     */
    public function execTest(
        ConsoleIO $io,
        string $output,
        bool $quiet = false,
        int $return_code = 0
    ): void {
        $this->quiet = $quiet;
        $this->exec();
        if ($this->commandReturnCode != $return_code) {
            if (!$this->quiet) {
                $this->dockworkerOutputBlock($io, $this->commandOutput);
            }
            throw new DockworkerException("Command [$this->command] returned error code $this->commandReturnCode.");
        }
        if (!$this->outputContainsExpectedOutput($output)) {
            if (!$this->quiet) {
                $this->dockworkerOutputBlock($io, $this->commandOutput);
            }
            throw new DockworkerException("Command [$this->command] returned unexpected output.");
        }
    }

    /**
     * Checks if the command's output contains an expected string.
     *
     * @param string $output
     *   A string that is expected to appear within the command's output.
     *
     * @return bool
     *   True if the output contains the expected output. False otherwise.
     */
    protected function outputContainsExpectedOutput(string $output): bool
    {
        foreach ($this->commandOutput as $line) {
            if (str_contains($line, $output)) {
                return true;
            }
        }
        return false;
    }
}
