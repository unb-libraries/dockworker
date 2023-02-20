<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Cli\DockerCliTrait;
use Dockworker\Cli\KubectlCliTrait;
use Dockworker\DockworkerCommands;
use Dockworker\IO\DockworkerIOTrait;
use Dockworker\System\LocalHostFileOperationsTrait;

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
     */
    public function buildComposeApplication($env = 'local'): void
    {
        if ($env === 'local') {
          $this->registerDockerCliTool();
          $this->checkRegisteredCliCommands();
        } else {
          $this->registerKubectlCliTool();
          $this->checkRegisteredCliCommands();
        }
    }

}
