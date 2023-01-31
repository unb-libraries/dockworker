<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\CommitMessageValidateTrait;
use Dockworker\DockworkerException;
use Dockworker\Robo\Plugin\Commands\DockworkerBaseCommands;

/**
 * Defines the commands used to validate a git commit message.
 *
 * @link https://github.com/acquia/blt Acquia BLT
 * @link https://github.com/mleko/validate-commit Commit Validation
 */
class CommitMessageValidateCommands extends DockworkerBaseCommands {

  use CommitMessageValidateTrait;

  const ERROR_INVALID_COMMIT_MESSAGE = 'Invalid commit message!';
  const ERROR_MISSING_JIRA_INFO = 'JIRA project and issue missing from subject line.';
  const SAMPLE_VALID_COMMIT_MESSAGE = 'Valid example: HERB-135 Add the new picture field to the article feature';
  const WARN_MISSING_JIRA_INFO = 'You have not specified a JIRA project and issue in your subject line. Continue Anyway?';

  /**
   * Validates a git commit message against project standards.
   *
   * @param string $message_file
   *   The path to the file containing the git commit message.
   *
   * @command validate:git:commit-msg
   * @throws \Dockworker\DockworkerException
   *
   * @usage /tmp/commit_msg.txt
   */
  public function validateCommitMsg($message_file) {
    $message_file_path = $this->repoRoot . '/' . $message_file;
    $message = file_get_contents($message_file_path);

    $this->message = $message;
    $this->subjectLine = str_contains($message, "\n") ?
      strstr($message, "\n", TRUE) :
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

}
