<?php

namespace Dockworker;

/**
 * Provides methods to interact with GitHub Actions.
 */
trait GitHubActionsTrait {

  use GitHubTrait;

  /**
   * The current action's workflow.
   *
   * @var array
   */
  protected $gitHubActionsCurWorkflow;

  /**
   * The current action's workflow runs.
   *
   * @var array
   */
  protected $gitHubActionsCurWorkflowRuns;

  /**
   * Sets up the latest GitHub Actions workflow run.
   *
   * @hook post-init @ci
   */
  public function setGitHubActionsWorkflow() : void {
    $key = '';
    $this->say("Querying CI Services Workflow run data for $this->gitHubRepo...");
    $workflows = $this->gitHubClient->api('repo')->workflows()
      ->all($this->gitHubOwner, $this->gitHubRepo);
    foreach ($workflows['workflows'] as $key => $value) {
      if ($value['name'] == $this->gitHubRepo) break;
    }
    $this->gitHubActionsCurWorkflow = $workflows['workflows'][$key];
    $this->setGitHubActionsWorkflowRuns();
  }

  /**
   * Restarts a GitHub Actions workflow run.
   */
  protected function setRestartGitHubActionsWorkflowRun($id) : void {
    $this->say(
      sprintf(
        'Restarting %s/%s Build #%s...',
        $this->gitHubOwner,
        $this->gitHubRepo,
        $id
      )
    );
    $this->gitHubClient->api('repo')->workflowRuns()
      ->rerun($this->gitHubOwner, $this->gitHubRepo, $id);
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
   * Sets the GitHub Actions workflow runs.
   */
  protected function setGitHubActionsWorkflowRuns() : void {
    $run = $this->gitHubClient->api('repo')->workflowRuns()
      ->listRuns(
        $this->gitHubOwner,
        $this->gitHubRepo,
        $this->gitHubActionsCurWorkflow['id']
      );
    $this->gitHubActionsCurWorkflowRuns = $run['workflow_runs'];
  }

  /**
   * Gets the latest GitHub Actions workflow run for a branch.
   *
   * @param $branch
   *   The branch to filter with.
   *
   * @return array
   *  The latest workflow run for the branch.
   */
  protected function getGitHubActionsWorkflowLatestRunByBranch($branch) : array {
    $runs = $this->getGitHubActionsRunsByBranch($branch);
    if (!empty($runs[0])) {
      return $runs[0];
    }
    return [];
  }

  /**
   * Gets a GitHub Actions workflow run by ID.
   *
   * @param $id
   *   The id to filter with.
   *
   * @return array
   *  The latest workflow run for the branch.
   */
  protected function getGitHubActionsWorkflowRunById($id) : array {
    foreach($this->gitHubActionsCurWorkflowRuns as $run) {
      if ($run['id'] == $id) {
        return $run;
      }
    }
    return [];
  }

  /**
   * Gets latest GitHub Actions workflow runs for a branch.
   *
   * @param $branch
   *   The branch to filter with.
   *
   * @return array
   *   An array of workflow runs for that branch.
   */
  protected function getGitHubActionsRunsByBranch($branch) : array {
    $branch_runs = [];
    foreach($this->gitHubActionsCurWorkflowRuns as $run) {
      if ($run['head_branch'] == $branch) {
        $branch_runs[] = $run;
      }
    }
    return $branch_runs;
  }

  /**
   * Initializes the trait for interacting with GitHub Actions.
   */
  protected function initSetupGitHubActionsTrait() : void {
    $this->setGitHubActionsWorkflow();
  }

}
