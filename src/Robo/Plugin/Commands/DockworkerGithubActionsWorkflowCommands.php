<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\RepoGithubActionsWorkflowWriterTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;

/**
 * Defines a class to write a standardized build file to a repository.
 */
class DockworkerGithubActionsWorkflowCommands extends DockworkerCommands {

  use RepoGithubActionsWorkflowWriterTrait;

  /**
   * Updates the application's GitHub actions workflow file.
   *
   * @command dockworker:gh-actions:update
   * @aliases update-gh-actions
   *
   * @usage dockworker:gh-actions:update
   *
   * @actionsworkflowcommand
   */
  public function setApplicationGithubActionsWorkflowFile() {
    $this->githubActionsWorkflowSourcePath = $this->repoRoot . '/vendor/unb-libraries/dockworker/data/gh-actions/test-suite.yaml';
    $this->writeApplicationGithubActionsWorkflowFile();
  }

  /**
   * Write out the workflow file.
   */
  protected function writeApplicationGithubActionsWorkflowFile() {
    $tokenized_workflow_contents = file_get_contents($this->githubActionsWorkflowSourcePath);
    $workflow_contents = str_replace('INSTANCE_NAME', $this->instanceName, $tokenized_workflow_contents);
    file_put_contents($this->githubActionsWorkflowFilepath, $workflow_contents);
    $this->say('The updated GitHub actions workflow file has been written.');
  }

}
