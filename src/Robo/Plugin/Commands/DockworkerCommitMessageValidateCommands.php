<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\GitCommitMessageValidatorTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;

/**
 * Defines commands used to validate a git commit message.
 *
 * @link https://github.com/acquia/blt Acquia BLT
 * @link https://github.com/mleko/validate-commit Commit Validation
 */
class DockworkerCommitMessageValidateCommands extends DockworkerCommands
{
    use GitCommitMessageValidatorTrait;

    protected const ERROR_INVALID_COMMIT_MESSAGE = 'Invalid commit message!';
    protected const ERROR_MISSING_JIRA_INFO = 'JIRA project and issue missing from subject line.';
    protected const SAMPLE_VALID_COMMIT_MESSAGE = 'Valid example: HERB-135 Add the new picture field to the article feature';
    protected const WARN_MISSING_JIRA_INFO = 'You have not specified a JIRA project and issue in your subject line. Continue Anyway?';

    /**
     * Validates a git commit message against this application's standards.
     *
     * @param string $message_file
     *   The path to a file containing the git commit message.
     *
     * @command validate:git:commit-msg
     * @usage /tmp/commit_msg.txt
     *
     * @jira
     */
    public function validateCommitMsg(string $message_file): void
    {
        $message_file_path = $this->applicationRoot . '/' . $message_file;
        $message = file_get_contents($message_file_path);

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
            $this->dockworkerSubTitle('Issues with commit message');
            $this->dockworkerListing($this->errors);
            $this->dockworkerSay([self::SAMPLE_VALID_COMMIT_MESSAGE]);
            throw new DockworkerException(self::ERROR_INVALID_COMMIT_MESSAGE);
        }

        if (!empty($this->jiraProjectKeys)) {
            if (!$this->getValidateProjectPrefix($this->jiraProjectKeys)) {
                if (!$this->confirm(self::WARN_MISSING_JIRA_INFO)) {
                    $this->dockworkerSay([self::SAMPLE_VALID_COMMIT_MESSAGE]);
                    throw new DockworkerException(self::ERROR_MISSING_JIRA_INFO);
                }
            }
        }
    }
}
