<?php

namespace Dockworker;

/**
 * Class for LogCheckerTrait.
 */
trait LogCheckerTrait {

  protected $logErrorExceptions = [];
  protected $logErrors = [];

  protected $logErrorTriggers = [
    'error',
  ];

  protected $logIgnoredErrors = [];

  protected function checklogsForErrors(array $logs) {
    foreach ($logs as $id => $log) {
      $this->checklogForErrors($id, $log);
    }
  }

  protected function checklogForErrors($id, $log) {
    $line = strtok($log, PHP_EOL);
    $line_no = 0;
    while ($line !== FALSE) {
      $line_no++;
      $line = strtok(PHP_EOL);
      $this->evaluateLineForErrors($line, $line_no, $id);
    }
  }

  protected function evaluateLineForErrors($line, $line_no, $id) {
    foreach ($this->logErrorTriggers as $trigger) {
      if (strpos($line, $trigger) !== FALSE) {
        foreach ($this->logErrorExceptions as $exception => $reason) {
          if (strpos($line, $exception) !== FALSE) {
            $this->logIgnoredErrors[] = [
              'id' => $id,
              'line' => $line_no,
              'error' => $line,
              'info' => $reason,
            ];
            continue 2;
          }
        }

        $this->logErrors[] = [
          'id' => $id,
          'line' => $line_no,
          'error' => $line,
          'info' => "Triggered by matching [$trigger]",
        ];
      }
    }
  }

  protected function logsHaveErrors() {
    return !empty($this->logErrors);
  }

  protected function logsHaveErrorExceptions() {
    return !empty($this->logIgnoredErrors);
  }

  protected function addLogErrorExceptions(array $exceptions) {
    $this->logErrorExceptions = array_merge($this->logErrorExceptions, $exceptions);
  }

}
