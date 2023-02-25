<?php

namespace Dockworker\K8s;

use Dockworker\Docker\DeployedContainersTrait;
use Dockworker\Docker\DockerContainer;
use Dockworker\Storage\DockworkerPersistentDataStorageTrait;
use Dockworker\IO\DockworkerIO;

trait DeployedK8sResourcesTrait
{
    use DeployedContainersTrait;

    protected function enableK8sResourceDiscovery(): void
    {
        $this->deployedContainerDiscoveryMethods[] = [
          'name' => 'deployed kubernetes pods',
          'method' => 'discoverDeployedK8sContainers',
        ];
    }

    private function discoverDeployedK8sContainers(
      DockworkerIO $io,
      $config,
      $env
    ) {
        foreach (
          $this->getConfigItem(
            $config,
            'dockworker.application.workflows.k8s.deployments'
          ) as $service
        ) {
            if (!empty($service['namespaces']) && !empty($service['name'])) {
                foreach ($service['namespaces'] as $namespace) {
                    $this->setDeployedK8sPods($service['name'], $namespace);
                }
            }
        }
    }

    /**
     * Sets the latest pods sets for the deployments.
     *
     * For now, this only sets pods from the NewReplicaSet, as I do not believe
     * we would have many core use cases for the OldReplicaSets.
     *
     * @throws \Dockworker\DockworkerException
     */
    private function setDeployedK8sPods($deployment_name, $env)
    {
        $replica_sets = $this->getDeployedK8sDeploymentReplicaSets($deployment_name, $env);
        if (!empty($replica_sets)) {
          foreach ($replica_sets as $replica_set) {
              $pods = $this->getDeployedK8sDeploymentPods($replica_set, $env);
              if (!empty($pods)) {
                  foreach ($pods as $pod_name) {
                      $this->deployedDockerContainers[] = $this->getContainerObjectFromK8sPod($pod_name, $env, [$replica_set]);
                  }
              }
          }
        }
    }

    /**
     * Gets the pods belonging to a replica set.
     *
     * @param string $set_name
     * @param string $env
     *
     * @return void
     * @throws \Dockworker\DockworkerException
     */
    protected function getDeployedK8sDeploymentPods(
      string $set_name,
      string $env
    ): array {
        $hash = $this->getDeployedK8sReplicationSetPodTemplateHash($set_name, $env);
        $cmd = $this->kubeCtlRun(
          [
            'get',
            'pods',
            "--namespace=$env",
            '-o',
            'jsonpath={.items[*].metadata.name}',
            "-l",
            "pod-template-hash=$hash",
          ],
          'Get pods for replica set',
          null,
          false
        );
        $output = $cmd->getOutput();
        if (!empty($output)) {
            return (explode("\n", $output));
        }
    }

    protected function getDeployedK8sReplicationSetPodTemplateHash($set_name, $env) {
        $cmd = $this->kubeCtlRun(
          [
            'get',
            "replicaset/$set_name",
            "--namespace=$env",
            '-o',
            'jsonpath={.metadata.labels.pod-template-hash}',
          ],
          'Get pod template hash for replica set',
          null,
          false
        );
        $output = $cmd->getOutput();
        if (!empty($output)) {
            return $output;
        }
        return '';
    }

    /**
     * Gets the latest replica set for a deployment.
     *
     * @param string $deployment
     * @param string $type
     *
     * @return void
     * @throws \Dockworker\DockworkerException
     */
    protected function getDeployedK8sDeploymentReplicaSets(
      string $deployment,
      string $env,
      string $type = 'NewReplicaSet'
    ): array {
        $cmd = $this->kubeCtlRun(
          [
            'describe',
            "deployment/$deployment",
            "--namespace=$env",
          ],
          'Describe deployment in namespace',
          null,
          false
        );
        $output = $cmd->getOutput();
        if (!empty($output)) {
            return $this->getReplicaSetsFromDeploymentDescribeOutput($output);
        }
        return [];
    }

    /**
     * @param $output
     * @param $type
     *
     * @return array|string[]
     * @TODO This is a gross way to get the replica sets. It should be
     *   replaced with a proper JSON parser, but kubectl describe currently
     *   does NOT allow outputs in JSON format!
     */
    private function getReplicaSetsFromDeploymentDescribeOutput(
      string $output,
      string $type = 'NewReplicaSet'
    ) {
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (strpos($line, "$type:") !== FALSE) {
                $replica_set = explode(' ', $line);
                if (!empty($replica_set[3]) && $replica_set[3] !== '<none>') {
                    return (explode(',', $replica_set[3]));
                }
            }
        }
        return [];
    }

    private function getContainerObjectFromK8sPod($pod_name, $env, $parents = []) {
        $pod_details = $this->getK8sPodDetails($pod_name, $env);
        return DockerContainer::create(
          $pod_name,
          $env,
          $pod_details['spec']['containers'][0]['image'],
          $pod_details['status']['phase'],
          \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::RFC3339,
            $pod_details['metadata']['creationTimestamp']
          ),
          $parents,
          $this->getContainerExecEntryPointFromK8sPod($pod_name, $env)
        );
    }

    private function getK8sPodDetails($pod_name, $env) {
        $cmd = $this->kubeCtlRun(
          [
            'get',
            'pods',
            "--namespace=$env",
            $pod_name,
            '-o',
            'json',
          ],
          'Get pod details',
          null,
          false
        );
        $output = $cmd->getOutput();
        if (!empty($output)) {
            return json_decode($output, true);
        }
        return [];
    }

    private function getContainerExecEntryPointFromK8sPod($pod_name, $env) {
        return [
            $this->cliTools['kubectl'],
            'exec',
            "--namespace=$env",
            '-it',
            $pod_name,
            '--',
        ];
    }
}
