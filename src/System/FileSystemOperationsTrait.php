<?php

namespace Dockworker\System;

use Dockworker\DockworkerException;
use Symfony\Component\Finder\Finder;

/**
 * Provides methods to interact with a local filesystem.
 */
trait FileSystemOperationsTrait
{
    /**
     * Initializes and retrieves a directory path from its path elements.
     *
     * @param array $path_elements
     *   The path elements to use to initialize the directory.
     * @param string $permissions
     *   The octal permissions to assign to the path, if it is created.
     *
     * @return string
     *   The path to the directory.
     */
    protected function initGetPathFromPathElements(
        array $path_elements,
        string $permissions = '0755'
    ): string {
        $path_string = $this->getPathFromPathElements($path_elements);
        if (!file_exists($path_string)) {
            mkdir($path_string, $permissions, true);
        }
        return $path_string;
    }

    /**
     * Retrieves an absolute path from an array of path elements.
     *
     * @param array $path_elements
     *   The path elements to use to construct the path.
     *
     * @return string
     *  The absolute path.
     */
    protected function getPathFromPathElements(array $path_elements): string
    {
        return implode('/', $path_elements);
    }

  /**
   * Throws an exception if a file does not exist.
   *
   * @param string $path
   *   The path to the file.
   *
   * @throws \Dockworker\DockworkerException
   */
    protected function exceptIfFileDoesNotExist(string $path): void
    {
        if (!file_exists($path)) {
            throw new DockworkerException("The file $path does not exist.");
        }
    }

    /**
     * @param string $path
     * @return void
     * @throws \Dockworker\DockworkerException
     */
    protected function setTreeGroupOwnershipToCurrentUser(string $path): void
    {
        $this->exceptIfFileDoesNotExist($path);
        $finder = new Finder();
        $finder->files()->in(__DIR__);
        foreach ($finder as $file) {
            $this->setFileGroupOwnershipToCurrentUser($file->getRealPath());
        }
        $this->setFileGroupOwnershipToCurrentUser($path);
    }

    /**
     * @param string $path
     * @return void
     * @throws \Dockworker\DockworkerException
     */
    protected function setFileGroupOwnershipToCurrentUser(string $path): void
    {
        $this->exceptIfFileDoesNotExist($path);
        chgrp($path, posix_getgid());
    }
}
