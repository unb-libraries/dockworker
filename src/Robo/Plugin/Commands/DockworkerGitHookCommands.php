<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerCommands;
use Dockworker\System\FileSystemOperationsTrait;

/**
 * Provides commands for interacting with Docker images.
 */
class DockworkerGitHookCommands extends DockworkerCommands
{
    use FileSystemOperationsTrait;

    /**
     * Sets up the required git hooks for dockworker.
     *
     * @command git:setup-hooks
     * @hidden
     */
    public function setupGitHooks(): void
    {
        $hooks = ['commit-msg'];
        foreach ($hooks as $hook) {
            $source_file = $this->getPathFromPathElements(
                [
                    $this->applicationRoot,
                    'vendor/unb-libraries/dockworker/data/scripts/git-hooks',
                    $hook,
                ]
            );
            $target_file = $this->getPathFromPathElements(
                [
                    $this->applicationRoot,
                    '.git/hooks',
                    $hook,
                ]
            );
            $this->_copy($source_file, $target_file);
        }
    }
}
