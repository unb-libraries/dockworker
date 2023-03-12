<?php

namespace Dockworker\Core;

use Dockworker\IO\DockworkerIO;
use Dockworker\IO\DockworkerIOTrait;
use Grasmash\SymfonyConsoleSpinner\Checklist;

/**
 * Provides methods to check CLI tools for existence and functionality.
 */
trait PreFlightCheckTrait
{
    use DockworkerIOTrait;

    /**
     * The currently registered preflight checks.
     *
     * @var PreFlightCheck[]
     */
    protected array $preFlightChecks = [];

    /**
     * Adds a CLI command to the list of commands to check.
     *
     * @param string $label
     *   A label to use when checking.
     * @param object $command
     *   The command object.
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
     *   A string that is expected to appear within the command's output.
     * @param array|string $fail_message
     *   The message to display if the command fails.
     */
    protected function registerNewPreflightCheck(
        string $label,
        object $command,
        string $test_method,
        array $test_method_args,
        string $output_method,
        array $output_method_args,
        string $expected_output,
        array|string $fail_message,
    ): void {
        $this->preFlightChecks[] = PreFlightCheck::create(
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
     * Checks all registered preflight checks.
     *
     * @param DockworkerIO $io
     *   The IO to use for input and output.
     */
    protected function checkPreflightChecks(DockworkerIO $io): void
    {
        $checklist = new Checklist($io->output());
        if (!empty($this->preFlightChecks)) {
            $io->title('Pre-Flight Checks');
            foreach ($this->preFlightChecks as $check) {
                $checklist->addItem($check->getLabel());
                $check->check($io, true);
                $checklist->completePreviousItem();
            }
            $io->newLine();
        }
    }
}
