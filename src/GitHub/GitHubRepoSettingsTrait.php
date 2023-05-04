<?php

namespace Dockworker\GitHub;

use Dockworker\GitHub\GitHubClientTrait;

/**
 * Provides methods to authenticate and interact with a GitHub repo's settings.
 */
trait GitHubRepoSettingsTrait
{
    use GitHubClientTrait;
    protected array $gitHubRepositoryTopics = [];

    protected function addGitHubRepositoryTopics(array $topics): void
    {
        foreach ($topics as $topic) {
            $this->addGitHubRepositoryTopic($topic);
        }
    }

    protected function addGitHubRepositoryTopic(string $topic): void
    {
        $this->gitHubRepositoryTopics[] = strtolower(
            str_replace(
                ' ',
                '-',
                $topic
            )
        );
    }

    protected function clearGitHubRepositoryTopics(): void
    {
        $this->gitHubRepositoryTopics = [];
    }

    protected function writeGitHubRepositoryTopics($owner, $name): void
    {
        $this->gitHubClient->api('repo')->replaceTopics(
            $owner,
            $name,
            $this->gitHubRepositoryTopics
        );
    }

    protected function writeGitHubRepositoryDescription($owner, $name, array $description): void
    {
        $this->gitHubClient->api('repo')->update(
            $owner,
            $name,
            $description
        );
    }

}
