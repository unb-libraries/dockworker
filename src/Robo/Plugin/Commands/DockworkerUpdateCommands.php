<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerCommands;
use Dockworker\IO\DockworkerIOTrait;

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
     *
     * @hidden
     */
    public function updateDockworker(): void
    {
        $this->dockworkerIO->title('Updating Dockworker');
        $this->dockworkerIO->say(
            'Checking for any updates to unb-libraries/dockworker...'
        );
        $this->taskExec('composer')
            ->dir($this->applicationRoot)
            ->arg('update')
            ->arg('unb-libraries/dockworker')
            ->silent(true)
            ->run();
    }

}
