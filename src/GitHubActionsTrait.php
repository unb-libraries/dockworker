<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Dockworker\GitHubTrait;

/**
 * Provides methods to interact with GitHub actions.
 */
trait GitHubActionsTrait {

  use GitHubTrait;

  /**
   * The current actions workflow.
   *
   * @var array
   */
  protected array $gitHubActionsCurWorkflow;

  /**
   * The current actions workflow's runs.
   *
   * @var array
   */
  protected array $gitHubActionsCurWorkflowRuns;

  /**
   * Sets the GitHub Actions Workflow.
   *
   * @hook post-init @github-actions
   * @throws \Dockworker\DockworkerException
   */
  public function setGitHubActionsWorkflow() {
    $this->say("Querying GitHub Actions Workflow run data for $this->gitHubRepo...");
    $workflows = $this->gitHubClient->api('repo')->workflows()->all($this->gitHubOwner, $this->gitHubRepo);
    foreach ($workflows['workflows'] as $key => $value) {
      if ($value['name'] == $this->gitHubRepo) break;
    }
    $this->gitHubActionsCurWorkflow = $workflows['workflows'][$key];
    $this->setGitHubActionsWorkflowRuns();
  }

  /**
   * Sets the current actions workflow runs.
   */
  protected function setGitHubActionsWorkflowRuns() {
    $run = $this->gitHubClient->api('repo')->workflowRuns()->listRuns($this->gitHubOwner, $this->gitHubRepo, $this->gitHubActionsCurWorkflow['id']);
    $this->gitHubActionsCurWorkflowRuns = $run['workflow_runs'];
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
  protected function getGitHubActionsWorkflowLatestRunByBranch($branch) {
    $runs = $this->getGitHubActionsWorkflowRunsByBranch($branch);
    if (!empty($runs[0])) {
      return $runs[0];
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
  protected function getGitHubActionsWorkflowRunsByBranch($branch) {
    $branch_runs = [];
    foreach($this->gitHubActionsCurWorkflowRuns as $run) {
      if ($run['head_branch'] == $branch) {
        $branch_runs[] = $run;
      }
    }
    return $branch_runs;
  }

}
