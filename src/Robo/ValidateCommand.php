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
   * Validate files using phpcs.
   *
   * @command validate:phpcs:files
   */
  public function validatePhpCs($files) {
    $this->setPhpCsCoderSymlink();

    $files_array = explode("\n", $files);
    return $this->taskPhpcsLintFiles()
      ->setStandard('Drupal')
      ->setReport('full')
      ->setFiles($files_array)
      ->setColors(TRUE)
      ->run();
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
