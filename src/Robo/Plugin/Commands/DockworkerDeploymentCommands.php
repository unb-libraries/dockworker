<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerException;
use Dockworker\DockworkerLogCheckerTrait;
use Dockworker\KubernetesDeploymentTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerLocalCommands;
use Robo\Robo;

/**
 * Defines the commands used to interact with Kubernetes deployment resources.
 */
class DockworkerDeploymentCommands extends DockworkerLocalCommands {

  use KubernetesDeploymentTrait;

  /**
   * Retrieves the rollout status for this application's k8s deployment.
   *
   * @param string $env
   *   The environment to check.
   *
   * @command k8s:deployment:status
   * @throws \Dockworker\DockworkerException
   * @throws \Exception
   *
   * @usage k8s:deployment:status prod
   *
   * @kubectl
   */
  public function getDeploymentRolloutStatus($env) {
    $this->deployedK8sResourceInit($this->repoRoot, $env);
    $this->kubectlExec(
      'rollout',
      [
        'status',
        'deployment',
        $this->deployedK8sResourceName,
        '--namespace',
        $this->deployedK8sResourceNameSpace,
      ],
      TRUE
    );
  }

  /**
   * Restarts this application's k8s deployment.
   *
   * @param string $env
   *   The environment to update.
   *
   * @command k8s:deployment:restart
   * @throws \Dockworker\DockworkerException
   * @throws \Exception
   *
   * @usage k8s:deployment:restart prod
   *
   * @kubectl
   */
  public function restartDeployment($env) {
    $this->deployedK8sResourceInit($this->repoRoot, $env);
    $this->kubectlExec(
      'rollout',
      [
        'restart',
        "deployment/{$this->deployedK8sResourceName}",
        '--namespace',
        $this->deployedK8sResourceNameSpace,
      ],
      TRUE
    );
    $this->kubectlExec(
      'rollout',
      [
        'status',
        '--timeout=300s',
        "deployment/{$this->deployedK8sResourceName}",
        '--namespace',
        $this->deployedK8sResourceNameSpace,
      ],
      TRUE
    );
  }

  /**
   * Sets the docker image this application's k8s deployment deploys.
   *
   * @param string $image
   *   The docker image to use in the deployment.
   * @param string $tag
   *   The docker image tag to use in the deployment.
   * @param string $env
   *   The environment to update.
   *
   * @command k8s:deployment:image:update
   * @throws \Dockworker\DockworkerException
   * @throws \Exception
   *
   * @usage k8s:deployment:image:update ghcr.io/unb-libraries/lib.unb.ca prod-20200228122322 prod
   *
   * @kubectl
   */
  public function setDeploymentImage($image, $tag, $env) {
    $this->deployedK8sResourceInit($this->repoRoot, $env);
    $this->kubectlExec(
      'set',
      [
        'image',
        '--record',
        "deployment/{$this->deployedK8sResourceName}",
        "{$this->deployedK8sResourceName}=$image:$tag",
        '--namespace',
        $this->deployedK8sResourceNameSpace,
      ],
      TRUE
    );
    $this->kubectlExec(
      'rollout',
      [
        'status',
        'deployment',
        $this->deployedK8sResourceName,
        '--namespace',
        $this->deployedK8sResourceNameSpace,
      ],
      TRUE
    );
  }

  /**
   * Updates the metdata defining this application's k8s deployment.
   *
   * @param string $file
   *   The path to the YAML deployment definition file to apply. This file must
   *   define the namespace to update.
   *
   * @command k8s:deployment:update
   * @throws \Dockworker\DockworkerException
   *
   * @usage k8s:deployment:update /tmp/deployment/lib-unb-ca.Deployment.prod.yaml
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
   * Deletes this application's k8s deployment.
   *
   * @param string $file
   *   The path to the YAML deployment definition file to delete. This file must
   *   define the namespace to update.
   *
   * @command k8s:deployment:delete
   * @throws \Dockworker\DockworkerException
   *
   * @usage k8s:deployment:delete /tmp/deployment/lib-unb-ca.Deployment.prod.yaml
   *
   * @kubectl
   */
  public function deleteDeploymentImage($file) {
    $this->kubectlExec(
      'delete',
      [
        '-f',
        $file,
        '--ignore-not-found=true',
      ],
      TRUE
    );
  }

  /**
   * Deletes this application's k8s deployment and re-creates it.
   *
   * @param string $file
   *   The path to the YAML deployment definition file to apply. This file must
   *   define the namespace to update.
   *
   * @command k8s:deployment:delete-apply
   * @throws \Dockworker\DockworkerException
   *
   * @usage k8s:deployment:delete-apply /tmp/lib-unb-ca.Deployment.prod.yaml
   *
   * @kubectl
   */
  public function deleteApplyDeploymentImage($file) {
    $this->deleteDeploymentImage($file);
    $this->applyDeploymentImage($file);
  }

