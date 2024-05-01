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
     */
    protected function archiveGitHubRepository(
        string $owner,
        string $name,
        string $checkout_branch = 'dev',
        array $metadata = [],
        string $target_directory = ''
    ): string {
        $bare_repo_path = $this->createTemporaryLocalStorage("github-clone-$owner-$name-bare");
        if (empty($target_directory)) {
            $repo_path = $this->createTemporaryLocalStorage("github-clone-$owner-$name");
        }
        else {
            $repo_path = $target_directory;
        }
        $git = new Git();
        
        $git->cloneRepository($this->getGitHubRepositoryCloneUrl($owner, $name), $bare_repo_path, ['--mirror']);
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
    protected function getGitHubRepositoryCloneUrl(string $owner, string $name): string {
        return "https://github.com/$owner/$name.git";
    }

}
