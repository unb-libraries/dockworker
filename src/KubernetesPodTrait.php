<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Dockworker\DockworkerLogCheckerTrait;
use Dockworker\KubectlTrait;

/**
 * Provides methods to execute commands inside Kubernetes Pods.
 */
trait KubernetesPodTrait {

  use DockworkerLogCheckerTrait;
  use KubectlTrait;

  /**
   * The pod queue to execute commands in.
   *
   * @var string[]
   */
  protected $kubernetesCurPods = [];

  /**
   * The replica sets.
   *
   * @var string[]
   */
  protected $kubernetesCurReplicaSets = [];

  /**
   * The latest replica set.
   *
   * @var string[]
   */
  protected $kubernetesLatestReplicaSet;

  /**
   * The entity name to use when populating the pod queue.
   *
   * @var string
   */
  protected $kubernetesPodParentResourceName;

  /**
   * The entity type to use when populating the pod queue.
   *
   * @var string
   */
  protected $kubernetesPodParentResourceType;

  /**
   * The deployment name currently active.
   *
   * @var string
   */
  protected $kubernetesCurDeployment;

  /**
   * The k8s resource name currently active.
   *
   * @var string
   */
  protected $kubernetesCurResource;

  /**
   * The namespace to filter when populating the pod queue.
   *
   * @var string
   */
  protected $kubernetesPodParentResourceNamespace;

  /**
   * Sets up the pod details for the remote Kubernetes pods.
   *
   * @param string $entity_name
   *   The resource name.
   * @param string $resource_type
   *   The type of resource being set up.
   * @param string $namespace
   *   The namespace of the resource.
   * @param string $action
   *   A description of the actions intended to take (Used for labels).
   * @param bool $quiet
   *   TRUE if all io should be suppressed.
   *
   * @throws \Exception
   */
  protected function kubernetesSetupPods($entity_name, $resource_type, $namespace, $action, $quiet = FALSE) {
    $this->kubernetesCurPods = [];
    $this->kubernetesPodParentResourceName = $entity_name;
    $this->kubernetesPodParentResourceType = $resource_type;
    $this->kubernetesPodParentResourceNamespace = $namespace;
    $this->kubernetesSetPods($quiet);
    $this->kubernetesThrowExceptionIfNoPods();
  }

