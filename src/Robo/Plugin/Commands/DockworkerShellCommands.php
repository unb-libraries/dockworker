<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Cli\DockerCliTrait;
use Dockworker\Cli\KubectlCliTrait;
use Dockworker\Docker\DeployedLocalResourcesTrait;
use Dockworker\DockworkerCommands;
use Dockworker\GitHub\GitHubClientTrait;
use Dockworker\K8s\DeployedK8sResourcesTrait;
use Robo\Robo;

/**
 * Provides commands for building and deploying the application locally.
 */
class DockworkerShellCommands extends DockworkerCommands
{
    use DockerCliTrait;
    use KubectlCliTrait;
    use GitHubClientTrait;
    use DeployedK8sResourcesTrait;
    use DeployedLocalResourcesTrait;

    /**
     * Opens a shell into the application.
     *
     * @command shell
     *
     * @throws \Dockworker\DockworkerException
     */
    public function openApplicationShell($env = 'local'): void
    {
        $this->initShellCommand($env);
        $this->discoverDeployedContainers(
          $this->dockworkerIO,
          Robo::config(),
          $env
        );
        $this->dockworkerIO->title('Opening Shell');
        $this->deployedDockerContainers[0]->run(
          ['/bin/sh'],
          $this->dockworkerIO
        );
    }

    /**
     * Initializes the command and executes all preflight checks.
     *
     * @param string $env
     *   The environment to initialize the command for.
     */
    protected function initShellCommand(string $env)
    {
        if ($env === 'local') {
            // $this->initGitHubClientApplicationRepo();
            $this->registerDockerCliTool($this->dockworkerIO);
            $this->enableLocalResourceDiscovery();
        } else {
            $this->registerKubectlCliTool($this->dockworkerIO);
            $this->enableK8sResourceDiscovery();
        }
        $this->checkPreflightChecks($this->dockworkerIO);
    }
}
