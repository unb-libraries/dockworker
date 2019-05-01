<?php

namespace Dockworker;

use Symfony\Component\Finder\Finder;
use Dockworker\Robo\Plugin\Commands\DockworkerApplicationCommands;

/**
 * Defines trait for building SCSS files
 */
trait RecursivePathFileOperatorTrait {

  private $recursivePathOperatorFiles = [];

  /**
   * Add files recursively to the to-operate list.
   */
  protected function addRecursivePathFilesFromPath(array $paths, array $extension_filter = []) {
    foreach ($paths as $path) {
      $directory = new \RecursiveDirectoryIterator($path);
      $iterator = new \RecursiveIteratorIterator($directory);
      $files = [];
      foreach ($iterator as $info) {
        $files[] = $info->getPathname();
      }

      foreach ($files as $file_key => $filename) {
        $fileExt = pathinfo($filename, PATHINFO_EXTENSION);
        if (empty($extension_filter) || in_array($fileExt, $extension_filter)) {
          $this->recursivePathOperatorFiles[] = $filename;
        }
      }
    }
  }

  /**
   * Return the list of current files as a string.
   */
  protected function clearRecursivePathFiles() {
    $this->recursivePathOperatorFiles = [];
  }

  /**
   * Return the list of current files as a string.
   */
  protected function getRecursivePathFiles() {
    return $this->recursivePathOperatorFiles;
  }

  /**
   * Return the list of current files as a string.
   */
  protected function getRecursivePathStringFileList($separator = ' ', $quote = '\'') {
    if (!empty($this->recursivePathOperatorFiles)) {
      return $quote . implode("$quote$separator$quote", $this->recursivePathOperatorFiles) . $quote;
    }
    return NULL;
  }

}