  /**
   * Retrieves this application's k8s deployment logs.
   *
   * @param string $env
   *   The environment to obtain the logs from.
   *
   * @command logs:deployed
   * @throws \Exception
   *
   * @usage logs:deployed prod
   *
   * @kubectl
   */
  public function printDeploymentLogs($env) {
    $this->k8sInitSetupPods($env, 'deployment', 'Logs');
    $this->kubernetesPrintLogsFromCurrentPods();
  }

  /**
   * Determines if this application's k8s deployment logs contain errors.
   *
   * @param string $env
   *   The environment to check the logs in.
   *
   * @command logs:check:deployed
   * @throws \Exception
   *
   * @usage logs:check:deployed prod
   *
   * @kubectl
   */
  public function checkDeploymentLogs($env) {
    // Allow modules to implement custom handlers to trigger errors.
    $handlers = $this->getCustomEventHandlers('dockworker-deployment-log-error-triggers');
    foreach ($handlers as $handler) {
      $this->addLogErrorTriggers($handler());
    }

    // Allow modules to implement custom handlers to add exceptions.
    $handlers = $this->getCustomEventHandlers('dockworker-deployment-log-error-exceptions');
    foreach ($handlers as $handler) {
      $this->addLogErrorExceptions($handler());
    }


    $this->k8sInitSetupPods($env, 'deployment', 'Check Logs');
    $this->kubernetesCheckLogsFromCurrentPods();
  }

  /**
   * Opens a shell within this application's k8s deployment.
   *
   * @param string $env
   *   The environment to open a shell to.
   * @param string $shell
   *   The path within pod to the shell binary to execute.
   *
   * @command shell:deployed
   * @throws \Exception
   *
   * @return \Robo\Result
   *   The result of the shell.
   *
   * @usage shell:deployed prod /bin/sh
   *
   * @kubectl
   */
  public function openDeploymentShell($env, $shell = '') {
    if (empty($shell)) {
      $shell = $this->applicationShell;
    }
    $pod_id = $this->k8sGetLatestPod($env, 'deployment', 'Open Shell');
    $this->io()->note('Opening remote pod shell... Type "exit" when finished.');
    return $this->taskExec($this->kubeCtlBin)
      ->arg('exec')->arg('-it')->arg($pod_id)
      ->arg("--namespace={$this->kubernetesPodParentResourceNamespace}")
      ->arg('--')
      ->arg($shell)
      ->run();
  }

  /**
   * Initializes, sets up a k8s resource/pods for interaction.
   *
   * @param string $env
   *   The environment to initialize.
   * @param $type
   *   The type of k8s resource to target.
   * @param $action
   *   The intended action being taken, used for messaging.
   *
   * @return void
   * @throws \Exception
   */
  protected function k8sInitSetupPods($env, $type, $action) {
    $this->deployedK8sResourceInit($this->repoRoot, $env, $type);
    $this->kubernetesSetupPods(
      $this->deployedK8sResourceName,
      $type,
      $this->deployedK8sResourceNameSpace,
      $action
    );
  }

  /**
   * Retrieves the name of the latest-deployed k8s pod in a resource.
   *
   * @param string $env
   *   The environment to initialize.
   * @param $type
   *   The type of k8s resource to target.
   * @param $action
   *   The intended action being taken, used for messaging.
   *
   * @return void
   * @throws \Exception
   */
  protected function k8sGetLatestPod($env, $type, $action) {
    $this->k8sInitSetupPods($env, $type, $action);
    return $this->kubernetesGetLatestPod();
  }

  /**
   * Runs multiple commands sequentially in pod(s) of a resource.
   *
   * @param array $env
   *   The environments to run the commands in.
   * @param array $commands
   *   The commands to run.
   * @param bool $all_pods
   *   FALSE if the command should only be run in a single pod. TRUE otherwise.
   *
   */
  protected function runMultipleInstanceCommands($env = [], $commands = [], $all_pods = FALSE) {
    $pods = $this->k8sInitSetupPods($env, 'deployment', 'Multiple Instance');
    foreach ($this->kubernetesCurPods as $pod) {
      foreach ($commands as $cmd) {
        $this->kubernetesPodExecCommand(
          $pod_id,
          $env,
          $cmd
        );
      }
      if ($all_pods == FALSE) {
        break;
      }
    }
  }

}
