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
   * Audits a dockworker application's logs for errors.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function auditApplicationLogs($print_errors = TRUE) {
    if ($this->logsHaveErrorExceptions()) {
      $this->printConsoleTable(
        "Ignored Errors",
        ['Pod ID', 'Line', 'Error', 'Info'],
        $this->logIgnoredErrors
      );
    }

    if ($this->logsHaveErrors()) {
      if ($print_errors) {
        $this->printApplicationLogErrors();
      }
      throw new DockworkerException(sprintf("%s errors found in logs!", count($this->logErrors)));
    }
  }

  /**
   * Prints a summary of the current log errors to the console.
   *
   */
  protected function printApplicationLogErrors() {
    $this->printConsoleTable(
      "Errors",
      ['Pod ID', 'Line', 'Error', 'Info'],
      $this->logErrors
    );
  }

}
