<?php

namespace UnbLibraries\DockWorker\Robo;

use Robo\Robo;
use Robo\Tasks;
use UnbLibraries\DockWorker\Robo\DockWorkerCommand;

/**
 * Defines commands in the GitCommand namespace.
 */
class DrupalCommand extends DockWorkerCommand {

  use \Boedah\Robo\Task\Drush\loadTasks;

  /**
   * This hook will fire for all commands in this command file.
   *
   * @hook init
   */
  public function initialize() {
    $this->getInstanceName();
    $this->getContainerRunning();
  }

  /**
   * Rebuild the cache in the Drupal container.
   *
   * @command drupal:cr
   */
  public function resetCache() {
    return $this->taskDockerExec($this->getInstanceName())
      ->interactive()
      ->exec(
        $this->taskDrushStack()
          ->drupalRootDirectory('/app/html')
          ->uri('default')
          ->drush('cr')
      )
      ->run();
  }

  /**
   * Run the behat tests located in tests/.
   *
   * @command drupal:tests:behat
   */
  public function runBehatTests() {
    return $this->taskDockerExec($this->getInstanceName())
      ->interactive()
      ->exec('/scripts/runTests.sh')
      ->run();
  }

  /**
   * Get a ULI from the Drupal container.
   *
   * @command drupal:uli
   */
  public function uli() {
    return $this->taskDockerExec($this->getInstanceName())
      ->interactive()
      ->exec(
        $this->taskDrushStack()
          ->drupalRootDirectory('/app/html')
          ->uri('default')
          ->drush('uli')
      )
      ->run();
  }

  /**
   * Write out the configuration from the instance.
   *
   * @command drupal:write-config
   */
  public function writeConfig() {
    return $this->taskDockerExec($this->getInstanceName())
      ->interactive()
      ->exec('/scripts/configExport.sh')
      ->run();
  }

}
