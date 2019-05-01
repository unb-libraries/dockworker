<?php

namespace Dockworker;

use Symfony\Component\Finder\Finder;
use Dockworker\Robo\Plugin\Commands\DockworkerApplicationCommands;

/**
 * Defines trait for validate
 */
trait PhpCsValidateTrait {

  use \Sweetchuck\Robo\Phpcs\PhpcsTaskLoader;

  /**
   * Validate files using phpcs.
   */
  protected function validate($files, array $lint_standards = ['PSR2']) {
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

  /**
   * Validate files using phpcs.
   */
  protected function validatePaths(array $paths = [], array $lint_extensions = ['php'], array $lint_standards = ['PSR2']) {
    if (!empty($files)) {
      return $this->validate($files, $lint_standards);
    }
    else {
      print "No PHP files found to lint!\n";
    }
  }

}
