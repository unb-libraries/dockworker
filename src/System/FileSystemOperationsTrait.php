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
     * @param string[] $path_elements
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
            // @phpstan-ignore-next-line
            mkdir($path_string, $permissions, true);
        }
        return $path_string;
    }

    /**
     * Retrieves an absolute path from an array of path elements.
     *
     * @param string[] $path_elements
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
     * Sets the group ownership of a tree to the current user.
     *
     * @param string $path
     *  The path to the tree.
     *
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
     * Sets the group ownership of a file to the current user.
     *
     * @param string $path
     *   The path to the file.
     *
     * @throws \Dockworker\DockworkerException
     */
    protected function setFileGroupOwnershipToCurrentUser(string $path): void
    {
        $this->exceptIfFileDoesNotExist($path);
        chgrp($path, posix_getgid());
    }

    public static function bytesToHumanString($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round(
            $bytes / (1024 ** $pow),
            2
        ) . ' ' . $units[$pow];
    }
}
