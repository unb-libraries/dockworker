<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Robo\Plugin\Commands\DockworkerCommands;

/**
 * Defines the commands used to test an application.
 */
class ApplicationTestCommands extends DockworkerCommands {

  /**
   * Tests the application using all testing frameworks.
   *
   * @command tests:all
   * @aliases test
   */
  public function testApplication() {
    // No-op here. Specific frameworks implement this in post-command hook.
  }

}
