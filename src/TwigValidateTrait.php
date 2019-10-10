<?php

namespace Dockworker;

use Dockworker\DockworkerException;

/**
 * Provides methods to validate Twig templates.
 */
trait TwigValidateTrait {

  /**
   * Validates twig files using twig-lint.
   *
   * @param string[] $files
   *   The files to validate.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function validateTwig(array $files) {
    if (!empty($files)) {
      foreach ($files as $file) {
        $twig = '.html.twig';
        $length = strlen($twig);
        if (substr($file, -$length) === $twig) {
          $result = $this->_exec(
            "vendor/bin/twig-lint lint $file"
          );
          if ($result->getExitCode() > 0) {
            throw new DockworkerException(
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
