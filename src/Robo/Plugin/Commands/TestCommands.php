<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerCommands;

/**
 * Provides commands for running the application's tests.
 */
class TestCommands extends DockworkerCommands
{

  /**
   * Execute this application's tests.
   *
   * This command is a placeholder for framework-specific test commands that
   * can be implemented by extensions of this package.
   *
   * @command test:all
   * @aliases test-all
   */
  public function runTests(): void
  {
    // Pass.
  }

  /**
   * Execute this application's unit tests.
   *
   * This command is a placeholder for framework-specific unit-test commands that
   * can be implemented by extensions of this package.
   *
   * @command test:unit
   * @aliases test-unit
   */
  public function runUnitTests(): void
  {
    // Pass.
  }

  /**
   * Execute this application's e2e tests.
   *
   * This command is a placeholder for framework-specific e2e-test commands that
   * can be implemented by extensions of this package.
   *
   * @param string[] $options
   *   An array of options to pass to the command.
   *
   * @option bool $headless Whether to run the tests in headless mode.
   * @default $headless FALSE
   *
   * @command test:e2e
   * @aliases test-e2e
   *
   */
  public function runE2eTests(
    array $options = ['headless' => FALSE]
  ): void
  {
    // Pass.
  }

}
