<?php

namespace UnbLibraries\DockWorker\Robo;

use Robo\Robo;
use Robo\Tasks;
use Robo\Result;

use UnbLibraries\DockWorker\Robo\DockWorkerCommand;

/**
 * Defines commands in the ValidateCommand namespace.
 *
 * Some tests copied from mleko/validate-commit.
 * Some tests copied from acquia/blt.
 */
class GitCommitMessageValidateCommand extends DockWorkerCommand {

  use \Cheppers\Robo\Phpcs\PhpcsTaskLoader;

  const ERROR_BODY_SEPARATION = 'Separate subject from body with a blank line';
  const ERROR_BODY_TOO_WIDE = 'Wrap the body at 72 characters, current max: %d';
  const ERROR_EMPTY_SUBJECT = "Subject line cannot be empty";
  const ERROR_INVALID_COMMIT_MESSAGE = 'Invalid commit message!';
  const ERROR_MISSING_JIRA_INFO = 'JIRA project and issue missing from subject line.';
  const ERROR_MISSING_PROJECT_PREFIX = "Commit messages must begin with the project prefix, followed by a hyphen,the JIRA issue number and a space";
  const ERROR_NOT_CAPITALIZED = "Capitalize subject line";
  const ERROR_PERIOD_END = 'Do not end the subject line with period';
  const ERROR_TOO_LONG = "Limit the subject line to 50 characters, %d present";
  const SAMPLE_VALID_COMMIT_MESSAGE = 'Valid example: HERB-135 Add the new picture field to the article feature';
  const WARN_MISSING_JIRA_INFO = 'You have not specified a JIRA project and issue in your subject line. Continue Anyway?';

  /**
   * Any errors thrown during the validation.
   *
   * @var string[]
   */
  private $errors = [];

  /**
   * The subject line of the git commit message.
   *
   * @var string
   */
  private $message;

  /**
   * The entire git commit message.
   *
   * @var string
   */
  private $subjectLine;

  /**
   * Validate the maximum message width.
   */
  public function getValidateMessageWidth() {
    $lines = explode("\n", $this->message);
    $max = max(array_map('strlen', $lines));
    if ($max > 72) {
      $this->errors[] = sprintf(self::ERROR_BODY_TOO_WIDE, $max);
    }
  }

  /**
   * Validate if body of commit message is separated from subject by a space.
   */
  public function getValidateBodySeparation() {
    $lines = explode("\n", $this->message);
    if (count($lines) < 2) {
      return NULL;
    }
    if ('' !== trim($lines[1])) {
      $this->errors[] = self::ERROR_BODY_SEPARATION;
    }
  }

  /**
   * Validate if subject exceeds maximum length.
   */
  private function getValidateSubjectLength() {
    $length = strlen($this->subjectLine);
    if ($length > 50) {
      $this->errors[] = sprintf(static::ERROR_TOO_LONG, $length);
    }
    return NULL;
  }

  /**
   * Validate if subject line has a first capitalized character.
   */
  private function getValidateSubjectCapital() {
    if (ucfirst($this->subjectLine) != $this->subjectLine) {
      $this->errors[] = static::ERROR_NOT_CAPITALIZED;
    }
    return NULL;
  }

  /**
   * Validate if subject is empty.
   */
  private function getValidateIsEmpty() {
    if (trim($this->subjectLine) == '') {
      $this->errors[] = self::ERROR_EMPTY_SUBJECT;
    }
  }

  /**
   * Validate if subject line ends in a period.
   */
  private function getValidatePeriodEnding() {
    if (substr($this->subjectLine, -1) == '.') {
      $this->errors[] = self::ERROR_PERIOD_END;
    }
  }

  /**
   * Validate if JIRA ticket is attached to message.
   */
  private function getValidateProjectPrefix() {
    $prefix = $this->getProjectPrefix();
    return preg_match("/^$prefix-[0-9]+ {1}[a-zA-Z0-9]{1}.*/", $this->subjectLine);
  }

  /**
   * Validates a git commit message.
   *
   * @command validate:git:commit-msg
   */
  public function validateCommitMsg($message_file) {
    $message_file_path = $this->repoRoot . '/' . $message_file;
    $message = file_get_contents($message_file_path);

    $this->message = $message;
    $this->subjectLine = strpos($message, "\n") !== FALSE ? strstr($message, "\n", TRUE) : $message;

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
      throw new \Exception(self::ERROR_INVALID_COMMIT_MESSAGE);
    }

    // Validate optional project prefix.
    if (!$this->getValidateProjectPrefix()) {
      if (!$this->getAllowsPrefixLessCommit()) {
        if (!$this->confirm(self::WARN_MISSING_JIRA_INFO)) {
          $this->say(self::SAMPLE_VALID_COMMIT_MESSAGE);
          throw new \Exception(self::ERROR_MISSING_JIRA_INFO);
        }
      }
    }
  }

}
