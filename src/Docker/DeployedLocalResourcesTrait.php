<?php

namespace Dockworker\Docker;

use Dockworker\Cli\DockerCliTrait;
use Dockworker\Docker\DeployedContainersTrait;
use Dockworker\Docker\DockerContainer;
use Dockworker\IO\DockworkerIO;

trait DeployedLocalResourcesTrait
{
    use DeployedContainersTrait;
    use DockerCliTrait;

    protected function enableLocalResourceDiscovery(): void
    {
        $this->deployedContainerDiscoveryMethods[] = [
          'name' => 'local docker containers',
          'method' => 'discoverDeployedLocalContainers',
        ];
    }

    private function discoverDeployedLocalContainers(
      DockworkerIO $io,
      $config,
      $env
    ) {
        foreach (
          $this->getConfigItem(
            $config,
            'dockworker.application.workflows.local.deployments'
          ) as $service
        ) {
            if (!empty($service['name'])) {
                $this->deployedDockerContainers[] = $this->getContainerObjectFromLocalContainer($service['name']);
            }
        }
    }

    private function getContainerObjectFromLocalContainer($name) {
        $container_details = $this->getLocalContainerDetails($name);
        return DockerContainer::create(
          $name,
          'local',
          'Local',
          $container_details[0]['State']['Status'],
          \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::RFC3339,
            preg_replace(
              '~\.\d+~',
              '',
              $container_details[0]['Created']
            )
          ),
          [],
          $this->getContainerExecEntryPointFromLocalContainer($name)
        );
    }

    private function getLocalContainerDetails($name) {
        $cmd = $this->dockerRun(
          [
            'inspect',
            '--format',
            'json',
            $name,
          ],
          'Discover local container details',
          null,
          false
        );
         return json_decode($cmd->getOutput(), true);
    }

    private function getContainerExecEntryPointFromLocalContainer($pod_name) {
        return [
          $this->cliTools['docker'],
          'exec',
          '-it',
          $pod_name,
        ];
    }
}
