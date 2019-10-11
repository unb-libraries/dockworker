<?php

namespace Dockworker;

use Dockworker\ConsoleOutputFormattingTrait;
use Dockworker\DockworkerException;
use Dockworker\LogCheckerTrait;

/**
 * Provides methods to check Dockworker application logs for errors.
 */
trait DockworkerLogCheckerTrait {

  use ConsoleOutputFormattingTrait;
  use LogCheckerTrait;

  /**
   * Audit a dockworker application's startup logs for errors.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function auditStartupLogs() {
    if ($this->logsHaveErrorExceptions()) {
      $this->printConsoleTable(
        "Ignored Errors",
        ['Pod ID', 'Line', 'Error', 'Info'],
        $this->logIgnoredErrors
      );
    }

    if ($this->logsHaveErrors()) {
      $this->printConsoleTable(
        "Errors",
        ['Pod ID', 'Line', 'Error', 'Info'],
        $this->logErrors
      );
      throw new DockworkerException(sprintf("%s errors found in deployment logs!", count($this->logErrors)));
    }
  }

}
