<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Robo\Plugin\Commands\DockworkerBaseCommands;

/**
 * Defines the commands used to test this application.
 */
class ApplicationTestCommands extends DockworkerBaseCommands {

  /**
   * Tests this application using all testing frameworks.
   *
   * @command tests:all
   * @aliases test
   */
  public function testApplication() {
    // No-op here. Specific frameworks implement this in post-command hook.
    $this->io()->title("Running All Tests");
  }

}
