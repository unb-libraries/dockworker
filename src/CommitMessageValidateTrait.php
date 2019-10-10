<?php

namespace Dockworker;

/**
 * Provides methods to validate a git commit message.
 */
trait CommitMessageValidateTrait {

  /**
   * Any errors thrown during the validation.
   *
   * @var string[]
   */
  private $errors = [];

  /**
   * The maximum length of the commit message subject line.
   *
   * @var string
   */
  private $maxCommitMessageLength = 70;

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
   * Validates the maximum commit message width.
   */
  public function getValidateMessageWidth() {
    $lines = explode("\n", $this->message);
    $max = max(array_map('strlen', $lines));
    if ($max > 72) {
      $this->errors[] = sprintf('Wrap the body at 72 characters, current max: %d', $max);
    }
  }

  /**
   * Validates if commit message body is separated from the subject by a space.
   */
  public function getValidateBodySeparation() {
    $lines = explode("\n", $this->message);
    if (count($lines) < 2) {
      return;
    }
    if ('' !== trim($lines[1])) {
      $this->errors[] = 'Separate subject from body with a blank line';
    }
  }

  /**
   * Validates if the commit message subject exceeds the maximum length.
   */
  private function getValidateSubjectLength() {
    $length = strlen($this->subjectLine);
    if ($length > $this->maxCommitMessageLength) {
      $this->errors[] = sprintf(
        'Limit the subject line to ' .
        $this->maxCommitMessageLength .
        ' characters, %d present',
        $length
      );
    }
    return;
  }

  /**
   * Validates if the commit subject has its first character capitalized.
   */
  private function getValidateSubjectCapital() {
    if (ucfirst($this->subjectLine) != $this->subjectLine) {
      $this->errors[] = 'Capitalize subject line';
    }
    return;
  }

  /**
   * Validates if the commit subject is empty.
   */
  private function getValidateIsEmpty() {
    if (trim($this->subjectLine) == '') {
      $this->errors[] = 'Subject line cannot be empty';
    }
  }

  /**
   * Validates if the commit subject line ends in a period.
   */
  private function getValidatePeriodEnding() {
    if (substr($this->subjectLine, -1) == '.') {
      $this->errors[] = 'Do not end the subject line with period';
    }
  }

  /**
   * Validates if a JIRA ticket is attached to the commit subject.
   *
   * @return bool
   *   TRUE if a JIRA ticket ID is attached to the subject. FALSE otherwise.
   */
  private function getValidateProjectPrefix() {
    $prefix = $this->getProjectPrefix();
    return
      preg_match("/^$prefix-[0-9]+ {1}[a-zA-Z0-9]{1}.*/", $this->subjectLine) ||
      preg_match("/^IN-[0-9]+ {1}[a-zA-Z0-9]{1}.*/", $this->subjectLine);
  }

}
