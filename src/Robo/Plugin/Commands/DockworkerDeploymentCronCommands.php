<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerException;
use Dockworker\Robo\Plugin\Commands\DockworkerDeploymentCommands;

/**
 * Defines the commands used to interact with Kubernetes cronjob resources.
 */
class DockworkerDeploymentCronCommands extends DockworkerDeploymentCommands {

  /**
   * Retrieves this application's k8s deployment cron logs.
   *
   * @param string $env
   *   The environment to obtain the logs from.
   *
   * @option $all
   *   Display logs from all cron pods, not only the latest.
   *
   * @command cron:logs:deployed
   * @throws \Exception
   *
   * @usage dev
   *
   * @kubectl
   */
  public function getDeployedCronLogs($env, array $options = ['all' => FALSE]) {
    $this->k8sInitSetupPods($env, 'cronjob', 'Cron Logs');
    if (!$options['all']) {
      $this->kubernetesFilterPodsOnlyLatest();
    }
    $this->kubernetesPrintLogsFromCurrentPods();
  }


  /**
   * Executes this application's k8s deployment cron.
   *
   * @param string $env
   *   The environment to execute the cron in.
   *
   * @option $no-write-logs
   *   Do not display logs after execution.
   *
   * @command cron:exec:deployed
   * @throws \Exception
   *
   * @usage prod
   *
   * @kubectl
   */
  public function runDeploymentCronPod($env, array $options = ['no-write-logs' => FALSE]) {
    $this->deployedK8sResourceInit($this->repoRoot, $env, 'cronjob');
    $logs = $this->getRunDeploymentCronPodLogs($env);
    if (!$options['no-write-logs']) {
      $this->io()->block($logs);
    }
  }

  /**
   * Executes this application's k8s deployment cron, and determines if its logs contain errors.
   *
   * @param string $env
   *   The environment to execute the cron in.
   *
   * @option $timeout
   *   The amount of time to allow cron to run before failing.
   * @option $write-successful-logs
   *   Display logs even if no errors found.
   *
   * @command cron:exec-check:deployed
   * @throws \Exception
   *
   * @usage prod
   *
   * @kubectl
   */
  public function runCheckDeploymentCronPod($env, array $options = ['timeout' => '300', 'write-successful-logs' => FALSE]) {
    $this->deployedK8sResourceInit($this->repoRoot, $env);
    $logs = $this->getRunDeploymentCronPodLogs($env);
    $this->checkLogForErrors('Manual Cron Pod', $logs);
    try {
      $this->auditApplicationLogs(FALSE);
      $this->say("No errors found in logs.");
    }
    catch (DockworkerException) {
      $this->kubernetesPrintPodLogs($logs);
      $this->printApplicationLogErrors();
      throw new DockworkerException("Error(s) found in deployment cron logs!");
    }
  }

  /**
   * Determines if this application's k8s deployment cron logs contain errors.
   *
   * @param string $env
   *   The environment to check the logs in.
   *
   * @option $all
   *   Check logs from all cron pods, not only the latest.
   * @option $timeout
   *   The amount of time to allow cron to run before failing.
   *
   * @command cron:logs:check:deployed
   * @throws \Exception
   *
   * @usage prod
   *
   * @kubectl
   */
  public function checkDeploymentCronLogs($env, array $options = ['all' => FALSE, 'timeout' => '300']) {
    $this->k8sInitSetupPods($env, 'cronjob', 'Cron Log Check');
    if (!$options['all']) {
      $this->kubernetesFilterPodsOnlyLatest();
    }
    $this->getCustomLogTriggersExceptions('cronjob');
    $this->kubernetesCheckLogsFromCurrentPods();
  }

  /**
   * Runs a deployment's cron pod and returns the logs.
   *
   * @param string $env
   *   The environment to run the cron pod in.
   * @param integer $timeout
   *   The amount of time to wait for the cron job to complete before erroring.
   *
   * @return string
   *   The logs from the cron run.
   */
  protected function getRunDeploymentCronPodLogs($env, $timeout = 300) {
    $delete_job_cmd = sprintf(
      $this->kubeCtlBin . " --kubeconfig $this->kubeCtlConf" . ' delete job/manual-dockworker-%s --ignore-not-found=true --namespace=%s',
      $this->deployedK8sResourceName,
      $this->deployedK8sResourceNameSpace
    );
    $this->say($delete_job_cmd);
    shell_exec($delete_job_cmd);

    $create_job_cmd = sprintf(
      $this->kubeCtlBin . " --kubeconfig $this->kubeCtlConf" . ' create job --from=cronjob/%s manual-dockworker-%s --namespace=%s',
      $this->deployedK8sResourceName,
      $this->deployedK8sResourceName,
      $this->deployedK8sResourceNameSpace
    );
    $this->say($create_job_cmd);
    shell_exec($create_job_cmd);

    $wait_job_cmd = sprintf(
      $this->kubeCtlBin . " --kubeconfig $this->kubeCtlConf" . " wait --for=condition=complete --timeout={$timeout}s job/manual-dockworker-%s --namespace=%s",
      $this->deployedK8sResourceName,
      $this->deployedK8sResourceNameSpace
    );
    $this->say($wait_job_cmd);
    shell_exec($wait_job_cmd);

    $get_logs_cmd = sprintf(
      $this->kubeCtlBin . " --kubeconfig $this->kubeCtlConf" . ' logs job/manual-dockworker-%s --namespace=%s',
      $this->deployedK8sResourceName,
      $this->deployedK8sResourceNameSpace
    );
    $this->say($get_logs_cmd);
    $logs = shell_exec($get_logs_cmd);

    $this->say($delete_job_cmd);
    shell_exec($delete_job_cmd);

    return $logs;
  }

}
