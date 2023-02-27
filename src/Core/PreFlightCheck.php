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
     * The arguments to pass to the output retrieval method.
     *
     * @var string[]
     */
    private array $outputMethodArgs;

    /**
     * The method within the command to run as a test.
     *
     * @var string
     */
    private string $testMethod;

    /**
     * The arguments to pass to the test method.
     *
     * @var string[]
     */
    private array $testMethodArgs;

    /**
     * Constructor
     *
     * @param string $label
     *   A label to use when checking the command's output.
     * @param object $command
     *   The command object to use in the check.
     * @param string $test_method
     *   The method within the command to execute.
     * @param array $test_method_args
     *   The arguments to pass to the test method.
     * @param string $output_method
     *   The method within the command that retrieves $test_method's output.
     *   If empty, the output from $test_method is ignored and not tested.
     * @param array $output_method_args
     *   The arguments to pass to the output retrieval method.
     * @param string $expected_output
     *   A string expected to appear within the $output_method's return.
     * @param array|string $fail_message
     *   A message to display if the command fails.
     */
    private function __construct(
        string $label,
        object $command,
        string $test_method,
        array $test_method_args,
        string $output_method,
        array $output_method_args,
        string $expected_output,
        array|string $fail_message
    ) {
        $this->command = $command;
        $this->expectedOutput = $expected_output;
        $this->failMessage = $fail_message;
        $this->label = $label;
        $this->outputMethod = $output_method;
        $this->outputMethodArgs = $output_method_args;
        $this->testMethod = $test_method;
        $this->testMethodArgs = $test_method_args;
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
     * @param array $test_method_args
     *   The arguments to pass to the test method.
     * @param string $output_method
     *   The method within the command that retrieves $test_method's output.
     *   If empty, the output from $test_method is ignored and not tested.
     * @param array $output_method_args
     *   The arguments to pass to the output retrieval method.
     * @param string $expected_output
     *   A string expected to appear within the $output_method's return.
     * @param array|string $fail_message
     *   A message to display if the command fails.
     */
    public static function create(
        string $label,
        object $command,
        string $test_method,
        array $test_method_args,
        string $output_method,
        array $output_method_args,
        string $expected_output,
        array|string $fail_message
    ): self {
        return new static(
            $label,
            $command,
            $test_method,
            $test_method_args,
            $output_method,
            $output_method_args,
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
            call_user_func_array(
              [$this->command, $this->testMethod],
              $this->testMethodArgs
            );
            if (!empty($this->outputMethod)) {
                $this->checkOutput($io);
            }
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
        $output = call_user_func_array(
          [$this->command, $this->outputMethod],
          $this->outputMethodArgs
        );
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
