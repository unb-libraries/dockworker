<?php

namespace Dockworker\GitHub;

use Dockworker\IO\DockworkerIO;
use Github\ResultPager;
use Symfony\Component\Console\Helper\Table;

/**
 * Trait for interacting with multiple instance repositories in GitHub.
 */
trait GitHubMultipleRepositoryTrait
{
    use GitHubClientTrait;

    /**
     * The repositories to operate on.
     *
     * @var array
     */
    protected array $githubRepositories = [];

    /**
     * Determines the list of repositories to operate on and confirms list with user.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param array $organizations
     *     An array of organizations to search for repositories.
     * @param array $include_names
     *     Only repositories whose names contain one of $include_names values will be
     *     returned. Optional.
     * @param array $include_topics
     *     Only repositories whose topics contain one of $include_topics values
     *     exactly will be stored. Optional.
     * @param array $include_callbacks
     *     Only repositories whose filter callbacks functions provided here return
     *     TRUE will be stored. Optional.
     * @param array $omit_names
     *     An array of repository names to omit from the list.
     * @param array $omit_topics
     *     An array of repository topics to omit from the list.
     * @param string $operation_description
     *     The operation string to display in the confirmation message. Defaults to
     *     'operation'.
     * @param bool $no_confirm
     *     TRUE if all confirmations be assumed yes.
     *
     * @return bool
     *     TRUE if user agreed, FALSE otherwise.
     */
    protected function setConfirmRepositoryList(
        DockworkerIO $io,
        array $organizations,
        array $include_names = [],
        array $include_topics = [],
        array $include_callbacks = [],
        array $omit_names = [],
        array $omit_topics = [],
        string $operation_description = 'operation',
        bool $no_confirm = false,
    ): bool {
        $this->setRepositoryList(
            $io,
            $organizations,
            $include_names,
            $include_topics,
            $include_callbacks,
            $omit_names,
            $omit_topics
        );

        // Optionally filter them.
        if (!$no_confirm) {
            $this->listRepositoryNames($io);
            $need_remove = $this->confirm('Would you like to remove any instances?');
        } else {
            $need_remove = false;
        }

        while ($need_remove == true) {
            $to_remove = $this->ask('Which ones? (Specify Name, Comma separated list)');
            if (!empty($to_remove)) {
                $removes = explode(',', $to_remove);
                foreach ($this->githubRepositories as $repository_index => $repository) {
                    if (in_array($repository['name'], $removes)) {
                        $this->say("Removing {$repository['name']} from list");
                        unset($this->githubRepositories[$repository_index]);
                    }
                }
            }
            $this->listRepositoryNames($io);
            $need_remove = $this->confirm('Would you like to remove any more instances?');
        }

        if (!$no_confirm) {
            return $this->confirm(
                sprintf(
                    'The %s operation(s) will be applied to ALL of the above repositories. Are you sure you want to continue?',
                    $operation_description
                )
            );
        } else {
            return true;
        }
    }

    /**
     * Gets the list of repositories to operate on from GitHub and filter them.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param array $organizations
     *     An array of organizations to search for repositories.
     * @param array $include_names
     *     Only repositories whose names contain one of $include_names values will be
     *     returned. Optional.
     * @param array $include_topics
     *     Only repositories whose topics contain one of $include_topics values
     *     exactly will be stored. Optional.
     * @param array $include_callbacks
     *     Only repositories whose filter callbacks functions provided here return
     *     TRUE will be stored. Optional.
     * @param array $omit
     *     An array of repository names to omit from the list.
     * @param array $omit_topics
     *     An array of repository topics to omit from the list.
     */
    private function setRepositoryList(
        DockworkerIO $io,
        array $organizations,
        array $include_names = [],
        array $include_topics = [],
        array $include_callbacks = [],
        array $omit = [],
        array $omit_topics = []
    ): void {
        $io->say('Getting repositories from GitHub API...');
        $this->populateGitHubRepositoryList($organizations);

        // Case : no filtering.
        if (
            empty($include_names[0]) &&
            empty($include_topics[0]) &&
            empty($include_callbacks[0])
        ) {
            return;
        }

        // Filter repositories. Place these in order of least intensive to most!
        $this->filterRepositoriesByName($io, $include_names);
        $this->filterRepositoriesByCallback($io, $include_callbacks);
        $this->filterRepositoriesByTopic($io, $include_topics);

        // Remove omissions.
        foreach ($this->githubRepositories as $repository_index => $repository) {
            foreach ($omit_topics as $omit_topic) {
                if (in_array($omit_topic, $repository['topics'])) {
                    unset($this->githubRepositories[$repository_index]);
                    break;
                }
            }
            if (in_array($repository['name'], $omit)) {
                unset($this->githubRepositories[$repository_index]);
            }
        }

        // If we have any repositories left, pedantically rekey the array.
        $this->githubRepositories = array_values($this->githubRepositories);
    }

