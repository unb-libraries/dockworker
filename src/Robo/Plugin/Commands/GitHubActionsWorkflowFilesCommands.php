<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerCommands;

/**
 * Provides commands for building the application's theme assets.
 */
class GitHubActionsWorkflowFilesCommands extends DockworkerCommands
{
    /**
     * Write the default GitHub Actions workflow files for this repository.
     *
     * @command github:workflows:write-default
     * @hidden
     */
    public function writeDefaultWorkflowFiles(): void
    {
        // Pass
    }
}
