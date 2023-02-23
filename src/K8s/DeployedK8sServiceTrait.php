<?php

namespace Dockworker\K8s;

use Dockworker\DockworkerException;
use Dockworker\Storage\DockworkerPersistentDataStorageTrait;
use Github\Client as GitHubClient;
use Robo\Robo;

trait DeployedK8sServiceTrait
{
    use \Dockworker\Core\RoboConfigTrait;

    protected array $deployedK8sDeploymentNames = [];
    protected string $deployedK8sServiceNameSpace = '';
    protected array $deployedK8sDeploymentontainers = [];

  /**
   * Initializes the application's core properties.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function setDeployedK8sServiceProperties($env): void {
    $this->deployedK8sServiceNameSpace = $env;
    $this->setDeployedK8sDeployments($env);
  }

  private function setDeployedK8sDeployments($env) {
    $config = Robo::config();
    foreach (
      $this->getConfigItem(
        $config,
        'dockworker.application.workflows.k8s.deployments'
      ) as $service
    ) {
      if (empty($this->deployedK8sDeploymentNames[$this->deployedK8sServiceNameSpace])) {
        $this->deployedK8sDeploymentNames[$this->deployedK8sServiceNameSpace] = [];
      }
      $this->deployedK8sDeploymentNames[$this->deployedK8sServiceNameSpace][] = $service['name'];
    }
  }

  private function setDeployedK8sPods() {
    foreach ($this->deployedK8sDeploymentNames as $env => $deployments) {
      foreach ($deployments as $deployment_idx => $deployment) {
        $get_rs_cmd = sprintf(
          $this->kubeCtlBin . " --kubeconfig $this->kubeCtlConf" . " describe deployment/%s --namespace=%s | grep 'ReplicaSet.*:' | awk '{ print $2 }'",
          $deployment,
          $env
        );
        $get_pod_cmd = sprintf(
          $this->kubeCtlBin . " --kubeconfig $this->kubeCtlConf" . " get pods --namespace=%s -o json | jq -r '.items[] | select(.metadata.ownerReferences[] | select(.name==\"%s\")) | .metadata.name '",
          $env,
          $this->kubernetesLatestReplicaSet
        );
      }
    }
  }

}
