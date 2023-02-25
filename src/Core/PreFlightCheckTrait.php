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
     * @var \Dockworker\Core\PreFlightCheck[]
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
     * @param string $output_method
     *   The method within the command that retrieves the output.
     * @param string $expected_output
     *   A string that is expected to appear within the command's output.
     * @param array|string $fail_message
     *   The message to display if the command fails.
     */
    protected function registerNewPreflightCheck(
        string $label,
        object $command,
        string $test_method,
        string $output_method,
        string $expected_output,
        array|string $fail_message,
    ): void {
        $this->preFlightChecks[] = PreFlightCheck::create(
            $label,
            $command,
            $test_method,
            $output_method,
            $expected_output,
            $fail_message
        );
    }

    /**
     * Checks all registered preflight checks.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     *
     * @return void
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
