<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\KubernetesDeploymentTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerBaseCommands;
use Robo\Robo;

/**
 * Defines a class to write a standardized CI workflow file to a repository.
 */
class DockworkerBaseCIServicesWorkflowCommands extends DockworkerBaseCommands {

  use KubernetesDeploymentTrait;

  protected $CIServicesWorkflowFilepath;
  protected $CIServicesWorkflowSourcePath;

  /**
   * Writes standardized CI Services workflow files for this application to this repository.
   *
   * @command ci:workflow:file:write
   * @aliases update-ci-workflow
   * @aliases uciw
   */
  public function setApplicationCIServicesWorkflowFile() {
    $workflow_type = $this->getGitHubActionsWorkflowType();
    $workflow_source = $this->getGitHubActionsWorkflowSource();

    $this->CIServicesWorkflowSourcePath = $this->constructRepoPathString([
      "vendor/unb-libraries/$workflow_source/data/gh-actions",
      "$workflow_type.yaml",
    ]);

    $this->CIServicesWorkflowFilepath = $this->constructRepoPathString([
      '.github/workflows',
      'deployment-workflow.yaml',
    ]);
    $this->writeApplicationCIServicesWorkflowFile();
  }

  /**
   * Writes out the CI Services workflow file.
   */
  protected function writeApplicationCIServicesWorkflowFile() {
    // Set Name.
    $this->setInstanceName();
    $tokenized_workflow_contents = file_get_contents($this->CIServicesWorkflowSourcePath);
    $workflow_contents = str_replace('INSTANCE_NAME', $this->instanceName, $tokenized_workflow_contents);

    // Set Branches.
    $deploy_branches = $this->getDeployableEnvironments();
    $deploy_branches_string = '"' . implode('","', $deploy_branches) . '"';
    $workflow_contents = str_replace('INSTANCE_DEPLOY_BRANCHES', $deploy_branches_string, $workflow_contents);

    // Write File.
    file_put_contents($this->CIServicesWorkflowFilepath, $workflow_contents);
    $this->say('The updated GitHub actions workflow file has been written.');
  }

  /**
   * Gets the gh-actions deployment type for this application.
   */
  protected function getGitHubActionsWorkflowType() : string {
    $workflow_type = Robo::Config()->get('dockworker.deployment.workflow.type');
    if (empty($workflow_type)) {
      return 'deployment-workflow';
    }
    else {
      return $workflow_type;
    }
  }

  /**
   * Gets the gh-actions deployment source package for this application.
   */
  protected function getGitHubActionsWorkflowSource() : string {
    $workflow_source = Robo::Config()->get('dockworker.deployment.workflow.source');
    if (empty($workflow_source)) {
      return 'dockworker';
    }
    else {
      return $workflow_source;
    }
  }

}
