<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Dockworker\GitHubTrait;

/**
 * Provides methods to interact with CI Services.
 */
trait CIServicesTrait {

  use GitHubTrait;

  /**
   * The current actions workflow.
   *
   * @var array
   */
  protected $CIServicesCurWorkflow;

  /**
   * The current actions workflow's runs.
   *
   * @var array
   */
  protected $CIServicesCurWorkflowRuns;

  /**
   * Sets the CI Services Workflow Runs.
   *
   * @hook post-init @ci
   */
  public function setCIServicesWorkflow() {
    $key = NULL;
    $this->say("Querying CI Services Workflow run data for $this->gitHubRepo...");
    $workflows = $this->gitHubClient->api('repo')->workflows()->all($this->gitHubOwner, $this->gitHubRepo);
    foreach ($workflows['workflows'] as $key => $value) {
      if ($value['name'] == $this->gitHubRepo) break;
    }
    $this->CIServicesCurWorkflow = $workflows['workflows'][$key];
    $this->setCIServicesWorkflowRuns();
  }

  /**
   * Restarts a CI Service Build.
   */
  protected function setRestartCiServiceBuild($id) {
    $this->say(
      sprintf(
        'Restarting %s/%s Build #%s...',
        $this->gitHubOwner,
        $this->gitHubRepo,
        $id
      )
    );
    $this->gitHubClient->api('repo')->workflowRuns()->rerun($this->gitHubOwner, $this->gitHubRepo, $id);
    $this->say("Done!");
    $this->say(
      sprintf(
        'Build URI : https://github.com/%s/%s/actions/runs/%s',
        $this->gitHubOwner,
        $this->gitHubRepo,
        $id
      )
    );
  }

  /**
   * Sets the current actions workflow runs.
   */
  protected function setCIServicesWorkflowRuns() {
    $run = $this->gitHubClient->api('repo')->workflowRuns()->listRuns($this->gitHubOwner, $this->gitHubRepo, $this->CIServicesCurWorkflow['id']);
    $this->CIServicesCurWorkflowRuns = $run['workflow_runs'];
  }

  /**
   * Gets the latest workflow run for a branch.
   *
   * @param $branch
   *   The branch to filter with.
   *
   * @return array
   *  The latest workflow run for the branch.
   */
  protected function getCIServicesWorkflowLatestRunByBranch($branch) {
    $runs = $this->getCIServicesWorkflowRunsByBranch($branch);
    if (!empty($runs[0])) {
      return $runs[0];
    }
    return [];
  }

  /**
   * Gets a workflow run by ID.
   *
   * @param $id
   *   The id to filter with.
   *
   * @return array
   *  The latest workflow run for the branch.
   */
  protected function getCIServicesWorkflowRunById($id) {
    foreach($this->CIServicesCurWorkflowRuns as $run) {
      if ($run['id'] == $id) {
        return $run;
      }
    }
    return [];
  }

  /**
   * Gets all latest workflow runs for a branch.
   *
   * @param $branch
   *   The branch to filter with.
   *
   * @return array
   *   An array of workflow runs for that branch.
   */
  protected function getCIServicesWorkflowRunsByBranch($branch) {
    $branch_runs = [];
    foreach($this->CIServicesCurWorkflowRuns as $run) {
      if ($run['head_branch'] == $branch) {
        $branch_runs[] = $run;
      }
    }
    return $branch_runs;
  }

  protected function initSetupCIServicesTrait() {
    $this->setCIServicesWorkflow();
  }

}
