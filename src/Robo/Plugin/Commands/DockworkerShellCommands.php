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
        if ($env === 'local') {
            $this->registerDockerCliTool($this->dockworkerIO);
            $this->initGitHubClient();
            $this->checkPreflightChecks($this->dockworkerIO);
        } else {
            $this->registerDockerCliTool($this->dockworkerIO);
            $this->registerKubectlCliTool($this->dockworkerIO);
            $this->checkPreflightChecks($this->dockworkerIO);
        }
    }
}
