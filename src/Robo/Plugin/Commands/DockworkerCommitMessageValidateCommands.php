<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerException;
use Dockworker\GitCommitMessageValidatorTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;
use Robo\Robo;

/**
 * Defines commands used to validate a git commit message.
 *
 * @link https://github.com/acquia/blt Acquia BLT
 * @link https://github.com/mleko/validate-commit Commit Validation
 */
abstract class DockworkerCommitMessageValidateCommands extends DockworkerCommands
{
    use GitCommitMessageValidatorTrait;

    protected const ERROR_INVALID_COMMIT_MESSAGE = 'Invalid commit message!';
    protected const ERROR_MISSING_JIRA_INFO = 'JIRA project and issue missing from subject line.';
    protected const SAMPLE_VALID_COMMIT_MESSAGE = 'Valid example: HERB-135 Add the new picture field to the article feature';
    protected const WARN_MISSING_JIRA_INFO = 'You have not specified a JIRA project and issue in your subject line. Continue Anyway?';

    /**
     * Validates a git commit message against project standards.
     *
     * @param string $message_file
     *   The path to the file containing the git commit message.
     *
     * @command validate:git:commit-msg
     * @usage /tmp/commit_msg.txt
     *
     * @throws \Dockworker\DockworkerException
     */
    public function validateCommitMsg($message_file): void
    {
        $message_file_path = $this->repoRoot . '/' . $message_file;
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
            $this->say("Commit messages issues:\n");
            foreach ($this->errors as $error) {
                $this->say($error);
            }
            $this->say(self::SAMPLE_VALID_COMMIT_MESSAGE);
            throw new DockworkerException(self::ERROR_INVALID_COMMIT_MESSAGE);
        }

        // Validate optional project prefix.
        if (!$this->getValidateProjectPrefix()) {
            if (!$this->getAllowsPrefixLessCommit()) {
                if (!$this->confirm(self::WARN_MISSING_JIRA_INFO)) {
                    $this->say(self::SAMPLE_VALID_COMMIT_MESSAGE);
                    throw new DockworkerException(self::ERROR_MISSING_JIRA_INFO);
                }
            }
        }
    }

    /**
     * Gets if the project allows prefix-less commit messages.
     *
     * @return bool
     *   The command's total run time, formatted for humans.
     */
    protected function getAllowsPrefixLessCommit(): bool
    {
        $allow_prefixless_commits = Robo::Config()->get('dockworker.git.allow_prefixless_commits');
        if (empty($upstream_image)) {
            return false;
        }
        return (bool) $allow_prefixless_commits;
    }
}
