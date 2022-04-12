<?php

namespace Dockworker;

/**
 * Provides methods to validate PHP code for standards and errors.
 */
trait PhpValidateTrait {

  /**
   * Validates files using phpcs.
   *
   * @param string $files
   *   The files to validate.
   * @param string[] $lint_standards
   *   An array of linting standards to enforce.
   * @param bool $no_warnings
   *  Only report errors, not warnings.
   *
   * @return \Robo\Result
   */
  protected function validatePhp($files, array $lint_standards = ['PSR2'], $no_warnings = FALSE) {
    if (!empty($files)) {
      $standards_string = implode(',', $lint_standards);

      $cmd = [
        'vendor/bin/phpcs',
        '--colors',
        "--standard=$standards_string",
        '--runtime-set ignore_warnings_on_exit 1',
      ];
      if ($no_warnings) {
        $cmd[] = '--warning-severity=0';
      }
      $cmd[] = '--';
      $cmd = array_merge($cmd, $files);

      $cmd_string = implode(' ', $cmd);
      $robo_cmd = $this->taskExec($cmd_string);
      return $robo_cmd->run();
    }
    else {
      print "No PHP files found to lint!\n";
    }
  }

}
