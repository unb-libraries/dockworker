<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Robo\Plugin\Commands\DockworkerLocalDaemonCommands;

/**
 * Defines the commands used to interact with a Dockworker local application.
 */
class DockworkerLocalDaemonCleanupCommands extends DockworkerLocalDaemonCommands {

  /**
   * Deletes any persistent data from this application's stopped local deployment.
   *
   * @hook post-command local:rm
   *
   * @return \Robo\Result
   *   The result of the removal command.
   */
  public function removeDaemonData() {
    $this->unSetHostFileEntries();
  }

}
