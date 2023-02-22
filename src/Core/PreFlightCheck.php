<?php

namespace Dockworker\Core;

use CzProject\GitPhp\Exception;
use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to execute Preflight checks within Dockworker.
 */
class PreFlightCheck
{
    /**
     * The command object to use in the check.
     *
     * @var object
     */
    private object $command;

    /**
     * The string that is expected to appear within the command's output.
     *
     * @var string
     */
    private string $expectedOutput;

    /**
     * A message to display if the command fails.
     *
     * @var array|string
     */
    private array|string $failMessage;

    /**
     * A label to use when checking the command's output.
     *
     * @var string
     */
    private string $label;

    /**
     * The method within the command that retrieves the output.
     *
     * @var string
     */
    private string $outputMethod;

    /**
     * The method within the command to run as a test.
     *
     * @var string
     */
    private string $testMethod;

    /**
     * Constructor
     *
     * @param string $label
     *   A label to use when checking the command's output.
     * @param object $command
     *   The command object to use in the check.
     * @param string $test_method
     *   The method within the command to execute.
     * @param string $output_method
     *   The method within the command that retrieves the output.
     * @param string $expected_output
     *   A string that is expected to appear within the command's output.
     * @param array|string $fail_message
     *   A message to display if the command fails.
     */
    private function __construct(
        string $label,
        object $command,
        string $test_method,
        string $output_method,
        string $expected_output,
        array|string $fail_message
    ) {
        $this->command = $command;
        $this->expectedOutput = $expected_output;
        $this->failMessage = $fail_message;
        $this->label = $label;
        $this->outputMethod = $output_method;
        $this->testMethod = $test_method;
    }

    /**
     * Factory.
     *
     * @param string $label
     *   A label to use when checking the command's output.
     * @param object $command
     *   The command object to use in the check.
     * @param string $test_method
     *   The method within the command to execute.
     * @param string $output_method
     *   The method within the command that retrieves the output.
     * @param string $expected_output
     *   A string that is expected to appear within the command's output.
     * @param array|string $fail_message
     *   A message to display if the command fails.
     */
    public static function create(
        string $label,
        object $command,
        string $test_method,
        string $output_method,
        string $expected_output,
        array|string $fail_message
    ): self {
        return new static(
            $label,
            $command,
            $test_method,
            $output_method,
            $expected_output,
            $fail_message
        );
    }

    /**
     * Checks the command operates and returns as expected.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param bool $quiet
     *   TRUE if the check should avoid non-error output.
     */
    public function check(
        DockworkerIO $io,
        bool $quiet = false
    ): void {
        try {
            if (!$quiet) {
                $io->say("$this->label...");
            }
            $this->command->{$this->testMethod}();
            $this->checkOutput($io);
        } catch (\Exception $e) {
            $io->error($this->failMessage);
            exit(1);
        }
    }

    /**
     * Checks the command's output for the expected output.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     *
     * @throws \Exception
     *   Throws an exception if the expected output is not found.
     */
    private function checkOutput(DockworkerIO $io): void
    {
        $output = $this->command->{$this->outputMethod}();
        if (
            !str_contains(
                $output,
                $this->expectedOutput
            )
        ) {
            $io->block($output);
            throw new Exception();
        }
    }

    /**
     * Gets the check's label.
     *
     * @return string
     *   The check's label.
     */
    public function getLabel(): string
    {
        return $this->label;
    }
}
