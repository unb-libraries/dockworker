<?php

namespace Dockworker\Jira;

use Dockworker\Storage\DockworkerPersistentDataStorageTrait;
use Exception;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\IssueField;
use JiraRestApi\Issue\IssueSearchResult;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;
use JiraRestApi\Project\ProjectService;

/**
 * Trait for interacting with a Jira instance.
 */
trait JiraConnectorTrait
{
    use DockworkerPersistentDataStorageTrait;
    use JiraProjectKeysTrait;

    /**
     * The config to use.
     *
     * @var \JiraRestApi\Configuration\ArrayConfiguration
     */
    protected ArrayConfiguration $jiraConfig;

    /**
     * The Jira server hostname.
     *
     * @var string
     */
    protected string $jiraEndpointUri;

    /**
     * The Jira server user name to authenticate with.
     *
     * @var string
     */
    protected string $jiraUserName;

    /**
     * The Jira server user password to authenticate with.
     *
     * @var string
     */
    protected string $jiraUserPassword;

    /**
     * The Jira project service.
     *
     * @var \JiraRestApi\Project\ProjectService
     */
    protected ProjectService $jiraProjectService;

    /**
     * The Jira issue service.
     *
     * @var \JiraRestApi\Issue\IssueService
     */
    protected IssueService $jiraIssueService;

    /**
     * Displays a list of open Jira issues.
     *
     * @throws \Exception
     */
    protected function displayOpenJiraIssues(): void
    {
        $this->initJiraConnection();
        $headers = ['ID', 'Summary', 'Last Updated'];
        foreach ($this->jiraProjectKeys as $project_key) {
            $rows = [];
            $jql = "project in ($project_key) and status not in (Resolved, closed)";
            $result = $this->getIssuesJql($jql);
            if (!empty($result->issues)) {
                foreach ($result->issues as $issue) {
                    $rows[] = [
                        $issue->key,
                        $issue->fields->summary,
                        $issue->fields->updated->format('Y-m-d H:i:s'),
                    ];
                }
                $this->dockworkerIO->setDisplayTable(
                    $headers,
                    $rows
                );
            } else {
                $this->dockworkerIO->writeln('No open issues found.');
            }
        }
    }

    /**
     * Sets up the Jira configuration.
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
     * Sets the Jira hostname.
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
     * Sets the Jira username.
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
     * Sets the Jira user password.
     *
     * Jira on-premises doesn't allow API keys to auth, so we need to
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

    /**
     * Sets the Jira service object.
     *
     * @throws \Exception
     */
    protected function setJiraServices(): void
    {
        $this->jiraProjectService = new ProjectService($this->jiraConfig);
        $this->jiraIssueService = new IssueService($this->jiraConfig);
    }

    /**
     * Retrieves a list of Jira issues matching a JQL query.
     *
     * @param string $jql
     *   The JQL query to execute.
     *
     * @return \JiraRestApi\Issue\IssueSearchResult|null
     */
    protected function getIssuesJql(string $jql): IssueSearchResult|null
    {
        try {
            return $this->jiraIssueService->search($jql);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Creates a new stub issue in Jira.
     *
     * @throws \Exception
     */
    protected function createNewJiraStubIssue(): void
    {
        $this->initJiraConnection();
        $this->dockworkerIO->section('Creating new Jira issue');
        $project_key = $this->dockworkerIO->ask(
            'Enter the Issue\'s JIRA project key',
            $this->getFirstJiraProjectKey()
        );
        $issue_type = $this->dockworkerIO->askRestricted(
            "Enter the Issue type ('Bug', 'Task', 'Story')",
            ['Bug', 'Task', 'Story'],
            'Task'
        );
        $issue_summary = $this->dockworkerIO->ask('Enter a Short Issue Summary (Title)');
        try {
            $issue_field = new IssueField();
            $issue_field->setProjectKey($project_key)
                ->setSummary($issue_summary)
                ->setIssueTypeAsString($issue_type);
            $ret = $this->jiraIssueService->create($issue_field);
            $this->dockworkerIO->say('Issue created: ' . $ret->key);
        } catch (JiraException $e) {
            $this->dockworkerIO->error($e->getMessage());
            exit(1);
        }
    }
}
