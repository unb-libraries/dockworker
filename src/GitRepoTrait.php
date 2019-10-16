<?php

namespace Dockworker;

use Dockworker\DockworkerException;

/**
 * Provides methods to interact with a local git repo.
 */
trait GitRepoTrait {

  private function gitRepoIsClean($path) {
    $repo_status = $this->taskExec('git')
      ->dir($path)
      ->arg('status')
      ->arg('--short')
      ->silent(TRUE)
      ->printOutput(FALSE)
      ->run();

    if (!empty($repo_status->getMessage())) {
      return FALSE;
    }
    return TRUE;
  }

}
