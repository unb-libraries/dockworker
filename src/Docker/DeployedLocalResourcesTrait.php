<?php

namespace Dockworker\Docker;

use Consolidation\Config\ConfigInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Dockworker\Cli\DockerCliTrait;
use Dockworker\Docker\DeployedResourcesTrait;
use Dockworker\Docker\DockerContainer;
use Exception;

/**
 * Provides methods to access deployed local resources.
 */
trait DeployedLocalResourcesTrait
{
    use DeployedResourcesTrait;
    use DockerCliTrait;

    /**
     * Registers the local docker container discovery method.
     */
    protected function enableLocalResourceDiscovery(): void
    {
        $this->deployedContainerDiscoveryMethods[] = [
            'name' => 'local docker containers',
            'method' => 'discoverDeployedLocalContainers',
        ];
    }

    /**
     * Discovers deployed local containers.
     *
     * @param ConfigInterface $config
     *   The configuration object.
     */
    private function discoverDeployedLocalContainers(ConfigInterface $config): void
    {
        foreach (
            $this->getConfigItem(
                $config,
                'dockworker.application.workflows.local.deployments'
            ) as $service
        ) {
            if (!empty($service['name'])) {
                $container = $this->getContainerObjectFromLocalContainer($service['name']);
                if (!empty($container)) {
                    $this->deployedDockerContainers[] = $container;
                }
            }
        }
    }

    /**
     * Gets a docker container object from a local container.
     *
     * @param string $name
     *   The name of the container.
     *
     * @return \Dockworker\Docker\DockerContainer|null
     *   The container object.
     */
    private function getContainerObjectFromLocalContainer(
        string $name
    ): DockerContainer|null {
        $container_details = $this->getLocalContainerDetails($name);
        if (
            !empty($container_details[0]['State']['Status'])
            && $container_details[0]['State']['Status'] != 'exited'
        ) {
            return DockerContainer::create(
                $name,
                'local',
                'Local',
                $container_details[0]['State']['Status'],
                DateTimeImmutable::createFromFormat(
                    DateTimeInterface::RFC3339,
                    preg_replace(
                        '~\.\d+~',
                        '',
                        $container_details[0]['Created']
                    )
                ),
                [],
                $this->getContainerExecEntryPointFromLocalContainer($name),
                $this->getContainerCopyEntryPointFromLocalContainer()
            );
        }
        return null;
    }

    /**
     * Gets the details of a local container.
     *
     * @param string $name
     *   The name of the container.
     *
     * @return array
     *   The details of the container.
     */
    private function getLocalContainerDetails(string $name): array
    {
        try {
            $cmd = $this->dockerRun(
                [
                    'inspect',
                    $name,
                ],
                'Discover local container details',
                null,
                false
            );
            return json_decode($cmd->getOutput(), true);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Gets the exec entry point for a local container.
     *
     * @param string $pod_name
     *   The name of the container.
     *
     * @return string[]
     *   The exec entry point for the container.
     */
    private function getContainerExecEntryPointFromLocalContainer(
        string $pod_name
    ): array {
        return [
            $this->cliTools['docker'],
            'exec',
            '-it',
            $pod_name,
        ];
    }

    /**
     * Gets the copy entry point for a local container.
     *
     * @return string[]
     *   The copy entry point for the container.
     */
    private function getContainerCopyEntryPointFromLocalContainer(): array
    {
        return [
            $this->cliTools['docker'],
            'cp',
            '-q',
        ];
    }
}
