<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Cli\CliCommandTrait;
use Dockworker\DockworkerCommands;
use Dockworker\IO\DockworkerIOTrait;
use Dockworker\System\FileSystemOperationsTrait;

/**
 * Provides commands to copy git hooks into an application repository.
 */
class DockworkerGitHookCommands extends DockworkerCommands
{
    use CliCommandTrait;
    use DockworkerIOTrait;

    /**
     * Sets up the required git hooks for dockworker.
     *
     * @command git:setup-hooks
     * @hidden
     */
    public function setupGitHooks(): void
    {
        $this->copyGitHookFiles('dockworker');
    }

    /**
     * Copies the git hook scripts from a repository into the git hooks path.
     *
     * @param string $source_repo
     *   The repository to copy the git hooks from.
     */
    protected function copyGitHookFiles(string $source_repo): void
    {
        $cmd = [
            'rsync',
            '-a',
            $this->applicationRoot . "/vendor/unb-libraries/$source_repo/data/scripts/git-hooks/",
            $this->applicationRoot . '/.git/hooks',
        ];
        $this->executeCliCommand(
            $cmd,
            $this->dockworkerIO,
            null,
            '',
            '',
            false
        );
    }
}
