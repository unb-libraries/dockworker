<?php

namespace UnbLibraries\DockWorker\Robo;

use Robo\Robo;
use Robo\Tasks;
use UnbLibraries\DockWorker\Robo\DockWorkerCommand;

/**
 * Defines commands in the GitCommand namespace.
 */
class GitCommand extends DockWorkerCommand {

  /**
   * Run git-clean on the repository.
   *
   * @command git:clean
   */
  public function clean() {
    return $this->_exec('git clean -fdx');
  }

  /**
   * Pull the upstream commits from Github.
   *
   * @command git:pull-current
   */
  public function pullCurrentBranch() {
    return $this->taskGitStack()
      ->pull();
  }

  /**
   * Setup git hooks.
   *
   * @command git:setup-hooks
   */
  public function setupHooks() {
    $source_dir = $this->repoRoot . "/vendor/unblibraries/dockworker/scripts/git-hooks";
    $target_dir = $this->repoRoot . "/.git/hooks";
    $this->_copy("$source_dir/commit-msg", "$target_dir/commit-msg");
    $this->_copy("$source_dir/pre-commit", "$target_dir/pre-commit");
  }

}
