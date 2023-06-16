<?php

namespace Dockworker\Docker;

use Consolidation\Config\ConfigInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Dockworker\Cli\DockerCliTrait;
use Dockworker\Core\RoboConfigTrait;
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
    use RoboConfigTrait;

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
    private function discoverDeployedLocalContainers(
        ConfigInterface $config,
        string $env
    ): void {
        foreach (
            $this->getConfigItem(
                $config,
                'dockworker.endpoints.deployments'
            ) as $id => $deployment
        ) {
            $container = $this->getContainerObjectFromLocalContainer($id);
            if (!empty($container)) {
                $this->deployedDockerContainers[] = [
                    'names' => $this->getDeployedContainerTargetNames($deployment, $id),
                    'container' => $container,
                ];
            }
        }
    }

    private function getLocalContainerTargetNames(
        string $service,
        string $id
    ): array {
        $names = [
            $id
        ];
        if (!empty($service['name'])) {
            $names[] = $service['name'];
        }
        return $names;
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
            $container_id = $container_details[0]['Id'];
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
                $this->getContainerExecEntryPointFromLocalContainer($container_id),
                $this->getContainerCopyEntryPointFromLocalContainer(),
                $this->getContainerLogsCommandFromLocalContainer($container_id)
            );
        }
        return null;
    }

    private function getLocalContainerId($name)
    {
        $container_details = $this->getLocalContainerDetails($name);
        if (!empty($container_details[0]['Id'])) {
            return $container_details[0]['Id'];
        }
        return '';
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
            $id_cmd = $this->dockerComposeRun(
                [
                    'ps',
                    '-q',
                    $name,
                ],
                'Discover local container ID',
                null,
                null,
                [],
                false
            );
            $container_id = trim($id_cmd->getOutput());
            $cmd = $this->dockerRun(
                [
                    'inspect',
                    $container_id,
                ],
                'Discover local container details',
                null,
                null,
                false
            );
            return json_decode($cmd->getOutput(), true);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Gets the exec entry point for a local docker container.
     *
     * @param string $container_id
     *   The ID of the container.
     *
     * @return string[]
     *   The exec entry point for the container.
     */
    private function getContainerExecEntryPointFromLocalContainer(
        string $container_id
    ): array {
        return [
            $this->cliTools['docker'],
            'exec',
            '-it',
            $container_id,
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

    /**
     * Gets the logs command for a local container.
     *
     * @param string $container_id
     *   The id of the container.
     *
     * @return string[]
     *   The logs command for the container.
     */
    private function getContainerLogsCommandFromLocalContainer(
        string $container_id
    ): array {
        return [
            $this->cliTools['docker'],
            'logs',
            $container_id,
        ];
    }
}
