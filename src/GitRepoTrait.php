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
   * @param string $path
   *   The path to the git repository.
   *
   * @return bool
   *   TRUE if the repository is clean. FALSE otherwise.
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

  /**
   * Retrieves the latest commit hash from a git repository.
   *
   * @param string $path
   *   The path to the git repository.
   *
   * @return string
   *   The hash if the repository exists, empty otherwise.
   */
  private function gitRepoLatestCommitHash($path) {
    $latest_hash = $this->taskExec('git')
      ->dir($path)
      ->arg('rev-parse')
      ->arg('HEAD')
      ->silent(TRUE)
      ->printOutput(FALSE)
      ->run();

    if (!empty($latest_hash->getMessage())) {
      return $latest_hash->getMessage();
    }
    return '';
  }

  /**
   * Retrieves the current branch from a git repository.
   *
   * @param string $path
   *   The path to the git repository.
   *
   * @return string
   *   The branch name if the repository exists, empty otherwise.
   */
  private function gitRepoCurrentBranch($path) {
    $cur_branch = $this->taskExec('git')
      ->dir($path)
      ->arg('rev-parse')
      ->arg('--abbrev-ref')
      ->arg('HEAD')
      ->silent(TRUE)
      ->printOutput(FALSE)
      ->run();

    if (!empty($cur_branch->getMessage())) {
      return $cur_branch->getMessage();
    }
    return '';
  }

}
