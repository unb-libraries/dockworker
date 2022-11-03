<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\ApplicationShellTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerDeploymentCommands;

/**
 * Defines the commands used to interact with a Dockworker deployed application.
 */
class DockworkerDeploymentDaemonCommands extends DockworkerDeploymentCommands {

  use ApplicationShellTrait;

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
   * @shell
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

}