  /**
   * Throws an exception if the current list of k8s pods is currently empty.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function kubernetesThrowExceptionIfNoPods() {
    if (empty($this->kubernetesCurPods)) {
      throw new DockworkerException(
        sprintf(
          "Could not find any pods for %s/%s:%s.",
          $this->deployedK8sResourceType,
          $this->deployedK8sResourceName,
          $this->deployedK8sResourceNameSpace
        )
      );
    }
  }

  /**
   * Removes all pods except the latest from the list.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function kubernetesFilterPodsOnlyLatest() {
    $this->kubernetesThrowExceptionIfNoPods();
    $this->kubernetesCurPods = array_slice($this->kubernetesCurPods, 0, 1);
  }

  /**
   * Gets the name of the latest pod selected.
   *
   * @return string
   *   The ID of the latest pod in the configuration.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function kubernetesGetLatestPod() {
    return $this->kubernetesCurPods[0];
  }

  /**
   * Displays logs from all currently selected pods.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function kubernetesPrintLogsFromCurrentPods() {
    $logs = $this->kubernetesGetLogsFromCurrentPods();
    if (!empty($logs)) {
      $pod_counter = 0;
      foreach ($logs as $pod_id => $log) {
        $pod_counter++;
        $this->io()->title("Logs for pod #$pod_counter [$this->kubernetesPodParentResourceNamespace.$pod_id]");
        $this->io()->writeln($log);
      }
    }
    else {
      $this->io()->title("No cron pods found. No logs!");
    }
  }

  /**
   * Displays logs from all currently selected pods.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function kubernetesGetPrintLogsFromCurrentPods() {
    $logs = $this->kubernetesGetLogsFromCurrentPods();
    $this->kubernetesPrintPodLogs($logs);
  }

  protected function kubernetesPrintPodLogs($logs) {
    if (!empty($logs)) {
      $pod_counter = 0;
      foreach ($logs as $pod_id => $log) {
        $pod_counter++;
        $this->io()->title("Logs for pod #$pod_counter [$this->kubernetesPodParentResourceNamespace.$pod_id]");
        $this->io()->writeln($log);
      }
    }
    else {
      $this->io()->title("No cron pods found. No logs!");
    }
  }

  /**
   * Displays logs from all currently selected pods.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function kubernetesCheckLogsFromCurrentPods() {
    $logs = $this->kubernetesGetLogsFromCurrentPods();

    if (!empty($logs)) {
      foreach ($logs as $pod_id => $log) {
        $this->checkLogForErrors($pod_id, $log);
      }
    }
    else {
      $this->io()->title("No pods found. No logs!");
    }

    try {
      $this->auditApplicationLogs(FALSE);
      $this->say("No errors found in logs.");
    }
    catch (DockworkerException) {
      $this->kubernetesPrintPodLogs($logs);
      $this->printApplicationLogErrors();
      throw new DockworkerException("Error(s) found in k8s resource pod logs!");
    }

    $this->say(sprintf("No errors found in %s pods.", count($logs)));
  }

  /**
   * Removes all pods except the latest from the list.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function kubernetesGetLogsFromCurrentPods() {
    $logs = [];
    foreach ($this->kubernetesCurPods as $pod_id) {
      $logs[$pod_id] = $this->getKubernetesPodLogs($pod_id);
    }
    return $logs;
  }

  /**
   * Gets a pod's logs.
   *
   * @param string $env
   *   The environment to check.
   *
   * @throws \Exception
   *
   * @return string[]
   *   An array of logs, keyed by pod IDs.
   */
  protected function getKubernetesPodLogs($pod_id) {
    return $this->kubectlExec(
      'logs',
      [
        $pod_id,
        '--namespace',
        $this->deployedK8sResourceNameSpace,
      ],
      FALSE
    );
  }

  /**
   * Populates the pod queue.
   *
   * @throws \Dockworker\DockworkerException
   * @throws \Exception
   */
  private function kubernetesSetPods($quiet = FALSE) {
    $this->kubernetesSetMatchingResource();
    switch ($this->kubernetesPodParentResourceType) {
      case 'deployment':
        $this->kubernetesSetMatchingDeploymentReplicaSets();
        $this->kubernetesSetMatchingDeploymentPods();
        break;
      case 'cronjob':
        $this->kubernetesSetMatchingCronPods();
        break;
      default:
        break;
    }
    $num_pods = count($this->kubernetesCurPods);
    if (!$quiet) {
      $this->io()->title("[$this->kubernetesPodParentResourceType/$this->kubernetesPodParentResourceName:$this->kubernetesPodParentResourceNamespace] $num_pods pods found.");
    }
  }

  /**
   * Set up the resource that matches the currently configured data.
   */
  protected function kubernetesSetMatchingResource() {
    $get_deployments_cmd = sprintf(
      $this->kubeCtlBin . " --kubeconfig $this->kubeCtlConf" . ' get %s/%s --namespace=%s --sort-by=.status.startTime --no-headers | awk \'{ print $1 }\'',
      $this->kubernetesPodParentResourceType,
      $this->kubernetesPodParentResourceName,
      $this->kubernetesPodParentResourceNamespace
    );

    $this->kubernetesCurResource = trim(
      shell_exec($get_deployments_cmd)
    );
  }

  /**
   * Set up replica sets that match the currently configured data.
   */
  protected function kubernetesSetMatchingDeploymentReplicaSets() {
    $this->kubernetesCurReplicaSets = [];
    $get_rs_cmd = sprintf(
      $this->kubeCtlBin . " --kubeconfig $this->kubeCtlConf" . " describe deployment/%s --namespace=%s | grep 'ReplicaSet.*:' | awk '{ print $2 }'",
      $this->kubernetesCurResource,
      $this->kubernetesPodParentResourceNamespace
    );

    $rs_list = explode(
      PHP_EOL,
      trim(
        shell_exec($get_rs_cmd)
      )
    );
    foreach($rs_list as $rs) {
      if ($rs != '<none>') {
        $this->kubernetesCurReplicaSets[] = $rs;
      }
    }
    $this->kubernetesLatestReplicaSet = end($this->kubernetesCurReplicaSets);
  }


