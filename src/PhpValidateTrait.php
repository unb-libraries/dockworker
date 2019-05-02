<?php

namespace Dockworker;

use Symfony\Component\Finder\Finder;
use Dockworker\Robo\Plugin\Commands\DockworkerApplicationCommands;

/**
 * Defines trait for validate
 */
trait PhpValidateTrait {

  use \Sweetchuck\Robo\Phpcs\PhpcsTaskLoader;

  /**
   * Validate files using phpcs.
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
