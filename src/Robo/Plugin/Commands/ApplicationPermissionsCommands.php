<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Robo\Plugin\Commands\DockworkerBaseCommands;

/**
 * Defines the commands used to correct permissions in the repository tree.
 */
class ApplicationPermissionsCommands extends DockworkerBaseCommands {

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
   */
  public function fixPermissions(array $options = ['path' => '']) : void {
  }

  /**
   * Sets path file ownership to current user's primary group. Requires sudo.
   *
   * @param string $path
   *   The path to change the group for.
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
