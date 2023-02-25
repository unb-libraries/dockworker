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
        $container = $this->getDeployedContainer($env);

        $this->dockworkerIO->title('Creating Shell');
        $this->dockworkerIO->info(
          "Creating shell in $env/{$container->getContainerName()}. Type 'exit' to close."
        );

        $container->run(
          [$this->getApplicationShell()],
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
        $this->discoverDeployedResources(
          $this->dockworkerIO,
          Robo::config(),
          $env
        );
    }

    protected function getApplicationShell() {
        return $this->getConfigItem(
          Robo::config(),
          'dockworker.application.shell.shell',
          '/bin/sh'
        );
    }

}
