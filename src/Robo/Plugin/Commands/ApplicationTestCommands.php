<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Robo\Plugin\Commands\DockworkerCommands;

/**
 * Defines commands for testing an application.
 */
class ApplicationTestCommands extends DockworkerCommands {

  /**
   * Test the application using all testing frameworks.
   *
   * @command application:test-all
   * @aliases test
   */
  public function testApplication() {
  }

}
