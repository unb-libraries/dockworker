<?php

namespace Dockworker\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Dockworker\DockworkerCommands;
use Dockworker\DockworkerException;
use Dockworker\Git\GitCommitMessageValidatorTrait;
use Dockworker\IO\DockworkerIOTrait;
use Dockworker\Jira\JiraConnectorTrait;
use Dockworker\Jira\JiraProjectKeysTrait;
use JiraRestApi\Issue\IssueField;

/**
 * Defines commands used to validate a git commit message.
 *
 * @link https://github.com/acquia/blt Acquia BLT
 * @link https://github.com/mleko/validate-commit Commit Validation
 */
class DockworkerCommitMessageValidateCommands extends DockworkerCommands
{
    use DockworkerIOTrait;
    use GitCommitMessageValidatorTrait;
    use JiraConnectorTrait;
    use JiraProjectKeysTrait;

    protected const ERROR_INVALID_COMMIT_MESSAGE = 'Invalid commit message!';
    protected const ERROR_MISSING_JIRA_INFO = 'JIRA project and issue missing from git commit\'s subject line.';
    protected const WARN_MISSING_JIRA_INFO = 'You have not specified a JIRA project and issue in your subject line.';
    protected const ASK_MISSING_JIRA_INFO_ACTION = 'Would you like to (i) ignore the warning, (a) abort, (l) list open issues, or (c) create stub issue?';

    /**
     * Validates a git commit message for this application.
     *
     * @param string $message_file
     *   The path to a file containing the git commit message.
     *
     * @command git:commit:validate-message
     * @usage /tmp/commit_msg.txt
     * @hidden
     *
     * @throws \Dockworker\DockworkerException
     */
    public function validateCommitMsg(
        string $message_file
    ): void {
        $message = file_get_contents($message_file);

        $this->message = $message;
        $this->subjectLine = str_contains($message, "\n") ?
            strstr($message, "\n", true) :
            $message;

        // Validators.
        $this->getValidateIsEmpty();
        $this->getValidateMessageWidth();
        $this->getValidateBodySeparation();
        $this->getValidateSubjectLength();
        $this->getValidateSubjectCapital();
        $this->getValidatePeriodEnding();

        // Process universal errors.
        if (!empty($this->errors)) {
            $this->dockworkerIO->section('Commit Message Validation Failure(s):');
            $this->dockworkerIO->listing($this->errors);
            $this->showSampleCommitMessage();
            exit(1);
        }

        $this->validateJiraProjectPrefix();
        print_r('About to actually commit');
        exit(1);
    }

    protected function validateJiraProjectPrefix() {
        $valid_keys = array_merge(
            $this->jiraGlobalProjectKeys,
            $this->jiraProjectKeys
        );
        if (!empty($valid_keys)) {
            if (!$this->getValidateProjectPrefix($valid_keys)) {
                $this->dockworkerIO->warning(self::WARN_MISSING_JIRA_INFO);
                $action = $this->dockworkerIO->ask(self::ASK_MISSING_JIRA_INFO_ACTION);
                switch($action) {
                    case 'i':
                        break;
                    case 'l':
                        $this->dockworkerIO->writeln('Listing open issues in Jira...');
                        $this->displayOpenJiraIssues();
                        exit(1);
                    case 'c':
                        $this->dockworkerIO->writeln('Creating stub issue in Jira...');
                        $this->createNewJiraStubIssue();
                        exit(1);
                    default:
                        $this->showSampleCommitMessage();
                        $this->dockworkerIO->error(self::ERROR_MISSING_JIRA_INFO);
                        exit(1);
                }
            }
        }
    }

    /**
     * Displays a list of open JIRA issues.
     *
     * @throws \Exception
     */
    protected function displayOpenJiraIssues() {
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
            }
            else {
                $this->dockworkerIO->writeln('No open issues found.');
            }
        }
    }

    /**
     * Creates a new stub issue in JIRA.
     *
     * @throws \Exception
     */
    protected function createNewJiraStubIssue() {
        $this->initJiraConnection();
        $this->dockworkerIO->section('Creating new JIRA issue');
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
        }
        catch (JiraException $e) {
            $this->dockworkerIO->error($e->getMessage());
            exit(1);
        }
    }

    /**
     * Validates arguments for validate:git:commit-msg.
     *
     * @hook validate git:commit:validate-message
     *
     * @throws \Dockworker\DockworkerException
     */
    public function validateCommitMsgValidator(CommandData $commandData): void
    {
        // Validates the commit message's filepath.
        $message_file = $commandData->input()->getArgument('message_file');
        $this->exceptIfFileDoesNotExist($message_file);
    }
}
