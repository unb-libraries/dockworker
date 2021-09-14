<?php

namespace Dockworker;

use Sweetchuck\Robo\Phpcs\PhpcsTaskLoader;

/**
 * Provides methods to validate PHP code for standards and errors.
 */
trait PhpValidateTrait {

  use PhpcsTaskLoader;

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
      $cmd = $this->taskPhpcsLintFiles()
        ->setStandards($lint_standards)
        ->setReport('full')
        ->setFiles($files)
        ->setColors(TRUE);
      if ($no_warnings) {
        $cmd->setWarningSeverity(0);
      }
      return $cmd->run();
    }
    else {
      print "No PHP files found to lint!\n";
    }
  }

}
