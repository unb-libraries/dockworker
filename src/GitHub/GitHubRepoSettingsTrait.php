<?php

namespace Dockworker\GitHub;

use Dockworker\GitHub\GitHubClientTrait;

/**
 * Provides methods to authenticate and interact with a GitHub repo's settings.
 */
trait GitHubRepoSettingsTrait
{
    use GitHubClientTrait;

    /**
     * The GitHub repository topics.
     *
     * @var <string></string>
     */
    protected array $gitHubRepositoryTopics = [];

    /**
     * Adds a list of GitHub repository topics.
     *
     * @param string[] $topics
     *   The list of topics to add.
     */
    protected function addGitHubRepositoryTopics(array $topics): void
    {
        foreach ($topics as $topic) {
            $this->addGitHubRepositoryTopic($topic);
        }
    }

    /**
     * Adds a GitHub repository topic.
     *
     * @param string $topic
     *   The topic to add.
     */
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

    /**
     * Clears the GitHub repository topics.
     */
    protected function clearGitHubRepositoryTopics(): void
    {
        $this->gitHubRepositoryTopics = [];
    }

    /**
     * Writes the GitHub repository topics.
     *
     * @param string $owner
     *   The GitHub repository owner.
     * @param string $name
     *   The GitHub repository name.
     */
    protected function writeGitHubRepositoryTopics($owner, $name): void
    {
        $this->gitHubClient->api('repo')->replaceTopics(
            $owner,
            $name,
            array_unique($this->gitHubRepositoryTopics)
        );
    }

    /**
     * Writes the GitHub repository description.
     *
     * @param string $owner
     *   The GitHub repository owner.
     * @param string $name
     *   The GitHub repository name.
     * @param string[] $description
     *   The GitHub repository description.
     */
    protected function writeGitHubRepositoryDescription($owner, $name, array $description): void
    {
        $this->gitHubClient->api('repo')->update(
            $owner,
            $name,
            $description
        );
    }
}
