<?php

namespace Dockworker\Docker;

use Dockworker\Cli\DockerCli;
use Dockworker\Cli\DockerCliTrait;
use Dockworker\Cli\KubectlCli;
use Dockworker\Cli\KubectlCliTrait;
use Dockworker\IO\DockworkerIO;
use Grasmash\SymfonyConsoleSpinner\Checklist;
use Robo\Config\Config;

/**
 * Provides methods to run commands inside containers regardless of environment.
 */
trait DeployedContainersTrait
{

    /**
     * @var \Dockworker\Docker\DockerContainer[]
     */
    protected array $deployedDockerContainers = [];

    /**
     * @var string[]
     */
    protected array $deployedContainerDiscoveryMethods = [];

    private function discoverDeployedContainers(
      DockworkerIO $io,
      Config $config,
      string $env
    ) {
        if (!empty($this->deployedContainerDiscoveryMethods)) {
            $checklist = new Checklist($io->output());
            $io->title('Resource Discovery');
            foreach ($this->deployedContainerDiscoveryMethods as $discovery) {
                $checklist->addItem(
                    sprintf(
                    "Discovering %s",
                    $discovery['name']
                    )
                );
                $this->{$discovery['method']}($io, $config, $env);
                $checklist->completePreviousItem();
            }
            $io->newLine();
        }
    }
}
