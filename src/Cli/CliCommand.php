<?php

namespace Dockworker\Cli;

use Dockworker\DockworkerException;
use Dockworker\IO\DockworkerIO;
use Symfony\Component\Process\Process;

/**
 * Provides methods to execute and manage CLI commands within Dockworker.
 *
 * @phpstan-consistent-constructor
 */
class CliCommand extends Process
{
    /**
     * A description of the command.
     *
     * @var string
     */
    protected string $description;

    /**
     * Constructor.
     *
     * @param string[] $command
     *   The full CLI command to execute.
     * @param string $description
     *   A description of the command.
     * @param string|null $cwd
     *   The working directory or null to use the working dir of the current PHP process
     * @param string[]|null $env
     *   The environment variables or null to use the same environment as the current PHP process
     * @param mixed $input
     *   The input as stream resource, scalar or \Traversable, or null for no input
     * @param ?float $timeout
     *   The timeout in seconds or null to disable
     */
    public function __construct(
        array $command,
        string $description = '',
        string $cwd = null,
        array $env = null,
        mixed $input = null,
        ?float $timeout = null
    ) {
        parent::__construct($command, $cwd, $env, $input, $timeout);
        $this->description = $description;
    }

    /**
     * Announces and runs a command, throwing an exception if the command fails.
     *
     * @param string[] $command
     *   The full CLI command to execute.
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param string $description
     *   A description of the command.
     * @param string|null $cwd
     *   The working directory or null to use the working dir of the current PHP process
     * @param string[]|null $env
     *   The environment variables or null to use the same environment as the current PHP process
     * @param mixed $input
     *   The input as stream resource, scalar or \Traversable, or null for no input
     * @param ?float $timeout
     *   The timeout in seconds or null to disable
     *
     * @throws \Dockworker\DockworkerException
     */
    public static function sayRunTestExcept(
        array $command,
        DockworkerIO $io,
        string $description = '',
        string $cwd = null,
        array $env = null,
        mixed $input = null,
        ?float $timeout = null
    ): void {
        $obj = new static(
            $command,
            $description,
            $cwd,
            $env,
            $input,
            $timeout
        );
        if (!empty($description)) {
            $io->say($description);
        }
        $obj->run();
        if (!$obj->isSuccessful()) {
            throw new DockworkerException("Error {$obj->getErrorOutput()}");
        }
    }

    /**
     * Gets the description of this command.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Sets the description of this command.
     *
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Runs the command and attaches to the current TTY.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param bool $new_line
     *   True to output a newline after the output.
     */
    public function runTty(
        DockworkerIO $io,
        bool $new_line = true,
    ): void {
        $this->setTty(true);
        $this->run(function ($type, $buffer) {
            if (self::ERR === $type) {
                echo 'ERR > ' . $buffer;
            } else {
                echo 'OUT > ' . $buffer;
            }
        });
        if ($new_line) {
            $io->newLine();
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
}
