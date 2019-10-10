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
   *
   * @return \Robo\Result
   */
  protected function validatePhp($files, array $lint_standards = ['PSR2']) {
    if (!empty($files)) {
      return $this->taskPhpcsLintFiles()
        ->setStandards($lint_standards)
        ->setReport('full')
        ->setFiles($files)
        ->setColors(TRUE)
        ->run();
    }
    else {
      print "No PHP files found to lint!\n";
    }
  }

}