    /**
     * Populates the repository list with all organizational repositories.
     */
    private function populateGitHubRepositoryList(array $organizations): void
    {
        $paginator = new ResultPager($this->gitHubClient);
        foreach ($organizations as $organization) {
            $organization_api = $this->gitHubClient->api('organization');
            $this->githubRepositories = array_merge(
                $this->githubRepositories,
                $paginator->fetchAll(
                    $organization_api,
                    'repositories',
                    [$organization]
                )
            );
        }
        usort($this->githubRepositories, fn($a, $b) => strcmp($a['name'], $b['name']));
    }

    /**
     * Filters the repository list based on results of user-provided callbacks.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param string[] $include_callbacks
     *     An array of callback names to execute. Callbacks returning FALSE indicate
     *     to remove the item.
     */
    private function filterRepositoriesByCallback(
        DockworkerIO $io,
        array $include_callbacks
    ): void {
        if (!empty($include_callbacks[0])) {
            $io->say('Callback filtering repositories...');
            foreach ($this->githubRepositories as $repository_index => $repository) {
                foreach ($include_callbacks as $callback_filter) {
                    if (!call_user_func($callback_filter, $repository)) {
                        unset($this->githubRepositories[$repository_index]);
                        break;
                    }
                }
            }
            $io->say('Callback filtering complete!');
        }
    }

    /**
     * Filters the repository list based on their names.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param string[] $include_names
     *     An array of keywords to compare against repository names. Repositories
     *     that do not match any keywords will be removed.
     */
    private function filterRepositoriesByName(
        DockworkerIO $io,
        array $include_names
    ): void {
        if (!empty($include_names[0])) {
            $io->say('Name filtering repositories...');
            foreach ($this->githubRepositories as $repository_index => $repository) {
                if (!static::instanceNameMatchesSearchTerms($include_names, $repository['name'])) {
                    unset($this->githubRepositories[$repository_index]);
                }
            }
            $io->say('Name filtering complete!');
        }
    }

    /**
     * Filters the repository list based on their GitHub topics.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param string[] $include_topics
     *     An array of keywords to compare against repository topics. Repositories
     *     that do not match any of the topics will be filtered.
     */
    private function filterRepositoriesByTopic(
        DockworkerIO $io,
        array $include_topics
    ): void {
        if (!empty($include_topics[0])) {
            $io->say('Topic filtering repositories...');
            foreach ($this->githubRepositories as $repository_index => $repository) {
                // This assumes an AND filter for multiple repo topics.
                if (!count(array_intersect($repository['topics'], $include_topics)) == count($include_topics)) {
                    unset($this->githubRepositories[$repository_index]);
                }
            }
            $io->say('Topic filtering complete!');
        }
    }

    /**
     * Determines if a repository name partially matches multiple terms.
     *
     * @param array $terms
     *     An array of terms to match in a case-insensitive manner against the name.
     * @param string $name
     *     The name to match against.
     *
     * @return bool
     *     TRUE if the name matches one of the terms. FALSE otherwise.
     */
    public static function instanceNameMatchesSearchTerms(
        array $terms,
        string $name
    ): bool {
        foreach ($terms as $match) {
            if (stristr($name, (string) $match)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Outputs a formatted list of repositories set to operate on to the console.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     */
    protected function listRepositoryNames(DockworkerIO $io): void
    {
        $wrapped_rows = array_map(
            fn($el) => [$el['name']],
            $this->githubRepositories
        );
        $table = new Table($io->output());
        $table->setHeaders(['Repository Name'])
            ->setRows($wrapped_rows);
        $table->setStyle('borderless');
        $table->render();
    }
}
