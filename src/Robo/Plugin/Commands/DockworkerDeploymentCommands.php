<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerException;
use Dockworker\DockworkerLogCheckerTrait;
use Dockworker\KubernetesDeploymentTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerLocalCommands;
use Robo\Robo;

/**
 * Defines the commands used to interact with Kubernetes deployments.
 */
class DockworkerDeploymentCommands extends DockworkerLocalCommands {

  use DockworkerLogCheckerTrait;
  use KubernetesDeploymentTrait;

  const ERROR_NO_PODS_IN_DEPLOYMENT = 'No pods were found for the deployment [%s:%s].';
  const ERROR_UNKNOWN_POD_ID = 'Pod ID [%s] not found in deployment [%s:%s].';

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
   * Restarts the k8s deployment rollout.
   *
   * @param string $env
   *   The environment to update.
   *
   * @command deployment:restart
   * @throws \Dockworker\DockworkerException
   * @throws \Exception
   *
   * @usage deployment:restart prod
   *
   * @kubectl
   */
  public function restartDeployment($env) {
    $this->deploymentCommandInit($this->repoRoot, $env);
    $this->kubectlExec(
      'rollout',
      [
        'restart',
        "deployment/{$this->deploymentK8sName}",
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
      throw new DockworkerException(
        sprintf(
          self::ERROR_NO_PODS_IN_DEPLOYMENT,
          $this->deploymentK8sName,
          $this->deploymentK8sNameSpace
        )
      );
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
      $this->printDeploymentLogs($env);
      $this->printStartupLogErrors();
      if (!empty(getenv('TRAVIS'))){
        $this->io()->writeln('Sleeping to allow Travis io to flush...');
        sleep(30);
      }
      throw new DockworkerException("Error(s) found in deployment startup logs!");
    }

    $this->say(sprintf("No errors found, %s pods deployed.", count($logs)));
  }

  /**
   * Open a shell into the k8s deployment.
   *
   * @param string $env
   *   The environment to open a shell to.
   * @param string $shell
   *   The path within pod to the shell binary to execute.
   *
   * @command deployment:shell
   * @throws \Exception
   *
   * @return \Robo\Result
   *   The result of the shell.
   *
   * @usage deployment:shell prod /bin/sh
   *
   * @kubectl
   */
  public function openDeploymentShell($env, $shell = '/bin/sh') {
    $this->deploymentCommandInit($this->repoRoot, $env);
    $this->kubernetesPodNamespace = $this->deploymentK8sNameSpace;
    $this->kubernetesSetupPods($this->deploymentK8sName, "Shell");

    if (!empty($this->kubernetesCurPods)) {
      $first_pod = reset($this->kubernetesCurPods);

      if (count($this->kubernetesCurPods) > 1) {
        $table_rows = array_map(
          function ($el) {
            return [$el];
          },
          $this->kubernetesCurPods
        );
        $this->printConsoleTable(
          "Available Pods - {$this->deploymentK8sName}[{$this->kubernetesPodNamespace}]:",
          ['Pod ID'],
          $table_rows
        );
        $pod_id = $this->askDefault('Enter the Pod ID to shell to: ', $first_pod);
      }
      else {
        $pod_id = $first_pod;
        $this->io()->note("Only one pod in deployment: $pod_id");
      }

      if (!in_array($pod_id, $this->kubernetesCurPods)) {
        throw new DockworkerException(
          sprintf(
            self::ERROR_UNKNOWN_POD_ID,
            $pod_id,
            $this->deploymentK8sName,
            $this->deploymentK8sNameSpace
          )
        );
      }
      $this->io()->writeln('Opening remote shell... Type "exit" to quit.');
      return $this->taskExec($this->kubeCtlBin)
        ->arg('exec')->arg('-it')->arg($pod_id)
        ->arg("--namespace={$this->kubernetesPodNamespace}")
        ->arg('--')
        ->arg($shell)
        ->run();
    }
    else {
      throw new DockworkerException(
        sprintf(
          self::ERROR_NO_PODS_IN_DEPLOYMENT,
          $this->deploymentK8sName,
          $this->deploymentK8sNameSpace
        )
      );
    }
  }

}
