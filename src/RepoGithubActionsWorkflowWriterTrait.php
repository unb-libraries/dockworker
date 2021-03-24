<?php

namespace Dockworker;

use Dockworker\DockworkerException;

/**
 * Provides methods to manipulate a repository github actions workflow.
 */
trait RepoGithubActionsWorkflowWriterTrait {

  protected $githubActionsWorkflowFilepath = NULL;
  protected $githubActionsWorkflowSourcePath = NULL;

  /**
   * Sets the workflow file location.
   *
   * @hook post-init @actionsworkflowcommand
   * @throws \Dockworker\DockworkerException
   */
  public function initActionsWorkflowCommand() {
    $this->githubActionsWorkflowFilepath = $this->repoRoot . '/.github/workflows/test-suite.yaml';
    $this->githubActionsWorkflowSourcePath = $this->repoRoot . '/vendor/unb-libraries/dockworker/data/gh-actions/test-suite.yaml';
  }

}
