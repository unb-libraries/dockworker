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
     * @throws \Dockworker\DockworkerException
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

        // Validate optional project prefix.
        if (!$this->getValidateProjectPrefix()) {
            if (!$this->allowPrefixlessCommitMessages()) {
                if (!$this->confirm(self::WARN_MISSING_JIRA_INFO)) {
                    $this->dockworkerSay([self::SAMPLE_VALID_COMMIT_MESSAGE]);
                    throw new DockworkerException(self::ERROR_MISSING_JIRA_INFO);
                }
            }
        }
    }

    /**
     * Determines if the application permits prefix-less commit messages.
     *
     * @return bool
     *   True if the commit messages do not require prefixes. False otherwise.
     */
    protected function allowPrefixlessCommitMessages(): bool
    {
        $allow_prefixless_commits = Robo::Config()->get('dockworker.git.allow_prefixless_commits');
        if (empty($upstream_image)) {
            return false;
        }
        return (bool) $allow_prefixless_commits;
    }
}
