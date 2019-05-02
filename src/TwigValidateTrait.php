<?php

namespace Dockworker;

/**
 * Defines trait for validate
 */
trait TwigValidateTrait {

  /**
   * Validate files using phpcs.
   */
  protected function validateTwig($files) {
    if (!empty($files)) {
      foreach ($files as $file) {
        $twig = '.html.twig';
        $length = strlen($twig);
        if (substr($file, -$length) === $twig) {
          $result = $this->_exec(
            "vendor/bin/twig-lint lint $file"
          );
          if ($result->getExitCode() > 0) {
            throw new \Exception(
              sprintf('%s failed Twig linting', $file)
            );
          }
        }
      }
    }
    else {
      print "No TWIG files found to lint!\n";
    }
  }

}
