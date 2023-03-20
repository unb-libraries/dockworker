<?php

namespace Dockworker\K8s;

use Consolidation\Config\ConfigInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Dockworker\Docker\DeployedResourcesTrait;
use Dockworker\Docker\DockerContainer;
use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to access deployed kubernetes resources.
 *
 * @INTERNAL This trait is intended only to be used by Dockworker commands. It
 * references user properties which are not in its own scope.
 */
trait DeployedK8sResourcesTrait
{
    use DeployedResourcesTrait;

    /**
     * Enables the discovery of deployed kubernetes pods.
     */
    protected function enableK8sResourceDiscovery(): void
    {
        $this->deployedContainerDiscoveryMethods[] = [
            'name' => 'deployed kubernetes pods',
            'method' => 'discoverDeployedK8sContainers',
        ];
    }

    /**
     * Discovers the currently deployed kubernetes pods.
     *
     * @param ConfigInterface $config
     *   The configuration object.
     */
    private function discoverDeployedK8sContainers(ConfigInterface $config): void
    {
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
     * @TODO For now, this only sets pods from the NewReplicaSet, as I do not
     *   believe we have many core use cases for the OldReplicaSets.
     */
    private function setDeployedK8sPods(
        string $deployment_name,
        string $env
    ): void {
        $replica_sets = $this->getDeployedK8sDeploymentReplicaSets($deployment_name, $env);
        if (!empty($replica_sets)) {
            foreach ($replica_sets as $replica_set) {
                $pods = $this->getDeployedK8sDeploymentPods($replica_set, $env);
                if (!empty($pods)) {
                    foreach ($pods as $pod_name) {
                        $this->deployedDockerContainers[] = $this->getContainerObjectFromK8sPod(
                            $pod_name,
                            $env,
                            [$replica_set]
                        );
                    }
                }
            }
        }
    }

    /**
     * Gets the latest replica set for a deployment.
     *
     * @param string $deployment
     *   The deployment to get the replica set for.
     * @param string $env
     *   The environment to get the replica set for.
     * @param string $type
     *   The type of replica set to get. Defaults to 'NewReplicaSet'.
     *
     * @return string[]
     *    An array of replica set names.
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
            return $this->getReplicaSetsFromDeploymentDescribeOutput($output, $type);
        }
        return [];
    }

    /**
     * Gets the replica sets from the output of a deployment describe command.
     *
     * @param string $output
     *   The output of the describe command.
     * @param string $type
     *   The type of replica set to get. Defaults to 'NewReplicaSet'.
     *
     * @return string[]
     * @TODO This is a gross way to get the replica sets. It should be
     *   replaced with a proper JSON parser, but kubectl describe currently
     *   does NOT allow outputs in JSON format.
     */
    private function getReplicaSetsFromDeploymentDescribeOutput(
        string $output,
        string $type = 'NewReplicaSet'
    ): array {
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (str_contains($line, "$type:")) {
                $replica_set = explode(' ', $line);
                if (!empty($replica_set[3]) && $replica_set[3] !== '<none>') {
                    return (explode(',', $replica_set[3]));
                }
            }
        }
        return [];
    }

    /**
     * Gets the pods belonging to a replica set.
     *
     * @param string $set_name
     *   The name of the replica set to get the pods for.
     * @param string $env
     *   The environment to get the pods for.
     *
     * @return string[]
     *   An array of pod names belonging to the replica set.
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
        return [];
    }

    /**
     * Gets the pod template hash for a replica set.
     *
     * @param string $set_name
     *   The name of the replica set to get the pod template hash for.
     * @param string $env
     *   The environment to get the pod template hash for.
     *
     * @return string
     *   The pod template hash for the replica set.
     */
    protected function getDeployedK8sReplicationSetPodTemplateHash(
        string $set_name,
        string $env
    ): string {
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
     * Gets a container object from a Kubernetes pod name.
     *
     * @param string $pod_name
     *   The name of the pod to get the container object for.
     * @param string $env
     *   The environment to get the container object for.
     * @param string[] $parents
     *   The replica controllers of the container.
     *
     * @return DockerContainer
     *   The container object.
     */
    private function getContainerObjectFromK8sPod(
        string $pod_name,
        string $env,
        array $parents = []
    ): DockerContainer {
        $pod_details = $this->getK8sPodDetails($pod_name, $env);
        return DockerContainer::create(
            $pod_name,
            $env,
            $pod_details['spec']['containers'][0]['image'],
            $pod_details['status']['phase'],
            DateTimeImmutable::createFromFormat(
                DateTimeInterface::RFC3339,
                $pod_details['metadata']['creationTimestamp']
            ),
            $parents,
            $this->getContainerExecEntryPointFromK8sPod($pod_name, $env),
            $this->getContainerCopyEntryPointFromK8sPod($env)
        );
    }

    /**
     * Gets the details of a Kubernetes pod.
     *
     * @param string $pod_name
     *   The name of the pod to get the details for.
     * @param string $env
     *   The environment to get the pod details for.
     *
     * @return array
     *   The details of the pod.
     */
    private function getK8sPodDetails(
        string $pod_name,
        string $env
    ): array {
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

    /**
     * Gets the CLI exec entry point for a Kubernetes pod.
     *
     * @param string $pod_name
     *   The name of the pod to get the exec entry point for.
     * @param string $env
     *   The environment to get the exec entry point for.
     *
     * @return string[]
     *   The exec entry point for the pod.
     */
    private function getContainerExecEntryPointFromK8sPod(
        string $pod_name,
        string $env
    ): array {
        return [
            $this->cliTools['kubectl'],
            'exec',
            "--namespace=$env",
            '-it',
            $pod_name,
            '--',
        ];
    }

    /**
     * Gets the copy entry point for a Kubernetes pod.
     *
     * @return string[]
     *   The copy entry point for the pod.
     */
    private function getContainerCopyEntryPointFromK8sPod(
        string $env
    ): array {
        return [
            $this->cliTools['kubectl'],
            'cp',
            "--namespace=$env",
        ];
    }
}
