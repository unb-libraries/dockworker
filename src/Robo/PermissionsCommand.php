<?php

namespace UnbLibraries\DockWorker\Robo;

use Robo\Robo;
use Robo\Tasks;
use UnbLibraries\DockWorker\Robo\DockWorkerCommand;

/**
 * Defines commands in the GitCommand namespace.
 */
class PermissionsCommand extends DockWorkerCommand {

  /**
   * Fix repository file permissions. Requires sudo.
   *
   * @command permissions:fix
   */
  public function fixPermissions() {
    $paths = [
      'custom',
      'config-yml',
      'tests',
    ];
    foreach ($paths as $path) {
      $this->setPermissions($path);
    }
  }

  /**
   * Set file permissions for a path to the current user. Requires sudo.
   */
  public function setPermissions($path) {
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
