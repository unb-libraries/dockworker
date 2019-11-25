<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Dockworker\KubectlTrait;

/**
 * Provides methods to execute commands inside Kubernetes Pods.
 */
trait KubernetesPodTrait {

  use KubectlTrait;

  /**
   * The pod queue to execute commands in.
   *
   * @var string[]
   */
  protected $kubernetesCurPods = [];

  /**
   * The deployment name to use when populating the pod queue.
   *
   * @var string
   */
  protected $kubernetesDeploymentName = NULL;

  /**
   * The namespace to filter when populating the pod queue.
   *
   * @var string
   */
  protected $kubernetesPodNamespace = NULL;

  /**
   * Sets up the pod details for the remote Kubernetes pods.
   *
   * @param string $deployment_name
   *   The deployment name.
   * @param string $action
   *   A description of the actions intended to take (Used for labels).
   *
   * @throws \Exception
   */
  protected function kubernetesSetupPods($deployment_name, $action) {
    $this->kubernetesDeploymentName = $deployment_name;

    if (empty($this->kubernetesPodNamespace)) {
      $this->kubernetesPodNamespace = $this->askDefault("Environment to target for $action? (dev/prod)", 'prod');
    }

    $this->kubernetesSetMatchingPods();
  }

  /**
   * Populates the pod queue.
   *
   * @throws \Dockworker\DockworkerException
   * @throws \Exception
   */
  private function kubernetesSetMatchingPods() {
    $get_pods_cmd = sprintf(
      $this->kubeCtlBin . " get pods --namespace=%s --sort-by=.status.startTime --no-headers | grep %s | grep 'Running' | tac | awk '{ print $1 }'",
      $this->kubernetesPodNamespace,
      $this->kubernetesDeploymentName
    );

    $pod_list = trim(
      shell_exec($get_pods_cmd)
    );

    $pods = explode(PHP_EOL, $pod_list);
    foreach ($pods as $pod) {
      $this->kubernetesCheckPodShellAccess($pod);
      $this->kubernetesCurPods[] = $pod;
    }

    if (empty($this->kubernetesCurPods)) {
      throw new DockworkerException("Could not find any pods for {$this->kubernetesDeploymentName}:{$this->kubernetesPodNamespace}.");
    }
  }

  /**
   * Checks to see if a Kubernetes pod has shell access.
   *
   * @param string $pod
   *   The pod name to check.
   *
   * @throws \Exception
   */
  private function kubernetesCheckPodShellAccess($pod) {
    $this->kubernetesPodExecCommand(
      $pod,
      $this->kubernetesPodNamespace,
      'ls'
    );
  }

  /**
   * Executes a command on a remote Kubernetes pod.
   *
   * @param string $pod
   *   The pod name to check.
   * @param string $namespace
   *   The namespace to target the pod in.
   * @param string $command
   *   The command to execute.
   * @param bool $except_on_error
   *   TRUE to throw an exception on error. FALSE otherwise.
   *
   * @throws \Dockworker\DockworkerException
   *
   * @return string
   *   The STDOUT output from the command.
   */
  protected function kubernetesPodExecCommand($pod, $namespace, $command, $except_on_error = TRUE) {
    exec(
      sprintf($this->kubeCtlBin . " exec %s --namespace=%s -- sh -c '%s'",
        $pod,
        $namespace,
        $command
      ),
      $cmd_output,
      $return_code
    );
    if ($return_code != 0 && $except_on_error) {
      throw new DockworkerException("Pod command [$command] returned error code $return_code : $cmd_output.");
    }
    return $cmd_output;
  }

  /**
   * Copies a file between a Kubernetes pod and the local filesystem.
   *
   * @param string $namespace
   *   The namespace to target the pod in.
   * @param string $source_path
   *   The source path of the file to copy.
   * @param string $target_path
   *   The target path of the file to copy.
   * @param bool $except_on_error
   *   TRUE to throw an exception on error. FALSE otherwise.
   *
   * @throws \Dockworker\DockworkerException
   *
   * @return string
   *   The STDOUT output from the command.
   */
  protected function kubernetesPodFileCopyCommand($namespace, $source_path, $target_path, $except_on_error = TRUE) {
    exec(
      sprintf($this->kubeCtlBin . " cp %s %s --namespace=%s",
        $source_path,
        $target_path,
        $namespace
      ),
      $cmd_output,
      $return_code
    );
    if ($return_code != 0 && $except_on_error) {
      throw new DockworkerException("Pod copy [$source_path -> $target_path] returned error code $return_code : $cmd_output.");
    }
    return $cmd_output;
  }

}
