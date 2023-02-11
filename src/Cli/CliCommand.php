<?php

namespace Dockworker\Cli;

use Dockworker\IO\DockworkerIOTrait;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Provides methods to execute and manage CLI commands within Dockworker.
 */
class CliCommand extends Process
{
    use DockworkerIOTrait;

    /**
     * A description of the command.
     *
     * @var string
     */
    protected string $description;

    /**
     * Constructor.
     *
     * @param array $command
     *   The full CLI command to execute.
     * @param string $description
     *   A description of the command.
     */
    public function __construct(
        array $command,
        string $description,
    ) {
        parent::__construct($command);
        $this->description = $description;
    }

    /**
     * Executes the command and checks for an expected output and return code.
     *
     * @param string $expected_output
     *   A string that is expected to appear within the command's output.
     * @param bool $quiet
     *   Optional. True to suppress any output, including errors.
     *
     * @return void
     * @throws \Dockworker\DockworkerException
     */
    public function execTest(
        string $expected_output,
        bool $quiet = false
    ): void {
        try {
            $this->mustRun();
        } catch (ProcessFailedException $exception) {
            if (!$quiet) {
                $this->dockworkerIO->block([$this->getOutput()]);
            }
            throw new DockworkerException("Command [$this->description] returned error code {$this->getExitCode()}.");
        }
        if (!$this->outputContainsExpectedOutput($expected_output)) {
            if (!$quiet) {
                $this->dockworkerIO->block([$this->getOutput()]);
            }
            throw new DockworkerException("Command [$this->description] returned unexpected output.");
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
        return str_contains($this->getOutput(), $output);
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Runs the command and attaches the command TTY to the current TTY.
     */
    public function runTty()
    {
        $this->setTty(true);
        $this->run(function ($type, $buffer) {
            if (self::ERR === $type) {
                echo 'ERR > ' . $buffer;
            } else {
                echo 'OUT > ' . $buffer;
            }
        });
    }
}
