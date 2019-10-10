<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Robo\Plugin\Commands\DockworkerCommands;

/**
 * Defines the commands used to correct permissions in the repository tree.
 */
class ApplicationPermissionsCommands extends DockworkerCommands {

  /**
   * Sets the repository file permissions to expected. Requires sudo.
   *
   * @command permissions:fix
   * @aliases pfix
   */
  public function fixPermissions() {
  }

  /**
   * Sets file permissions for a path to the current group. Requires sudo.
   *
   * @param string $path
   *   The path to change the group for.
   */
  protected function setPermissions($path) {
    $gid = posix_getgid();

    $this->taskExec('sudo chgrp')
      ->arg($gid)
      ->arg('-R')
      ->arg($this->repoRoot . "/$path")
      ->run();
    $this->taskExec('sudo chmod')
      ->arg('g+w')
      ->arg('-R')
      ->arg($this->repoRoot . "/$path")
      ->run();
  }

}
