<?php

namespace Dockworker\Jira;

use Dockworker\Storage\DockworkerPersistentDataStorageTrait;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\IssueSearchResult;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\Project\ProjectService;

/**
 * Trait for interacting with a Jira instance.
 */
trait JiraConnectorTrait
{
    use DockworkerPersistentDataStorageTrait;

    /**
     * The config to use.
     *
     * @var \JiraRestApi\Configuration\ArrayConfiguration
     */
    protected $jiraConfig;

    /**
     * The jira server hostname.
     *
     * @var string
     */
    protected $jiraEndpointUri;

    /**
     * The jira server user name to authenticate with.
     *
     * @var string
     */
    protected $jiraUserName;

    /**
     * The jira server user password to authenticate with.
     *
     * @var string
     */
    protected $jiraUserPassword;

    /**
     * The jira project service.
     *
     * @var object
     */
    protected $jiraProjectService;

    /**
     * The jira issue service.
     *
     * @var object
     */
    protected $jiraIssueService;

    /**
     * Sets up the JIRA configuration.
     *
     * @throws \Exception
     */
    protected function initJiraConnection(): void
    {
        $this->setJiraUri();
        $this->setJiraUser();
        $this->setJiraPass();
        $this->setJiraConfig();
        $this->setJiraServices();
    }

    /**
     * Sets the JIRA hostname.
     *
     * @throws \Exception
     */
    protected function setJiraUri(): void
    {
        $this->jiraEndpointUri = $this->getSetDockworkerPersistentDataConfigurationItem(
            'jira',
            'uri',
            "Enter the URI of the Jira endpoint",
            'https://jira.lib.unb.ca',
            '',
            [],
            'DOCKWORKER_JIRA_URI'
        );
    }

    /**
     * Sets the JIRA service object.
     *
     * @throws \Exception
     */
    protected function setJiraServices(): void
    {
        $this->jiraProjectService = new ProjectService($this->jiraConfig);
        $this->jiraIssueService = new IssueService($this->jiraConfig);
    }

    /**
     * Sets the JIRA username.
     *
     * @throws \Exception
     */
    protected function setJiraUser(): void
    {
        $this->jiraUserName = $this->getSetDockworkerPersistentDataConfigurationItem(
            'jira',
            'username',
            "Enter the username to use at $this->jiraEndpointUri",
            '',
            '',
            [],
            'DOCKWORKER_JIRA_USER_NAME'
        );
    }

    /**
     * Sets the JIRA user password.
     *
     * JIRA on-premises doesn't allow API keys to generate, so we need to
     * enter a password at run-time.
     *
     * @throws \Exception
     */
    protected function setJiraPass(): void
    {
        $this->jiraUserPassword = $this->getSetDockworkerPersistentDataConfigurationItem(
            'jira',
            'password',
            "Enter $this->jiraUserName's JIRA password at $this->jiraEndpointUri",
            '',
            '',
            [],
            'DOCKWORKER_JIRA_USER_PASSWORD'
        );
    }

    /**
     * Sets up the config array.
     *
     * @throws \Exception
     */
    protected function setJiraConfig(): void
    {
        $this->jiraConfig = new ArrayConfiguration(
            [
                'jiraHost' => $this->jiraEndpointUri,
                'jiraUser' => $this->jiraUserName,
                'jiraPassword' => $this->jiraUserPassword,
            ]
        );
    }

    protected function getIssuesJql($jql): IssueSearchResult|null {
        try {
            return $this->jiraIssueService->search($jql);
        } catch (\Exception $e) {
            return null;
        }
    }
}
