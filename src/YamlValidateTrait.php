<?php

namespace Dockworker;

/**
 * Defines trait for validate
 */
trait YamlValidateTrait {

  use \Sweetchuck\Robo\Phpcs\PhpcsTaskLoader;

  /**
   * Validate files using phpcs.
   */
  protected function validateYaml($files) {
    if (!empty($files)) {
      $linter_bin = $this->repoRoot . '/vendor/bin/yaml-lint';
      // Lint files.
      $return_code = 0;
      foreach ($files as $lint_file) {
        $return = $this->taskExec($linter_bin)
          ->arg($lint_file)
          ->run();
        if ($return->getExitCode() != "0") {
          $return_code = 1;
        }
      }
      return $return_code;
    }
    else {
      print "No YAML files found to lint!\n";
    }
  }

}
