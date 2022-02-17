<?php

namespace Dockworker;

use Dockworker\DockworkerException;

/**
 * Provides methods to manipulate a repository CI Services workflow.
 */
trait RepoCIServicesWorkflowWriterTrait {

  protected $CIServicesWorkflowFilepath;
  protected $CIServicesWorkflowSourcePath;

  /**
   * Sets the workflow file location.
   *
   * @hook post-init @actionsworkflowcommand
   * @throws \Dockworker\DockworkerException
   */
  public function initActionsWorkflowCommand() {
    $this->CIServicesWorkflowFilepath = $this->repoRoot . '/.github/workflows/deployment-workflow.yaml';
    $this->CIServicesWorkflowSourcePath = $this->repoRoot . '/vendor/unb-libraries/dockworker/data/gh-actions/deployment-workflow.yaml';
  }

}
