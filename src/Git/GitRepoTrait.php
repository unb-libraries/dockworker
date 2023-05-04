<?php

namespace Dockworker\Git;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitException;
use CzProject\GitPhp\GitRepository;
use Dockworker\DockworkerException;

/**
 * Provides methods to interact with a local git repo.
 *
 * @INTERNAL This trait is intended only to be used by Dockworker commands. It
 * references the Dockworker application root, which is not in its own scope.
 */
trait GitRepoTrait
{
    /**
     * The application's git repository.
     *
     * @var GitRepository;
     */
    protected GitRepository $applicationRepository;

    /**
     * Sets up the lean repository git repo.
     *
     * @hook init
     *
     * @throws \Dockworker\DockworkerException
     */
    public function initGitRepo(): void
    {
        $this->applicationRepository = $this->getGitRepoFromPath($this->applicationRoot);
        if (empty($this->applicationRepository)) {
            throw new DockworkerException('Could not initialize the git repository.');
        }
    }

  /**
   * Retrieves a git repository object from a repository path.
   *
   * @param string $path
   *   The path to the git repository.
   *
   * @return GitRepository
   *   The git repository object.
   */
    protected function getGitRepoFromPath(string $path): GitRepository
    {
        $git = new Git();
        return $git->open($path);
    }

    /**
     * Retrieves a list of changed files in the repository.
     *
     * 'Inspired' by https://github.com/czproject/git-php/pull/42/files
     *
     * @param GitRepository $repository
     *   The repository to query for changed files.
     * @param string $file_mask
     *   The regex pattern to search for, as a string.
     *
     * @return string[]
     *   The changed files, keyed by file path and values indicating status.
     * @throws GitException
     */
    protected function getGitRepoChanges(
        GitRepository $repository,
        string $file_mask = ''
    ): array {
        $repository->execute('update-index', '-q', '--refresh');
        $output = $repository->execute('status', '--porcelain');
        $files = [];
        foreach ($output as $line) {
            $line = trim($line);
            $file = explode(" ", $line, 2);
            if (count($file) >= 2) {
                if (empty($file_mask) || preg_match($file_mask, $file[1])) {
                    $files[trim($file[1])] = $file[0];
                }
            }
        }
        return $files;
    }

    /**
     * Retrieves a list of files staged for commit in the repository.
     *
     * @param \CzProject\GitPhp\GitRepository $repository
     *   The repository to query for staged files.
     * @param string $file_mask
     *   An optional regex pattern for files to include in the list.
     *
     * @return array
     *   The staged files, keyed by file path and values indicating status.
     *
     * @throws \CzProject\GitPhp\GitException
     */
    protected function getGitRepoStagedFiles(
        GitRepository $repository,
        string $file_mask = ''
    ): array {
        $staged_changes = [];
        $changes = $this->getGitRepoChanges(
            $repository,
            $file_mask
        );
        foreach ($changes as $file => $status) {
            if (str_contains($status, 'A')) {
                $staged_changes[] = $file;
            }
        }
        return $staged_changes;
    }

    /**
     * Retrieves files staged for commit in the current application repository.
     *
     * @param string $file_mask
     *   An optional regex pattern for files to include in the list.
     *
     * @return array
     * @throws \CzProject\GitPhp\GitException
     */
    protected function getApplicationGitRepoStagedFiles(
        string $file_mask = ''
    ): array {
        return $this->getGitRepoStagedFiles(
            $this->applicationRepository,
            $file_mask
        );
    }

    /**
     * Retrieves changed files in the current application repository.
     *
     * @param string $file_mask
     *   An optional regex pattern for files to include in the list.
     *
     * @return array
     *   The changed files.
     *
     * @throws \CzProject\GitPhp\GitException
     */
    protected function getApplicationGitRepoChangedFiles(
        string $file_mask = ''
    ): array {
        return array_keys($this->getGitRepoChanges(
            $this->applicationRepository,
            $file_mask
        ));
    }

    /**
     * Checks if a file has changes in the repository.
     *
     * @param string $file_path
     *   The path to the file to check.
     *
     * @return bool
     *   TRUE if the file has changes, FALSE otherwise.
     *
     * @throws GitException
     */
    protected function repoFileHasChanges(string $file_path): bool
    {
        $changes = $this->getGitRepoChanges($this->applicationRepository);
        return array_key_exists($file_path, $changes);
    }
}
