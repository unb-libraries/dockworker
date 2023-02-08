<?php

namespace Dockworker;

use Robo\Symfony\ConsoleIO;

/**
 * Provides methods to warn that a destructive action is about to be taken.
 */
trait DestructiveActionTrait
{
    use DockworkerIOTrait;

    /**
     * Warns the user that a destructive action is about to be performed.
     *
     * @param \Robo\Symfony\ConsoleIO $io
     *   The console IO.
     */
    protected function warnDestructiveAction(ConsoleIO $io): void
    {
        $this->dockworkerWarn($io, ['Destructive, Irreversible Actions Ahead!']);
    }

    /**
     * Determines if the user wishes to proceed with a destructive action.
     *
     * @param \Robo\Symfony\ConsoleIO $io
     *   The console IO.
     * @param string $prompt
     *   The prompt to display to the user.
     *
     * @return bool
     *   TRUE if the user wishes to continue. False otherwise.
     */
    protected function warnConfirmDestructiveAction(ConsoleIO $io, string $prompt): bool
    {
        $this->warnDestructiveAction($io);
        return ($this->dockworkerConfirm($io, $prompt));
    }

    /**
     * Warns, prompts the user for and conditionally exits the script.
     *
     * @param \Robo\Symfony\ConsoleIO $io
     *   The console IO.
     * @param string $prompt
     *   The prompt to display to the user.
     */
    protected function warnConfirmExitDestructiveAction(ConsoleIO $io, string $prompt): void
    {
        if (
            $this->warnConfirmDestructiveAction(
                $io,
                $prompt
            ) !== true
        ) {
            exit(0);
        }
    }
}
