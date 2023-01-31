<?php

namespace Dockworker;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Provides methods to operate on a series of files recursively in a path.
 */
trait RecursivePathFileOperatorTrait {

  /**
   * Filepaths to operate on.
   *
   * @var string[]
   */
  private $recursivePathOperatorFiles = [];

  /**
   * Adds files recursively to the operation queue.
   *
   * @param string[] $paths
   *   An array of file paths to traverse recursively.
   * @param string[] $extension_filter
   *   The extensions to include when populating to queue.
   */
  protected function addRecursivePathFilesFromPath(array $paths, array $extension_filter = []) {
    foreach ($paths as $path) {
      if (file_exists($path)) {
        $directory = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($directory);
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
  }

  /**
   * Filters an array of files, removing any that do not match given extensions.
   *
   * @param string[] $files
   *   The array of files to filter.
   * @param string[] $extension_filter
   *   An array of extensions to keep in the file list.
   *
   * @return string[]
   *   The filtered array of files.
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

  /**
   * Clears the list of files in the operation queue.
   */
  protected function clearRecursivePathFiles() {
    $this->recursivePathOperatorFiles = [];
  }

  /**
   * Returns the list of current files in the operation queue.
   *
   * @return string[]
   *   The list of currently queued files.
   */
  protected function getRecursivePathFiles() {
    return $this->recursivePathOperatorFiles;
  }

  /**
   * Returns the list of current files as a string.
   *
   * @param string $separator
   *   The separator to use when formatting the output list.
   * @param string $quote
   *   The string/character used when formatting the output list.
   *
   * @return string
   *   The formatted list of current files.
   */
  protected function getRecursivePathStringFileList($separator = ' ', $quote = '\'') {
    if (!empty($this->recursivePathOperatorFiles)) {
      return $quote . implode("$quote$separator$quote", $this->recursivePathOperatorFiles) . $quote;
    }
    return '';
  }

}
