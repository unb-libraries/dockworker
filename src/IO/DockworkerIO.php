<?php

namespace Dockworker\IO;

use Robo\Symfony\ConsoleIO;

class DockworkerIO extends ConsoleIO
{
    protected function warnConfirmExitDestructiveAction(string $prompt): void
    {
        if (
            $this->warnConfirmDestructiveAction(
                $prompt
            ) !== true
        ) {
            exit(0);
        }
    }

    /**
     * Determines if the user wishes to proceed with a destructive action.
     *
     * @param string $prompt
     *   The prompt to display to the user.
     *
     * @return bool
     *   TRUE if the user wishes to continue. False otherwise.
     */
    protected function warnConfirmDestructiveAction(string $prompt): bool
    {
        $this->warnDestructiveAction();
        return ($this->confirm($prompt));
    }

    /**
     * Warns the user that a destructive action is about to be performed.
     */
    protected function warnDestructiveAction(): void
    {
        $this->warning('Destructive, Irreversible Actions Ahead!');
    }
}
