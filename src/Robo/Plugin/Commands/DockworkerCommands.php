<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerBaseCommands;

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
   * Sets up the required git hooks for dockworker.
   *
   * @command dockworker:git:setup-hooks
   */
  public function setupHooks() {
    $source_dir = $this->repoRoot . "/vendor/unb-libraries/dockworker/scripts/git-hooks";
    $target_dir = $this->repoRoot . "/.git/hooks";
    $this->_copy("$source_dir/commit-msg", "$target_dir/commit-msg");
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
