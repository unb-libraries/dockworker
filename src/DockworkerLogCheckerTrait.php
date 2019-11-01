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
  protected function auditStartupLogs($print_errors = TRUE) {
    if ($this->logsHaveErrorExceptions()) {
      $this->printConsoleTable(
        "Ignored Errors",
        ['Pod ID', 'Line', 'Error', 'Info'],
        $this->logIgnoredErrors
      );
    }

    if ($this->logsHaveErrors()) {
      if ($print_errors) {
        $this->printStartupLogErrors();
      }
      throw new DockworkerException(sprintf("%s errors found in startup logs!", count($this->logErrors)));
    }
  }

  protected function printStartupLogErrors() {
    $this->printConsoleTable(
      "Errors",
      ['Pod ID', 'Line', 'Error', 'Info'],
      $this->logErrors
    );
  }

}
