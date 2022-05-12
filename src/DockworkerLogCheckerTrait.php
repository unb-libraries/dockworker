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
   * Audits a dockworker application's k8s resource logs for errors.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function auditK8sPodLogs($print_errors = TRUE) {
    if ($this->logsHaveErrorExceptions()) {
      $this->printConsoleTable(
        "Ignored Errors",
        ['Pod ID', 'Line', 'Error', 'Info'],
        $this->logIgnoredErrors
      );
    }

    if ($this->logsHaveErrors()) {
      if ($print_errors) {
        $this->printK8sPodLogErrors();
      }
      throw new DockworkerException(sprintf("%s errors found in logs!", count($this->logErrors)));
    }
  }

  /**
   * Prints a summary of the current log errors to the console.
   *
   */
  protected function printK8sPodLogErrors() {
    $this->printConsoleTable(
      "Errors",
      ['Pod ID', 'Line', 'Error', 'Info'],
      $this->logErrors
    );
  }

}
