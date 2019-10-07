<?php

namespace Dockworker;

use Dockworker\KubectlTrait;

/**
 * Defines trait for executing commands inside Kubernetes Pods.
 */
trait KubernetesPodTrait {

  use KubectlTrait;

  protected $kubernetesPodInstanceName = NULL;
  protected $kubernetesPodNamespace = NULL;
  protected $kubernetesCurPods = [];

  /**
   * Setup the pod details for the remote Kubernetes pods.
   *
   * @param string $instance_name
   *   The instance name of the dockworker project.
   * @param string $action
   *   A description of the actions to take for output purposes.
   *
   * @throws \Exception
   */
  protected function kubernetesSetupPods($instance_name, $action) {
    $this->kubernetesPodInstanceName = $instance_name;

    if (empty($this->kubernetesPodNamespace)) {
      $this->kubernetesPodNamespace = $this->askDefault("Environment to target for $action? (dev/prod)", 'prod');
    }

    $this->kubernetesSetMatchingPods();
  }

  /**
   * Setup pods that match the current configuration.
   *
   * @throws \Exception
   */
  private function kubernetesSetMatchingPods() {
    $get_pods_cmd = sprintf(
      $this->kubeCtlBin . " get pods --namespace=%s --sort-by=.status.startTime -l instance=%s --no-headers | grep 'Running' | tac | awk '{ print $1 }'",
      $this->kubernetesPodNamespace,
      $this->kubernetesPodInstanceName
    );

    $pod_list = trim(
      shell_exec(sprintf($get_pods_cmd, $this->kubernetesPodNamespace, $this->kubernetesPodInstanceName))
    );

    $pods = explode(PHP_EOL, $pod_list);
    foreach ($pods as $pod) {
      $this->kubernetesCheckPodShellAccess($pod);
      $this->kubernetesCurPods[] = $pod;
    }

    if (empty($this->kubernetesCurPods)) {
      throw new \Exception("Could not find any pods for {$this->kubernetesPodInstanceName}:{$this->kubernetesPodNamespace}.");
    }
  }

  /**
   * Check to see if a Kubernetes pod has shell access.
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
   * Execute a command on a remote Kubernetes pod.
   *
   * @param string $pod
   *   The pod name to check.
   * @param $namespace
   *   The namespace to target the pod in.
   * @param $command
   *   The command to execute.
   * @param bool $except_on_error
   *   TRUE to throw an exception on error. FALSE otherwise.
   *
   * @return mixed
   * @throws \Exception
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
      throw new \Exception("Pod command [$command] returned error code $return_code : $cmd_output.");
    }
    return $cmd_output;
  }

  /**
   * Copy a file between a Kubernetes pod and the local filesystem.
   *
   * @param $namespace
   *   The namespace to target the pod in.
   * @param string $source_path
   *   The source path of the file to copy.
   * @param $target_path
   *   The target path of the file to copy.
   * @param bool $except_on_error
   *   TRUE to throw an exception on error. FALSE otherwise.
   *
   * @return mixed
   * @throws \Exception
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
      throw new \Exception("Pod copy [$source_path -> $target_path] returned error code $return_code : $cmd_output.");
    }
    return $cmd_output;
  }

}
