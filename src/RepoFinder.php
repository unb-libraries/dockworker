<?php

namespace Dockworker;

/**
 * Defines a class to find the current repository context.
 */
class RepoFinder
{
    /**
     * Finds the root directory for the repository.
     *
     * Inspired by BLT.
     * @link https://github.com/acquia/blt Acquia BLT
     *
     * @return string
     *   The root directory for the repository.
     */
    public static function findRepoRoot(): string
    {
        $possible_repo_roots = [
            $_SERVER['PWD'],
            getcwd(),
            realpath(__DIR__ . '/../'),
            realpath(__DIR__ . '/../../../'),
        ];
        foreach ($possible_repo_roots as $possible_repo_root) {
            if ($repo_root = self::findDirectoryContainingFiles(
                $possible_repo_root,
                [
                    'vendor/bin/dockworker',
                    'vendor/autoload.php',
                ]
            )
            ) {
                return $repo_root;
            }
        }
        return '';
    }

    /**
     * Traverses file system upwards in search of a given file.
     *
     * Begins searching for $file in $working_directory and climbs up directories
     * $max_height times, repeating search. Inspired by BLT.
     * @link https://github.com/acquia/blt Acquia BLT
     *
     * @param string $working_directory
     *  The directory to begin searching in.
     * @param string[] $files
     *  The files to search for.
     * @param int $max_height
     *  The maximum number of directories to traverse.
     *
     * @return bool|string
     *   FALSE if file was not found. Otherwise, the directory path containing the
     *   file.
     */
    private static function findDirectoryContainingFiles(
        string $working_directory,
        array $files,
        int $max_height = 10
    ): bool|string {
        $file_path = $working_directory;
        for ($i = 0; $i <= $max_height; $i++) {
            if (self::filesExist($file_path, $files)) {
                return $file_path;
            }
            else {
                $file_path = realpath($file_path . '/..');
            }
        }
        return false;
    }

    /**
     * Determines if an array of files exist in a particular directory.
     *
     * @param string $dir
     *  The directory to search in.
     * @param string[] $files
     *  The files to search for.
     *
     * @return bool
     */
    private static function filesExist(string $dir, array $files): bool
    {
        foreach ($files as $file) {
            if (!file_exists($dir . '/' . $file)) {
                return false;
            }
        }
        return true;
    }
}
