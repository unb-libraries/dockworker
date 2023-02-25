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
trait DeployedResourcesTrait
{

    /**
     * @var \Dockworker\Docker\DockerContainer[]
     */
    protected array $deployedDockerContainers = [];

    /**
     * @var string[]
     */
    protected array $deployedContainerDiscoveryMethods = [];

    private function discoverDeployedResources(
      DockworkerIO $io,
      Config $config,
      string $env
    ) {
        if (!empty($this->deployedContainerDiscoveryMethods)) {
            $io->title('Resource Discovery');
            $this->discoverDeployedContainers($io, $config, $env);
        }
    }

    private function discoverDeployedContainers(
      DockworkerIO $io,
      Config $config,
      string $env
    ) {
        if (!empty($this->deployedContainerDiscoveryMethods)) {
            $checklist = new Checklist($io->output());
            $io->section('Containers');
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

    protected function getDeployedContainer($env, $pick = false): DockerContainer|null
    {
        $containers = $this->getDeployedContainers($env);
        if (!empty($containers)) {
            if (!$pick) {
                return array_shift($containers);
            }
            else {
                // @TODO: Implement a way to pick a container.
            }
        }
        return null;
    }

    private function getDeployedContainers($env) {
        return array_filter(
            $this->deployedDockerContainers,
            function($obj) use($env) {
                return $obj->getContainerNamespace() == $env;
            }
        );
    }

}
