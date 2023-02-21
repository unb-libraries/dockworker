<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Cli\DockerCliTrait;
use Dockworker\Cli\KubectlCliTrait;
use Dockworker\DockworkerCommands;

/**
 * Provides commands for building and deploying the application locally.
 */
class DockworkerShellCommands extends DockworkerCommands
{
    use DockerCliTrait;
    use KubectlCliTrait;

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
            $this->registerDockerCliTool();
            $this->checkRegisteredCommands();
        } else {
            $this->registerKubectlCliTool();
            $this->checkRegisteredCommands();
        }
    }
}
