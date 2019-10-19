<?php

namespace Dockworker;

use Dockworker\DockworkerException;

/**
 * Provides methods to interact with a local git repo.
 */
trait GitRepoTrait {

  /**
   * Determines if a git repository is clean.
   *
   * @param $path
   *   The path to the git repository.
   *
   * @return bool
   *   TRUE if the repository is clean, FALSE otherwise.
   */
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
