<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockerImageTrait;
use Dockworker\DockworkerException;
use Dockworker\KubernetesDeploymentTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerLocalCommands;
use Robo\Robo;
use Robo\Symfony\ConsoleIO;

/**
 * Defines the commands used to interact with Kubernetes deployment resources.
 */
class DockworkerDeploymentCommands extends DockworkerLocalCommands {

  use DockerImageTrait;
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
   * @usage ghcr.io/unb-libraries/lib.unb.ca prod-20200228122322 prod
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
   * @usage /tmp/deployment/lib-unb-ca.Deployment.prod.yaml
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
   * Creates the k8s deployment.
   *
   * @param string $file
   *   The path to the YAML deployment definition file to apply. This file must
   *   define the namespace to update.
   *
   * @command k8s:deployment:create
   * @throws \Dockworker\DockworkerException
   *
   * @usage /tmp/deployment/lib-unb-ca.Deployment.prod.yaml
   *
   * @kubectl
   */
  public function createDeploymentImage($file) {
    $this->kubectlExec(
      'create',
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
   * @usage /tmp/deployment/lib-unb-ca.Deployment.prod.yaml
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
   * Creates required secrets for cypress testing.
   *
   * @command k8s:deployment:create-test-secrets
   * @throws \Dockworker\DockworkerException
   *
   * @kubectl
   */
  public function createTestSecrets() {
    $this->kubectlExec(
      'delete',
      [
        'secret',
        $this->instanceSlug . '-cypress',
        '--ignore-not-found=true',
        '--namespace=prod',
      ],
      TRUE
    );
    $this->kubectlExec(
      'create',
      [
        'secret',
        'generic',
        $this->instanceSlug . '-cypress',
        "--from-file=file=$this->repoRoot/tests/cypress/spec.js",
        "--from-literal=file_name={$this->instanceSlug}_spec.js",
        '--namespace=prod',
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
   * @usage /tmp/lib-unb-ca.Deployment.prod.yaml
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
   * @usage prod
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
   * @usage prod
   *
   * @kubectl
   */
  public function checkDeploymentLogs($env) {
    $this->k8sInitSetupPods($env, 'deployment', 'Check Logs');
    $this->getCustomLogTriggersExceptions('deployment');
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
   * @usage prod /bin/sh
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
      ->arg('--kubeconfig')->arg($this->kubeCtlConf)
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
   * @return string
   *   The ID of the latest pod in the configuration.
   *
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

  /**
   * Deletes the k8s deployment from the dockworker default deployment file.
   *
   * @param string $env
   *   The environment to delete the deployment from.
   *
   * @command k8s:deployment:delete:default
   * @usage dev
   *
   * @hidden
   *
   * @dockerimage
   * @kubectl
   */
  public function deleteK8sDeploymentDefault(ConsoleIO $io, $env) {
    $image_name = "{$this->dockerImageName}:$env";
    $cron_file = $this->getTokenizedKubeFile($this->repoRoot, $env, $image_name, 'deployment');
    $this->setRunOtherCommand("k8s:deployment:delete $cron_file");
  }

  /**
   * Creates the k8s deployment from the dockworker default deployment file.
   *
   * @param string $env
   *   The environment to create the deployment from.
   *
   * @command k8s:deployment:create:default
   * @usage dev
   *
   * @hidden
   *
   * @dockerimage
   * @kubectl
   */
  public function createK8sDeploymentDefault(ConsoleIO $io, $env) {
    $image_name = "{$this->dockerImageName}:$env";
    $cron_file = $this->getTokenizedKubeFile($this->repoRoot, $env, $image_name, 'deployment');
    $this->setRunOtherCommand("k8s:deployment:create $cron_file");
  }

  /**
   * Provides log checker with ignored log exception items for deployed pods.
   *
   * @hook on-event dockworker-deployment-log-error-exceptions
   */
  public function getCoreErrorLogDeployedExceptions() {
    return [
      'errors=0' => 'A report of zero errors is not an error',
    ];
  }

}
