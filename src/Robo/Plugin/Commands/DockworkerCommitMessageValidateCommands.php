<?php

namespace Dockworker\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Dockworker\DockworkerCommands;
use Dockworker\DockworkerException;
use Dockworker\Git\GitCommitMessageValidatorTrait;
use Dockworker\IO\DockworkerIOTrait;
use Dockworker\Jira\JiraProjectKeysTrait;

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
    use JiraProjectKeysTrait;

    protected const ERROR_INVALID_COMMIT_MESSAGE = 'Invalid commit message!';
    protected const ERROR_MISSING_JIRA_INFO = 'JIRA project and issue missing from git commit\'s subject line.';
    protected const WARN_MISSING_JIRA_INFO = 'You have not specified a JIRA project and issue in your subject line. Continue Anyway?';

    /**
     * Validates a git commit message against this application's standards.
     *
     * @param string $message_file
     *   The path to a file containing the git commit message.
     *
     * @command git:commit:validate-message
     * @usage /tmp/commit_msg.txt
     *
     * @throws \Dockworker\DockworkerException
     *
     * @jira
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
            throw new DockworkerException(self::ERROR_INVALID_COMMIT_MESSAGE);
        }

        if (!empty($this->jiraProjectKeys)) {
            if (!$this->getValidateProjectPrefix($this->jiraProjectKeys)) {
                if (!$this->confirm(self::WARN_MISSING_JIRA_INFO)) {
                    $this->showSampleCommitMessage();
                    throw new DockworkerException(self::ERROR_MISSING_JIRA_INFO);
                }
            }
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
