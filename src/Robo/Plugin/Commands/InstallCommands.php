<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerCommands;

/**
 * Provides commands for installing application dependencies.
 */
class InstallCommands extends DockworkerCommands {

  /**
   * Install one or more dependencies.
   *
   * @param array $dependencies
   *   A list of dependencies to install.
   *
   * @option only
   *   Limit the dependency to only the provided value, e.g. "dev".
   *   Leave blank for no limitation.
   *
   * @command dockworker:install
   * @aliases install
   *
   * @usage dockworker install dep1 dep2 --only=dev
   */
  public function installDependencies(
    array $dependencies,
    array $options = [
      'only' => '',
    ]
  ): void
  {
    // Pass
  }

}
