<?php

namespace Dockworker;

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
}
