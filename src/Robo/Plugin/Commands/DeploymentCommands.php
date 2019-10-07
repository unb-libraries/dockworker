<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Robo\Plugin\Commands\DockworkerApplicationCommands;
use Dockworker\KubernetesPodTrait;

/**
 * Commands to interact with Kubernetes deployments.
 */
class DeploymentCommands extends DockworkerApplicationCommands {

  use KubernetesPodTrait;

  /**
   * Check the deployment rollout status.
   *
   * @param string $env
   *   The deploy environment to check.
   *
   * @command deployment:rollout:status
   *
   * @throws \Exception
   */
  public function getDeploymentRolloutStatus($env) {
    $this->kubectlExec(
      'rollout',
      [
        'status',
        'deployment',
        $this->getKubernetesDeploymentNameFromUri($this->instanceName),
        '--namespace',
        $env,
      ],
      TRUE
    );
  }

  /**
   * Update the deployment image.
   *
   * @param string $image
   *   The image to set.
   * @param string $tag
   *   The image tag to set.
   * @param string $env
   *   The deploy environment to check.
   *
   * @command deployment:image:update
   *
   * @throws \Exception
   */
  public function setDeploymentImage($image, $tag, $env) {
    $deployment_name = $this->getKubernetesDeploymentNameFromUri($this->instanceName);
    $this->kubectlExec(
      'set',
      [
        'image',
        '--record',
        "deployment/{$deployment_name}",
        "{$deployment_name}=$image:$tag",
        '--namespace',
        $env,
      ],
      TRUE
    );
  }

  /**
   * Get the deployment logs.
   *
   * @param string $env
   *   The deploy environment to check.
   *
   * @command deployment:logs
   *
   * @throws \Exception
   */
  public function getDeploymentLogs($env) {
    $this->kubernetesPodNamespace = $env;
    $this->kubernetesSetupPods($this->instanceName, "Logs");

    if (!empty($this->kubernetesCurPods)) {
      foreach ($this->kubernetesCurPods as $pod_id) {
        $this->say("Logs for $pod_id:");
        $this->kubectlExec(
          'logs',
          [
            $pod_id,
            '--namespace',
            $env,
          ],
          TRUE
        );
      }
    }
    else {
      $this->say('No pods found for deployment...');
    }
  }

  /**
   * Get the kubernetes deployment name from the site URI.
   *
   * @param string $uri
   *   The uri to convert to deployment name.
   *
   * @return mixed
   */
  private static function getKubernetesDeploymentNameFromUri($uri) {
    return str_replace('.', '-', $uri);
  }

}
