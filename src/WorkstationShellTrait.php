<?php

namespace Dockworker;

use Dockworker\PersistentGlobalDockworkerConfigTrait;
use Robo\Robo;

/**
 * Provides methods to download data GitHub repositories.
 */
trait WorkstationShellTrait {

  use PersistentGlobalDockworkerConfigTrait;

  /**
   * The shell of the current workstation.
   *
   * @var string
   */
  protected $workstationShell;

  /**
   * Sets the local shell used.
   *
   * @hook post-init @workstationshell
   */
  public function setWorkstationShell() {
    if (empty($this->workstationShell)) {
      $this->workstationShell =
        !empty($this->getGlobalDockworkerConfigItem('dockworker.workstation.shell')) ?
        $this->getGlobalDockworkerConfigItem('dockworker.workstation.shell') :
        '/bin/sh';
    }
  }

}
