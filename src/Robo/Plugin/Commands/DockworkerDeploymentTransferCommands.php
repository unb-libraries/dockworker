<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerException;
use Dockworker\Robo\Plugin\Commands\DockworkerDeploymentCommands;

/**
 * Defines the commands used to copy files to/from Kubernetes deployments.
 */
class DockworkerDeploymentTransferCommands extends DockworkerDeploymentCommands {

  const ERROR_FILE_NOT_FOUND = 'Local file [%s] not found.';
  const INFO_COPYING_FILES = 'Copying file(s)...';

  /**
   * Copies a file from a local development instance to the k8s deployment.
   *
   * @param string $env
   *   The environment to copy to.
   * @param string $src
   *   The full local path of the file to copy.
   * @param string $dst
   *   The full remote path within the pod to save the file as.
   *
   * @command deployment:copy-to
   * @throws \Dockworker\DockworkerException
   *
   * @return \Robo\Result
   *   The result of the copy command.
   *
   * @usage deployment:copy-to prod /tmp/src_file.txt /tmp/dst_file.txt
   *
   * @kubectl
   */
  public function copyFileToDeploymentPod($env, $src, $dst) {
    $pods = $this->getDeploymentExecPodIds($env);
    $pod_id = array_shift($pods);

    if (!file_exists($src)) {
      throw new DockworkerException(
        sprintf(
          self::ERROR_FILE_NOT_FOUND,
          $src
        )
      );
    }
    $dst_string = "{$this->kubernetesPodNamespace}/$pod_id:$dst";
    return $this->kubeCtlCopyCommand($src, $dst_string);
  }

  /**
   * Copies a file from a k8s deployment to the local development instance.
   *
   * @param string $env
   *   The environment to copy from.
   * @param string $src
   *   The full remote path within the pod of the file to copy.
   * @param string $dst
   *   The full local path to save the file as.
   *
   * @command deployment:copy-from
   * @throws \Dockworker\DockworkerException
   *
   * @return \Robo\Result
   *   The result of the copy command.
   *
   * @usage deployment:copy-from prod /tmp/src_file.txt /tmp/dst_file.txt
   *
   * @kubectl
   */
  public function copyFileFromDeploymentPod($env, $src, $dst) {
    $pods = $this->getDeploymentExecPodIds($env);
    $pod_id = array_shift($pods);
    $src_string = "{$this->kubernetesPodNamespace}/$pod_id:$src";
    return $this->kubeCtlCopyCommand($src_string, $dst);
  }

  /**
   * Provides an interface to kubectl cp.
   *
   * @param $pod_id
   *   The pod ID to interact with.
   * @param $src
   *   The source filestring.
   * @param $dst
   *   The destination filestring.
   *
   * @see https://kubectl.docs.kubernetes.io/pages/container_debugging/copying_container_files.html
   * @return \Robo\Result
   * @throws \Dockworker\DockworkerException
   */
  private function kubeCtlCopyCommand($src, $dst) {
    $this->io()->note(self::INFO_COPYING_FILES);
    return $this->taskExec($this->kubeCtlBin)
      ->arg('cp')
      ->arg($src)
      ->arg($dst)
      ->run();
  }

}
