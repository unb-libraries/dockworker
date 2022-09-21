<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Robo\Plugin\Commands\DockworkerBaseCommands;
use Robo\Robo;
use Symfony\Component\Yaml\Yaml;

/**
 * Defines a base class for all Dockworker Robo commands.
 */
class DockworkerCommands extends DockworkerBaseCommands {

  /**
   * The shell of the current application.
   *
   * @var string
   */
  protected $applicationShell = '/bin/sh';

  /**
   * Sets the application shell used.
   *
   * @hook init
   */
  public function setApplicationShell() {
    $deployment_shell = Robo::Config()->get('dockworker.application.shell');
    if (!empty($deployment_shell)) {
      $this->applicationShell = $deployment_shell;
    }
  }

  /**
   * Determines the local primary service deployment port.
   *
   * @return string
   *   The local primary service deployment port.
   */
  protected function getLocalDeploymentPort() {
    $docker_compose = Yaml::parse(
      file_get_contents(
        $this->repoRoot . '/docker-compose.yml'
      )
    );
    foreach($docker_compose['services'][$this->instanceName]['ports'] as $portmap) {
      $values = explode(':', $portmap);
      return $values[0];
    }
  }

}
