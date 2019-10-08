<?php

namespace Dockworker;

use Dockworker\ConsoleOutputFormattingTrait;
use Dockworker\LogCheckerTrait;

/**
 * Class for LogCheckerTrait.
 */
trait DockworkerLogCheckerTrait {

  use ConsoleOutputFormattingTrait;
  use LogCheckerTrait;

  /**
   * @throws \Exception
   */
  protected function auditProcessedLogs() {
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
      throw new \Exception(sprintf("%s errors found in deployment logs!", count($this->logErrors)));
    }
  }

}
