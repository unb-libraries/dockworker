<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\RepoCIServicesWorkflowWriterTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;

/**
 * Defines a class to write a standardized build file to a repository.
 */
class DockworkerCIServicesWorkflowCommands extends DockworkerCommands {

  use RepoCIServicesWorkflowWriterTrait;

  /**
   * Updates the application's CI Services workflow file.
   *
   * @command ci:update-workflow-file
   * @aliases update-ci-workflow
   * @aliases uciw
   *
   * @usage ci:update-workflow-file
   *
   * @actionsworkflowcommand
   */
  public function setApplicationCIServicesWorkflowFile() {
    $this->CIServicesWorkflowSourcePath = $this->repoRoot . '/vendor/unb-libraries/dockworker/data/gh-actions/test-suite.yaml';
    $this->writeApplicationCIServicesWorkflowFile();
  }

  /**
   * Write out the workflow file.
   */
  protected function writeApplicationCIServicesWorkflowFile() {
    $tokenized_workflow_contents = file_get_contents($this->CIServicesWorkflowSourcePath);
    $workflow_contents = str_replace('INSTANCE_NAME', $this->instanceName, $tokenized_workflow_contents);
    file_put_contents($this->CIServicesWorkflowFilepath, $workflow_contents);
    $this->say('The updated GitHub actions workflow file has been written.');
  }

}
