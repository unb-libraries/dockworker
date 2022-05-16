<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Robo\Plugin\Commands\DockworkerCommands;

/**
 * Defines the commands used to correct permissions in the repository tree.
 */
class ApplicationPermissionsCommands extends DockworkerCommands {

  /**
   * Sets proper file permissions for this repository (uses sudo).
   *
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $path
   *   Only update permissions in this path.
   *
   * @command dockworker:permissions:fix
   * @aliases pfix
   *
   * @usage dockworker:permissions:fix
   */
  public function fixPermissions(array $options = ['path' => NULL]) {
  }

  /**
   * Sets file permissions for a path to the current group. Requires sudo.
   *
   * @param string $path
   *   The path to change the group for.
   *
   * @usage "1 /mnt/issues/archive"
   */
  protected function setPermissions($path) : void {
    $full_path = $this->repoRoot . "/$path";

    if (file_exists($full_path)) {
      $gid = posix_getgid();
      $this->taskExec('sudo chgrp')
        ->arg('-R')
        ->arg($gid)
        ->arg($full_path)
        ->run();
      $this->taskExec('sudo chmod')
        ->arg('g+w')
        ->arg('-R')
        ->arg($full_path)
        ->run();
    }
    else {
      $this->io()->note("Path $full_path not found, skipping.");
    }

  }

}
