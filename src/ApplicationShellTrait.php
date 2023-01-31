<?php

namespace Dockworker;

use Robo\Robo;

/**
 * Provides methods to download data GitHub repositories.
 */
trait ApplicationShellTrait {

  /**
   * The shell of the current application.
   *
   * @var string
   */
  protected $applicationShell = '/bin/sh';

  /**
   * Sets the application shell used.
   *
   * @hook post-init @shell
   */
  public function setApplicationShell() {
    $deployment_shell = Robo::Config()->get('dockworker.application.shell');
    if (!empty($deployment_shell)) {
      $this->applicationShell = $deployment_shell;
    }
  }

}
