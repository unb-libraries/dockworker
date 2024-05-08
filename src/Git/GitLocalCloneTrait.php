<?php

namespace Dockworker\Git;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitException;
use CzProject\GitPhp\GitRepository;
use Dockworker\Git\GitRepoTrait;
use Dockworker\Storage\TemporaryStorageTrait;

/**
 * Provides methods to authenticate and interact with a GitHub repo.
 */
trait GitLocalCloneTrait
{
    use GitRepoTrait;
    use TemporaryStorageTrait;

    /**
     * Clones a GitHub repository to a local directory, including all references.
     *
     * @param string $owner
     *   The owner of the repository to clone.
     * @param string $name
     *   The name of the repository to clone.
     * @param string $checkout_branch
     *   The branch to checkout after cloning.
     * @param mixed[] $metadata
     *   Metadata to store in the repository.
     * @param string $target_directory
     *   The target directory to clone the repository into.
     * @param bool $use_cli
     *   Whether to use the git CLI to clone the repository.
     */
    protected function archiveGitHubRepository(
        string $owner,
        string $name,
        string $checkout_branch = 'dev',
        array $metadata = [],
        string $target_directory = '',
        bool $use_cli = false
    ): string {
        $bare_repo_path = $this->createTemporaryLocalStorage("github-clone-$owner-$name-bare");
        if (empty($target_directory)) {
            $repo_path = $this->createTemporaryLocalStorage("github-clone-$owner-$name");
        } else {
            $repo_path = $target_directory;
        }
        $git = new Git();

        if ($use_cli) {
            passthru("git clone --mirror " . $this->getGitHubRepositoryCloneUrl($owner, $name) . " $bare_repo_path");
        } else {
            $git->cloneRepository($this->getGitHubRepositoryCloneUrl($owner, $name), $bare_repo_path, ['--mirror']);
        }

        rename($bare_repo_path, $repo_path . '/.git');

        $this->applicationRepository = $this->getGitRepoFromPath($repo_path);
        $this->applicationRepository->execute('config', ['--bool', 'core.bare', 'false']);
        $this->applicationRepository->checkout($checkout_branch);
        $this->applicationRepository->execute('reset', ['--hard', 'HEAD']);

        file_put_contents("$repo_path/metadata.json", json_encode($metadata, JSON_PRETTY_PRINT));
        return $repo_path;
    }

    /**
     * Retrieves the clone URL for a GitHub repository.
     */
    protected function getGitHubRepositoryCloneUrl(
        string $owner,
        string $name,
        bool $use_https = false
    ): string {
        if ($use_https) {
            return "https://github.com/$owner/$name.git";
        }
        return "git@github.com:$owner/$name.git";
    }
}