  /**
   * @return void
   */
  protected function kubernetesSetMatchingCronPods(): void {
    $this->kubernetesSetPodsFromKubeCtlCmd(
      sprintf(
        $this->kubeCtlBin . " --kubeconfig $this->kubeCtlConf" . " get pods --namespace=%s --sort-by=.status.startTime --no-headers | grep '^%s' | grep 'Completed\|Error' | sed '1!G;h;$!d' | awk '{ print $1 }'",
        $this->kubernetesPodParentResourceNamespace,
        $this->kubernetesPodParentResourceName
      )
    );
  }

  /**
   * @return void
   */
  protected function kubernetesSetMatchingDeploymentPods(): void {
    $this->kubernetesSetPodsFromKubeCtlCmd(
      sprintf(
        $this->kubeCtlBin . " --kubeconfig $this->kubeCtlConf" . " get pods --namespace=%s -o json | jq -r '.items[] | select(.metadata.ownerReferences[] | select(.name==\"%s\")) | .metadata.name '",
        $this->kubernetesPodParentResourceNamespace,
        $this->kubernetesLatestReplicaSet
      )
    );
  }

  /**
   * @param $cmd_string
   *
   * @return void
   */
  protected function kubernetesSetPodsFromKubeCtlCmd($cmd_string) {
    $this->kubernetesCurPods = [];
    $pod_list = trim(
      shell_exec($cmd_string)
    );

    if (!empty($pod_list)) {
      $this->kubernetesCurPods = explode(PHP_EOL, $pod_list);
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
      $this->kubernetesPodParentResourceNamespace,
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
   * @return string[]
   *   The STDOUT output from the command, each line as an array element.
   */
  protected function kubernetesPodExecCommand($pod, $namespace, $command, $except_on_error = TRUE) {
    exec(
      sprintf($this->kubeCtlBin . " --kubeconfig $this->kubeCtlConf" . " exec %s --namespace=%s -- sh -c '%s'",
        $pod,
        $namespace,
        $command
      ),
      $cmd_output,
      $return_code
    );
    $output_string = implode("\n", $cmd_output);
    if ($return_code != 0 && $except_on_error) {
      throw new DockworkerException("Pod command [$command] returned error code $return_code : $output_string.");
    }
    return $cmd_output;
  }

  /**
   * Copies files from a remote kubernetes pod.
   *
   * @param string $pod
   *   The pod name to check.
   * @param string $namespace
   *   The namespace to target the pod in.
   * @param string $pod_path
   *   The filepath inside the pod.
   * @param string $local_path
   *   The filepath target locally.
   * @param bool $except_on_error
   *   TRUE to throw an exception on error. FALSE otherwise.
   *
   * @throws \Dockworker\DockworkerException
   *
   * @return string
   *   The STDOUT output from the command.
   */
  protected function kubernetesCopyFromPodCommand($pod, $namespace, $pod_path, $local_path, $except_on_error = TRUE) {
    exec(
      sprintf($this->kubeCtlBin . " --kubeconfig $this->kubeCtlConf" . " cp %s/%s:%s %s",
        $namespace,
        $pod,
        $pod_path,
        $local_path
      ),
      $cp_output,
      $return_code
    );
    $output_string = implode("\n", $cp_output);
    if ($return_code != 0 && $except_on_error) {
      throw new DockworkerException("Pod cp [$pod_path/$local_path] returned error code $return_code : $output_string.");
    }
    return $cp_output;
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
      sprintf($this->kubeCtlBin . " --kubeconfig $this->kubeCtlConf" . " cp %s %s --namespace=%s",
        $source_path,
        $target_path,
        $namespace
      ),
      $cmd_output,
      $return_code
    );
    $output_string = implode("\n", $cmd_output);
    if ($return_code != 0 && $except_on_error) {
      throw new DockworkerException("Pod copy [$source_path -> $target_path] returned error code $return_code : $output_string.");
    }
    return $cmd_output;
  }

}
