<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Cli\DockerCliTrait;
use Dockworker\Cli\KubectlCliTrait;
use Dockworker\Docker\DockerComposeTrait;
use Dockworker\Docker\DockerImageBuilderTrait;
use Dockworker\DockworkerCommands;
use Dockworker\IO\DockworkerIOTrait;
use Dockworker\System\LocalHostFileOperationsTrait;

/**
 * Provides commands for building and deploying the application locally.
 */
class DockworkerLocalApplicationCommands extends DockworkerCommands
{
    use DockerCliTrait;
    use DockerComposeTrait;
    use DockworkerIOTrait;
    use KubectlCliTrait;
    use LocalHostFileOperationsTrait;

  /**
   * @hook post-init
   */
    public function initRequirements(): void
    {
        $this->registerDockerCliTool($this->dockworkerIO);
        $this->checkPreflightChecks($this->dockworkerIO);
    }

    /**
     * Deploys the application locally.
     *
     * @command deploy
     * @aliases start-over redeploy
     *
     * @throws \Dockworker\DockworkerException
     */
    public function deployComposeApplication(): void
    {
        $this->dockworkerIO->title("Deploying $this->applicationName Locally");
        $this->stopRemoveComposeApplicationData();
        $this->setLocalHostFileEntries();
        $this->buildComposeApplication();
        $this->startComposeApplication();
        $this->followComposeApplicationLogs();
    }
}
