<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Robo\Plugin\Commands\DockworkerCommands;

/**
 * Defines the commands used to correct permissions in the repository tree.
 */
class ApplicationPermissionsCommands extends DockworkerCommands {

  /**
   * Sets the correct repository file permissions. Requires sudo.
   *
   * @option string $path
   *   Only update permissions in this path.
   *
   * @command dockworker:permissions:fix
   * @aliases pfix
   *
   * @usage dockworker:permissions:fix
   */
  public function fixPermissions($options = ['path' => NULL]) {
  }

  /**
   * Sets file permissions for a path to the current group. Requires sudo.
   *
   * @param string $path
   *   The path to change the group for.
   *
   * @usage "1 /mnt/issues/archive"
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
