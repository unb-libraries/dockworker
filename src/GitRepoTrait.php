<?php

namespace Dockworker;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitRepository;

/**
 * Provides methods to interact with a local git repo.
 */
trait GitRepoTrait
{
    /**
     * The application's git repository.
     *
     * @var \CzProject\GitPhp\GitRepository;
     */
    protected GitRepository $applicationRepository;

    /**
     * Sets up the lean repository git repo.
     *
     * @hook pre-init
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
   * @return \CzProject\GitPhp\GitRepository
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
     * @param \CzProject\GitPhp\GitRepository $repository
     *   The repository to query for changed files.
     * @param string $file_mask
     *   The regex pattern to search for, as a string.
     *
     * @return string[]
     *   The changed files, keyed by file path and values indicating status.
     * @throws \CzProject\GitPhp\GitException
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
                    $files[$file[1]] = $file[0];
                }
            }
        }
        return $files;
    }
}
