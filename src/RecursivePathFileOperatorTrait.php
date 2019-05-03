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
      $this->recursivePathOperatorFiles = array_merge(
        $this->recursivePathOperatorFiles,
        self::filterArrayFilesByExtension($files, $extension_filter)
      );
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

  /**
   * Filter an array of files, removing any that do not match given extensions.
   *
   * @param string[] $files
   *   The array of files to filter.
   * @param string[] $extension_filter
   *   An array of extensions to keep in the file list.
   *
   * @return string[]
   *    The filtered array of files.
   */
  protected static function filterArrayFilesByExtension(array $files, $extension_filter = []) {
    foreach ($files as $file_key => $filename) {
      $fileExt = pathinfo($filename, PATHINFO_EXTENSION);
      if (!empty($extension_filter) && !in_array($fileExt, $extension_filter)) {
        unset($files[$file_key]);
      }
    }
    return $files;
  }

}
