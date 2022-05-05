<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\KubernetesDeploymentTrait;
use Dockworker\RepoCIServicesWorkflowWriterTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;

/**
 * Defines a class to write a standardized build file to a repository.
 */
class DockworkerCIServicesWorkflowCommands extends DockworkerCommands {

  use RepoCIServicesWorkflowWriterTrait;
  use KubernetesDeploymentTrait;

  /**
   * Writes a standardized CI Services workflow file for this application to its repository.
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
    $this->CIServicesWorkflowSourcePath = $this->repoRoot . '/vendor/unb-libraries/dockworker/data/gh-actions/deployment-workflow.yaml';
    $this->writeApplicationCIServicesWorkflowFile();
  }

  /**
   * Write out the workflow file.
   */
  protected function writeApplicationCIServicesWorkflowFile() {
    $this->setInstanceName();
    $tokenized_workflow_contents = file_get_contents($this->CIServicesWorkflowSourcePath);
    $workflow_contents = str_replace('INSTANCE_NAME', $this->instanceName, $tokenized_workflow_contents);
    $deployable_env_string = '';
    foreach ($this->getDeployableEnvironments() as $deploy_env) {
      $deployable_env_string .= "      refs/heads/$deploy_env\n";
    }
    $workflow_contents = str_replace('DEPLOY_BRANCHES', rtrim($deployable_env_string), $workflow_contents);
    file_put_contents($this->CIServicesWorkflowFilepath, $workflow_contents);
    $this->say('The updated GitHub actions workflow file has been written.');
  }

}
