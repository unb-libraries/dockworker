<?php

namespace Dockworker\Docker;

use Consolidation\Config\ConfigInterface;
use Dockworker\IO\DockworkerIO;
use Grasmash\SymfonyConsoleSpinner\Checklist;
use Robo\Config\Config;

/**
 * Provides methods to access deployed docker and kubernetes resources.
 */
trait DeployedResourcesTrait
{
    /**
     * The currently deployed containers.
     *
     * @var \Dockworker\Docker\DockerContainer[]
     */
    protected array $deployedDockerContainers = [];

    /**
     * The list of methods to discover deployed containers.
     *
     * @var string[]
     */
    protected array $deployedContainerDiscoveryMethods = [];

    /**
     * Discovers the currently deployed kubernetes pods.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param \Consolidation\Config\ConfigInterface $config
     *   The configuration object.
     * @param string $env
     *   The environment to discover in.
     */
    protected function discoverDeployedResources(
        DockworkerIO $io,
        ConfigInterface $config,
        string $env
    ): void {
        if (!empty($this->deployedContainerDiscoveryMethods)) {
            $io->title('Resource Discovery');
            $this->discoverDeployedContainers($io, $config, $env);
        }
    }

    /**
     * Discovers the currently deployed containers.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param \Consolidation\Config\ConfigInterface $config
     *   The configuration object.
     * @param string $env
     *   The environment to discover in.
     */
    private function discoverDeployedContainers(
        DockworkerIO $io,
        ConfigInterface $config,
        string $env
    ): void {
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

    /**
     * Retrieves a currently deployed container object.
     *
     * @param string $env
     *   The environment to retrieve the container from.
     * @param bool $pick
     *   If multiple containers are available, should one be picked? If false,
     *   the first container will be returned.
     *
     * @return \Dockworker\Docker\DockerContainer|null
     *   The container object, or null if none are available.
     */
    protected function getDeployedContainer(
        string $env,
        bool $pick = false
    ): DockerContainer|null {
        $containers = $this->getDeployedContainers($env);
        if (!empty($containers)) {
            if (!$pick) {
                return array_shift($containers);
            } else {
                // @TODO: Implement a way to pick a container.
            }
        }
        return null;
    }

    /**
     * Retrieves a list of currently deployed container objects.
     *
     * @param string $env
     *   The environment to retrieve the containers from.
     *
     * @return \Dockworker\Docker\DockerContainer[]
     *   The container objects.
     */
    private function getDeployedContainers(string $env): array
    {
        return array_filter(
            $this->deployedDockerContainers,
            function ($obj) use ($env) {
                return $obj->getContainerNamespace() == $env;
            }
        );
    }
}
