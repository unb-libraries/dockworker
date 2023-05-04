<?php

namespace Dockworker\Jira;

use Dockworker\Core\RoboConfigTrait;
use Robo\Robo;

/**
 * Provides methods to interact with Jira for this dockworker application.
 */
trait JiraProjectKeysTrait
{
    use RoboConfigTrait;

    /**
     * A list of Jira project keys that apply to all projects.
     *
     * @var string[]
     */
    protected array $jiraGlobalProjectKeys = ['IN', 'DOCKW'];

    /**
     * The Jira project keys relating to this application.
     *
     * @var string[]
     */
    protected array $jiraProjectKeys = [];

    /**
     * Initializes the Jira properties for the application.
     *
     * @hook init
     */
    public function setJiraProperties(): void
    {
        $this->jiraProjectKeys = $this->getConfigItem(
            Robo::config(),
            'dockworker.application.workflows.jira.project_keys'
        );
    }

    protected function getFirstJiraProjectKey(): string
    {
        return $this->jiraProjectKeys[0];
    }
}
