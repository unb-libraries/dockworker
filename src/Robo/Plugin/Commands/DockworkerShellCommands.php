<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Cli\DockerCliTrait;
use Dockworker\Cli\KubectlCliTrait;
use Dockworker\DockworkerCommands;
use Dockworker\GitHub\GitHubClientTrait;

/**
 * Provides commands for building and deploying the application locally.
 */
class DockworkerShellCommands extends DockworkerCommands
{
    use DockerCliTrait;
    use KubectlCliTrait;
    use GitHubClientTrait;

    /**
     * Opens a shell into the application.
     *
     * @command shell
     *
     * @throws \Dockworker\DockworkerException
     */
    public function buildComposeApplication($env = 'local'): void
    {
        $this->initShellCommands($env);
    }

    /**
     * Initializes the command and executes all preflight checks.
     *
     * @param string $env
     *   The environment to initialize the command for.
     */
    protected function initShellCommands(string $env)
    {
        if ($env === 'local') {
            $this->registerDockerCliTool($this->dockworkerIO);
            $this->initGitHubClientApplicationRepo();
        } else {
            $this->registerKubectlCliTool($this->dockworkerIO);
        }
        $this->checkPreflightChecks($this->dockworkerIO);
    }
}
