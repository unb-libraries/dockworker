<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerLogCheckerTrait;
use Dockworker\KubernetesDeploymentTrait;
use Dockworker\KubernetesPodTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerLocalCommands;
use Robo\Robo;

/**
 * Defines the commands used to interact with Kubernetes deployments.
 */
class DockworkerDeploymentCommands extends DockworkerLocalCommands {

  use DockworkerLogCheckerTrait;
  use KubernetesDeploymentTrait;

  /**
   * Checks the application's k8s deployment rollout status.
   *
   * @param string $env
   *   The environment to check.
   *
   * @command deployment:status
   * @throws \Dockworker\DockworkerException
   * @throws \Exception
   *
   * @usage deployment:status prod
   *
   * @kubectl
   */
  public function getDeploymentRolloutStatus($env) {
    $this->deploymentCommandInit($this->repoRoot, $env);
    $this->kubectlExec(
      'rollout',
      [
        'status',
        'deployment',
        $this->deploymentK8sName,
        '--namespace',
        $this->deploymentK8sNameSpace,
      ],
      TRUE
    );
  }

  /**
   * Updates the application's k8s deployment image.
   *
   * @param string $image
   *   The docker image to use in the deployment.
   * @param string $tag
   *   The docker image tag to use in the deployment.
   * @param string $env
   *   The environment to update.
   *
   * @command deployment:image:update
   * @throws \Dockworker\DockworkerException
   * @throws \Exception
   *
   * @usage deployment:image:update unblibraries/lib.unb.ca prod-20200228122322 prod
   *
   * @kubectl
   */
  public function setDeploymentImage($image, $tag, $env) {
    $this->deploymentCommandInit($this->repoRoot, $env);
    $this->kubectlExec(
      'set',
      [
        'image',
        '--record',
        "deployment/{$this->deploymentK8sName}",
        "{$this->deploymentK8sName}=$image:$tag",
        '--namespace',
        $this->deploymentK8sNameSpace,
      ],
      TRUE
    );
  }

  /**
   * Updates the application's k8s deployment definition.
   *
   * @param string $file
   *   The path to the YAML deployment definition file to apply. This file must
   *   define the namespace to update.
   *
   * @command deployment:apply
   * @throws \Dockworker\DockworkerException
   *
   * @usage deployment:apply /tmp/deployment/lib-unb-ca.Deployment.prod.yaml
   *
   * @kubectl
   */
  public function applyDeploymentImage($file) {
    $this->kubectlExec(
      'apply',
      [
        '-f',
        $file,
      ],
      TRUE
    );
  }

  /**
   * Displays the application's k8s deployed pod(s) logs.
   *
   * @param string $env
   *   The environment to obtain the logs from.
   *
   * @command deployment:logs
   * @throws \Exception
   *
   * @usage deployment:logs prod
   *
   * @kubectl
   */
  public function printDeploymentLogs($env) {
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
   * Gets the application's deployed k8s pod(s) logs.
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
    $this->deploymentCommandInit($this->repoRoot, $env);
    $this->kubernetesPodNamespace = $this->deploymentK8sNameSpace;
    $this->kubernetesSetupPods($this->deploymentK8sName, "Logs");

    $logs = [];
    if (!empty($this->kubernetesCurPods)) {
      foreach ($this->kubernetesCurPods as $pod_id) {
        $logs[$pod_id] = $this->kubectlExec(
          'logs',
          [
            $pod_id,
            '--namespace',
            $this->deploymentK8sNameSpace,
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
   * Checks the application's deployed k8s pod(s) logs for errors.
   *
   * @param string $env
   *   The environment to check the logs in.
   *
   * @command deployment:logs:check
   * @throws \Exception
   *
   * @usage deployment:logs:check prod
   *
   * @kubectl
   */
  public function checkDeploymentLogs($env) {
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

    try {
      $this->auditStartupLogs(FALSE);
      $this->say("No errors found in logs.");
    }
    catch (DockworkerException $e) {
      $this->printLocalLogs();
      $this->printStartupLogErrors();
      if (!empty(getenv('TRAVIS'))){
        $this->io()->writeln('Sleeping to allow Travis io to flush...');
        sleep(30);
      }
      throw new DockworkerException("Error(s) found in deployment startup logs!");
    }

    $this->say(sprintf("No errors found, %s pods deployed.", count($logs)));
  }

}
