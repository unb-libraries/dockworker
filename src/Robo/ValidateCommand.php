<?php

namespace UnbLibraries\DockWorker\Robo;

use Robo\Robo;
use Robo\Tasks;
use UnbLibraries\DockWorker\Robo\DockWorkerCommand;

/**
 * Defines commands in the ValidateCommand namespace.
 */
class ValidateCommand extends DockWorkerCommand {

  use \Sweetchuck\Robo\Phpcs\PhpcsTaskLoader;

  const ERROR_FAILED_TWIG_LINTING = '%s failed Twig linting';
  const INFO_CREATE_PHPCS_SYMLINK = 'Created symlink for Drupal coding standard to phpcs directory';

  /**
   * Set the Drupal coding symlink for phpcs.
   */
  public function setPhpCsCoderSymlink() {
    $target = $this->repoRoot . '/vendor/drupal/coder/coder_sniffer/Drupal';
    $link = $this->repoRoot . '/vendor/squizlabs/php_codesniffer/CodeSniffer/Standards/Drupal';

    if (!file_exists($link)) {
      symlink(
        $target,
        $link
      );
      $this->logger->info(self::INFO_CREATE_PHPCS_SYMLINK);
    };
  }

  /**
   * Validate all files in the 'custom' directory.
   *
   * @command validate:custom:audit
   */
  public function validateAllCustom() {
    $directory = new \RecursiveDirectoryIterator($this->repoRoot . '/custom');
    $iterator = new \RecursiveIteratorIterator($directory);
    $files = [];
    foreach ($iterator as $info) {
      $files[] = $info->getPathname();
    }

    $this->validateYamlFiles(implode("\n", $files));
    $this->validateTwig(implode("\n", $files));
    $this->validatePhpCs(implode("\n", $files));
  }

  /**
   * Validate files using phpcs.
   *
   * @command validate:phpcs:files
   */
  public function validatePhpCs($files) {
    $this->setPhpCsCoderSymlink();
    $files_array = explode("\n", $files);

    // Only validate PHP files with PHPCS.
    $lint_extensions = [
      'php',
      'inc',
      'lib',
      'module',
    ];

    foreach ($files_array as $file_key => $filename) {
      $fileExt = pathinfo($filename, PATHINFO_EXTENSION);
      if (!in_array($fileExt, $lint_extensions)) {
        unset($files_array[$file_key]);
      }
    }

    if (!empty($files_array)) {
      // Lint files.
      return $this->taskPhpcsLintFiles()
        ->setStandards(['Drupal'])
        ->setReport('full')
        ->setFiles($files_array)
        ->setColors(TRUE)
        ->run();
    }
    else {
      print "No PHP files found to lint!\n";
    }
  }

  /**
   * Validate files using yaml-lint.
   *
   * @command validate:yaml:files
   */
  public function validateYamlFiles($files) {
    $files_array = explode("\n", $files);

    // Only validate PHP files with PHPCS.
    $lint_extensions = [
      'yml',
      'yaml',
    ];

    foreach ($files_array as $file_key => $filename) {
      $fileExt = pathinfo($filename, PATHINFO_EXTENSION);
      if (!in_array($fileExt, $lint_extensions) || substr($filename, 0, strlen('config-yml')) === 'config-yml') {
        unset($files_array[$file_key]);
      }
    }

    if (!empty($files_array)) {
      $linter_bin = $this->repoRoot . '/vendor/bin/yaml-lint';
      // Lint files.
      $return_code = 0;

      foreach ($files_array as $lint_file) {
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

  /**
   * Validates twig files.
   *
   * @command validate:twig:files
   */
  public function validateTwig($files) {
    $files_array = explode("\n", $files);
    foreach ($files_array as $file) {
      $twig = '.html.twig';
      $length = strlen($twig);
      if (substr($file, -$length) === $twig) {
        $result = $this->_exec(
          "vendor/bin/twig-lint lint $file"
        );
        if ($result->getExitCode() > 0) {
          throw new \Exception(
            sprintf(self::ERROR_FAILED_TWIG_LINTING, $file)
          );
        }
      }
    }
  }

}
