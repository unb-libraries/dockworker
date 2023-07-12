<?php

namespace Dockworker\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Dockworker\DockworkerCommands;
use Dockworker\GitHub\GitHubRepoSettingsTrait;
use Dockworker\IO\DockworkerIOTrait;
use Robo\Robo;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides commands for building the application's theme assets.
 */
class GitHubRepositorySettingsCommands extends DockworkerCommands implements CustomEventAwareInterface
{
    use CustomEventAwareTrait;
    use DockworkerIOTrait;
    use GitHubRepoSettingsTrait;

    /**
     * Sets topics for the repository.
     *
     * @command github:repository:set-metadata
     * @hidden
     */
    public function setRepositoryMetadata(): void
    {
        $this->initRepositorySettings();
        $this->setRepositoryDescription();
        $this->setRepositoryTopics();
    }

    protected function setRepositoryDescription(): void
    {
        $this->dockworkerIO->title("Setting GitHub Repository Description");
        try {
            $description = Robo::Config()->get('dockworker.application.description');
            $uri = Robo::Config()->get('dockworker.application.uri');
            $this->dockworkerIO->block($description);
            $this->dockworkerIO->block($uri);
                $this->writeGitHubRepositoryDescription(
                    $this->applicationGitHubRepoOwner,
                    $this->applicationGitHubRepoName,
                    [
                        'description' => trim($description),
                        'homepage' => $uri,
                        'has_wiki' => false,
                        'has_issues' => false,
                        'has_downloads' => false,
                    ]
                );
            $this->say("GitHub repository description set successfully!");
        } catch (\Exception $e) {
            $this->dockworkerIO->error("Unable to set GitHub repository description: " . $e->getMessage());
            exit(1);
        }
    }

    /**
     * Sets topics for the repository.
     */
    protected function setRepositoryTopics(): void
    {
        $this->dockworkerIO->title("Setting GitHub Repository Topics");
        $this->clearGitHubRepositoryTopics();
        $this->addGitHubRepositoryTopics(
            $this->getGitHubRepositoryTopics()
        );
        try {
            $this->dockworkerIO->listing($this->gitHubRepositoryTopics);
            $this->writeGitHubRepositoryTopics(
                $this->applicationGitHubRepoOwner,
                $this->applicationGitHubRepoName
            );
            $this->say("GitHub repository topics set successfully!");
        } catch (\Exception $e) {
            $this->dockworkerIO->error("Unable to set GitHub repository topics: " . $e->getMessage());
            exit(1);
        }
    }

    /**
     * Initializes the repository's settings.
     */
    protected function initRepositorySettings(): void
    {
        $this->initGitHubClientApplicationRepo(
            $this->applicationGitHubRepoOwner,
            $this->applicationGitHubRepoName
        );
        $this->checkPreflightChecks($this->dockworkerIO);
    }

    /**
     * Returns the topics to set for the repository.
     *
     * @return string[]
     *   An array of topics.
     */
    protected function getGitHubRepositoryTopics(): array
    {
        $file_path = "$this->applicationRoot/vendor/unb-libraries/dockworker/data/github.yml";
        $data = Yaml::parseFile($file_path);
        $topics = $data['github']['repository']['topics'];

        foreach (Robo::Config()->get('dockworker.application.topics') as $topic) {
            $topics[] = $topic;
        }

        $handlers = $this->getCustomEventHandlers('dockworker-github-topics');
        foreach ($handlers as $handler) {
            $new_topics = $handler();
            $topics = array_merge(
                $topics,
                $new_topics
            );
        }
        return $topics;
    }
}
