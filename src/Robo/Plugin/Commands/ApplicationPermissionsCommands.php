<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Robo\Plugin\Commands\DockworkerCommands;

/**
 * Defines commands for fixing permissions for an application.
 */
class ApplicationPermissionsCommands extends DockworkerCommands {

  /**
   * Fix repository file permissions. Requires sudo.
   *
   * @command permissions:fix
   * @aliases pfix
   */
  public function fixPermissions() {
  }

  /**
   * Set file permissions for a path to the current user. Requires sudo.
   */
  protected function setPermissions($path) {
    $uid = posix_getuid();
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
