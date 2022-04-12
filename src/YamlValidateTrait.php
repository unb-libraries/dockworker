<?php

namespace Dockworker;

/**
 * Provides methods to validate YAML templates.
 */
trait YamlValidateTrait {

  /**
   * Validates YAML files using yaml-lint.
   *
   * @param string[] $files
   *   The files to validate.
   *
   * @return int
   *   The return code of the compile command.
   */
  protected function validateYaml(array $files) {
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
      return 0;
    }
  }

}
