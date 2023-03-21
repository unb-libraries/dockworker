<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerCommands;
use Dockworker\IO\DockworkerIOTrait;

/**
 * Provides commands to test bootstrapping in CI.
 */
class DockworkerBootstrapCommands extends DockworkerCommands
{
    use DockworkerIOTrait;
    /**
     * A simple command to test framework bootstrapping.
     *
     * @command dockworker:bootstrap
     * @aliases bootstrap
     * @hidden
     */
    public function bootstrapDockworker(): void
    {
        $this->dockworkerIO->say('Dockworker bootstrapped successfully.');
    }
}
