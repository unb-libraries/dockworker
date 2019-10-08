<?php

namespace Dockworker;

/**
 * Class for CommitMessageValidateTrait.
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
   * Validate the maximum message width.
   */
  public function getValidateMessageWidth() {
    $lines = explode("\n", $this->message);
    $max = max(array_map('strlen', $lines));
    if ($max > 72) {
      $this->errors[] = sprintf('Wrap the body at 72 characters, current max: %d', $max);
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
      $this->errors[] = 'Separate subject from body with a blank line';
    }
  }

  /**
   * Validate if subject exceeds maximum length.
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
    return NULL;
  }

  /**
   * Validate if subject line has a first capitalized character.
   */
  private function getValidateSubjectCapital() {
    if (ucfirst($this->subjectLine) != $this->subjectLine) {
      $this->errors[] = 'Capitalize subject line';
    }
    return NULL;
  }

  /**
   * Validate if subject is empty.
   */
  private function getValidateIsEmpty() {
    if (trim($this->subjectLine) == '') {
      $this->errors[] = 'Subject line cannot be empty';
    }
  }

  /**
   * Validate if subject line ends in a period.
   */
  private function getValidatePeriodEnding() {
    if (substr($this->subjectLine, -1) == '.') {
      $this->errors[] = 'Do not end the subject line with period';
    }
  }

  /**
   * Validate if JIRA ticket is attached to message.
   */
  private function getValidateProjectPrefix() {
    $prefix = $this->getProjectPrefix();
    return
      preg_match("/^$prefix-[0-9]+ {1}[a-zA-Z0-9]{1}.*/", $this->subjectLine) ||
      preg_match("/^IN-[0-9]+ {1}[a-zA-Z0-9]{1}.*/", $this->subjectLine);
  }

}
