<?php

namespace Dockworker;

/**
 * Provides methods to check k8s resource pod logs for errors.
 */
trait LogCheckerTrait {

  /**
   * Strings used to ignore errors in log files.
   *
   * @var string[]
   */
  protected $logErrorExceptions = [];

  /**
   * Errors found in logs.
   *
   * @var string[]
   */
  protected $logErrors = [];

  /**
   * Strings used to identify errors in log files.
   *
   * @var string[]
   */
  protected $logErrorTriggers = [
    'error',
    'Error',
    'ERROR',
  ];

  /**
   * Errors that have been ignored in the logs.
   *
   * @var string[]
   */
  protected $logIgnoredErrors = [];

  /**
   * Checks an array of logs for errors.
   *
   * @param string[] $logs
   *   The name of the container to execute the command in.
   */
  protected function checkLogsForErrors(array $logs) {
    foreach ($logs as $id => $log) {
      $this->checkLogForErrors($id, $log);
    }
  }

  /**
   * Checks a log file for errors.
   *
   * @param string $id
   *   An ID to assign to the log when storing errors.
   * @param string $log
   *   The log contents.
   */
  protected function checkLogForErrors($id, $log) {
    $line = strtok($log, PHP_EOL);
    $line_no = 0;
    while ($line !== FALSE) {
      $line_no++;
      $line = strtok(PHP_EOL);
      $this->evaluateLineForErrors($line, $line_no, $id);
    }
  }

  /**
   * Evaluates a line of a log for errors.
   *
   * @param string $line
   *   The line to evaluate.
   * @param string $line_no
   *   A line number to use as a reference when logging errors.
   * @param string $id
   *   An ID to assign to the log when storing errors.
   */
  protected function evaluateLineForErrors($line, $line_no, $id) {
    foreach ($this->logErrorTriggers as $trigger) {
      if (str_contains($line, $trigger)) {
        foreach ($this->logErrorExceptions as $exception => $reason) {
          if (str_contains($line, $exception)) {
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

  /**
   * Determines if the previously parsed logs had errors.
   *
   * @return bool
   */
  protected function logsHaveErrors() {
    return !empty($this->logErrors);
  }

  /**
   * Determines if the previously parsed logs had skipped errors.
   *
   * @return bool
   */
  protected function logsHaveErrorExceptions() {
    return !empty($this->logIgnoredErrors);
  }

  /**
   * Adds exceptions to the current list of exceptions.
   *
   * @param string[] $exceptions
   *   An associative array of exceptions to add.
   */
  protected function addLogErrorExceptions(array $exceptions) {
    $this->logErrorExceptions = array_merge($this->logErrorExceptions, $exceptions);
  }

  /**
   * Adds triggers to the current list of triggers.
   *
   * @param string[] $triggers
   *   An associative array of triggers to add.
   */
  protected function addLogErrorTriggers(array $triggers) {
    $this->logErrorTriggers = array_merge($this->logErrorTriggers, $triggers);
  }

}
