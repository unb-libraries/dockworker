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
     * @hook pre-init
     */
    public function setJiraProperties(): void
    {
        $jira_project_keys = $this->getConfigItem(
            Robo::config(),
            'dockworker.application.jira.project_keys'
        );
        if ($jira_project_keys != null) {
            $this->jiraProjectKeys = array_merge(
                $this->jiraGlobalProjectKeys,
                $jira_project_keys
            );
        }
    }
}
