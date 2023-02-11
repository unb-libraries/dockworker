<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerIOTrait;

/**
 * Provides commands for interacting with Docker images.
 */
class DockworkerUpdateCommands extends DockworkerCommands
{
    use DockworkerIOTrait;

    /**
     * Updates the dockworker package to the latest release.
     *
     * @command dockworker:update
     * @aliases update
     * @hidden
     */
    public function updateDockworker(ConsoleIO $io): void
    {
        $this->dockworkerTitle(
            $io,
            'Updating Dockworker'
        );
        $this->dockworkerSay(
            $io,
            ['Checking for any updates to unb-libraries/dockworker...']
        );
        $this->taskExec('composer')
            ->dir($this->applicationRoot)
            ->arg('update')
            ->arg('unb-libraries/dockworker')
            ->silent(true)
            ->run();
    }

}
