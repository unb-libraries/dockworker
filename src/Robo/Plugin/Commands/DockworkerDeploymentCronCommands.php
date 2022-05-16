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
   * @usage cron:logs:deployed dev
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
   * @usage cron:exec:deployed prod
   *
   * @kubectl
   */
  public function runDeploymentCronPod($env, array $options = ['no-write-logs' => FALSE]) {
    $this->deployedK8sResourceInit($this->repoRoot, $env);
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
   * @option $write-successful-logs
   *   Display logs even if no errors found.
   *
   * @command cron:exec-check:deployed
   * @throws \Exception
   *
   * @usage cron:exec-check:deployed prod
   *
   * @kubectl
   */
  public function runCheckDeploymentCronPod($env, array $options = ['write-successful-logs' => FALSE]) {
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
   * @command cron:logs:check:deployed
   * @throws \Exception
   *
   * @usage cron:logs:check:deployed prod
   *
   * @kubectl
   */
  public function checkDeploymentCronLogs($env) {
    $this->k8sInitSetupPods($env, 'cronjob', 'Cron Log Check');
    $this->getCustomLogTriggersExceptions('cronjob');
    $this->kubernetesCheckLogsFromCurrentPods();
  }

  /**
   * Runs a deployment's cron pod and returns the logs.
   *
   * @param string $env
   *   The environment to run the cron pod in.
   *
   * @return string
   *   The logs from the cron run.
   */
  protected function getRunDeploymentCronPodLogs($env) {
    $delete_job_cmd = sprintf(
      $this->kubeCtlBin . ' delete job/manual-dockworker-cron-%s --ignore-not-found=true --namespace=%s',
      $this->deployedK8sResourceName,
      $this->deployedK8sResourceNameSpace
    );
    $this->say($delete_job_cmd);
    shell_exec($delete_job_cmd);

    $create_job_cmd = sprintf(
      $this->kubeCtlBin . ' create job --from=cronjob/cron-%s manual-dockworker-cron-%s --namespace=%s',
      $this->deployedK8sResourceName,
      $this->deployedK8sResourceName,
      $this->deployedK8sResourceNameSpace
    );
    $this->say($create_job_cmd);
    shell_exec($create_job_cmd);

    $wait_job_cmd = sprintf(
      $this->kubeCtlBin . ' wait --for=condition=complete job/manual-dockworker-cron-%s --namespace=%s',
      $this->deployedK8sResourceName,
      $this->deployedK8sResourceNameSpace
    );
    $this->say($wait_job_cmd);
    shell_exec($wait_job_cmd);

    $get_logs_cmd = sprintf(
      $this->kubeCtlBin . ' logs job/manual-dockworker-cron-%s --namespace=%s',
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
