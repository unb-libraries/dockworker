<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerLogCheckerTrait;
use Dockworker\KubernetesPodTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerLocalCommands;
use Robo\Robo;

/**
 * Defines the commands used to interact with Kubernetes deployments.
 */
class DockworkerDeploymentCommands extends DockworkerLocalCommands {

  use DockworkerLogCheckerTrait;
  use KubernetesPodTrait;

  /**
   * Checks the k8s deployment rollout status.
   *
   * @param string $env
   *   The environment to check.
   *
   * @command deployment:status
   *
   * @kubectl
   */
  public function getDeploymentRolloutStatus($env) {
    $this->deploymentCommandShouldContinue($env);
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
   * Gets the k8s deployment name from the site URI.
   *
   * @param string $uri
   *   The uri to convert to deployment name.
   *
   * @return mixed
   */
  private static function getKubernetesDeploymentNameFromUri($uri) {
    return str_replace('.', '-', $uri);
  }

  /**
   * Updates the k8s deployment docker image.
   *
   * @param string $image
   *   The docker image to use in the deployment.
   * @param string $tag
   *   The docker image tag to use in the deployment.
   * @param string $env
   *   The environment to update.
   *
   * @command deployment:image:update
   *
   * @kubectl
   */
  public function setDeploymentImage($image, $tag, $env) {
    $this->deploymentCommandShouldContinue($env);
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
   * Displays the k8s deployment logs.
   *
   * @param string $env
   *   The environment to obtain the logs from.
   *
   * @command deployment:logs
   * @throws \Exception
   *
   * @kubectl
   */
  public function printDeploymentLogs($env) {
    $this->deploymentCommandShouldContinue($env);
    $logs = $this->getDeploymentLogs($env);
    $pod_counter = 0;

    if (!empty($logs)) {
      $num_pods = count($logs);
      $this->io()->title("$num_pods pods found in $env environment.");
      foreach ($logs as $pod_id => $log) {
        $pod_counter++;
        $this->io()->title("Logs for pod #$pod_counter [$env.$pod_id]");
        $this->io()->writeln($log);
      }
    }
    else {
      $this->io()->title("No pods found. No logs!");
    }
  }

  /**
   * Gets the k8s deployment logs.
   *
   * @param string $env
   *   The environment to check.
   *
   * @throws \Exception
   *
   * @return string[]
   *   An array of logs, keyed by pod IDs.
   */
  private function getDeploymentLogs($env) {
    $this->deploymentCommandShouldContinue($env);
    $this->kubernetesPodNamespace = $env;
    $this->kubernetesSetupPods($this->instanceName, "Logs");

    $logs = [];
    if (!empty($this->kubernetesCurPods)) {
      foreach ($this->kubernetesCurPods as $pod_id) {
        $logs[$pod_id] = $this->kubectlExec(
          'logs',
          [
            $pod_id,
            '--namespace',
            $env,
          ],
          FALSE
        );
      }
    }
    else {
      $this->io()->title('No pods found for deployment!');
    }
    return $logs;
  }

  /**
   * Checks the k8s deployment logs for errors.
   *
   * @param string $env
   *   The environment to check the logs in.
   *
   * @command deployment:logs:check
   * @throws \Exception
   *
   * @kubectl
   */
  public function checkDeploymentLogs($env) {
    $this->deploymentCommandShouldContinue($env);
    $logs = $this->getDeploymentLogs($env);

    // Allow modules to implement custom handlers to add exceptions.
    $handlers = $this->getCustomEventHandlers('dockworker-deployment-log-error-exceptions');
    foreach ($handlers as $handler) {
      $this->addLogErrorExceptions($handler());
    }

    if (!empty($logs)) {
      foreach ($logs as $pod_id => $log) {
        $this->checkLogForErrors($pod_id, $log);
      }
    }
    else {
      $this->io()->title("No pods found. No logs!");
    }
    $this->auditStartupLogs();
    $this->say(sprintf("No errors found, %s pods deployed.", count($logs)));
  }

  /**
   * Determines if a deployment command should continue.
   *
   * @throws \Exception
   */
  protected function deploymentCommandShouldContinue($env) {
    if ($this->environmentIsDeployable($env)) {
      return;
    }
    else {
      $this->say("Skipping deployment command for environment [$env]. Deployable environments: " . implode(',', $this->getDeployableEnvironments()));
      exit(0);
    }
  }

  /**
   * Determines if an environment is marked as deployable.
   *
   * @throws \Exception
   */
  protected function environmentIsDeployable($env) {
    $deployable_environments = $this->getDeployableEnvironments();
    if (!in_array($env, $deployable_environments)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Retrieves the environments that are marked as deployable.
   *
   * @throws \Exception
   */
  protected function getDeployableEnvironments() {
    return Robo::Config()
      ->get('dockworker.deployment.environments', []);
  }

}
