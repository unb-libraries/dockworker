<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\KubernetesDeploymentTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerBaseCIServicesWorkflowCommands;

/**
 * Defines a class to write a generic CI workflow file to a repository.
 */
class DockworkerCIServicesWorkflowCommands extends DockworkerBaseCIServicesWorkflowCommands {

  /**
   * Defines which CI Services workflow files exist for this application.
   *
   * @return string[]
   *   An associative array of workflow files supporting this application.
   */
  protected function getCiServicesWorkflowFileDefinitions() : array {
    return [
      [
        'name' => 'Generic build and push workflow',
        'file_name' => 'deployment-workflow.yaml',
        'source_path' => 'vendor/unb-libraries/dockworker/data/gh-actions',
        'repo_path' => '.github/workflows',
      ]
    ];
  }

}
