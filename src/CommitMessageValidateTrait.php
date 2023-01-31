<?php

namespace Dockworker;

/**
 * Provides a trait to validate git commit messages based on standards.
 */
trait CommitMessageValidateTrait {

  /**
   * Any errors thrown during the validation.
   *
   * @var string[]
   */
  private array $errors = [];

  /**
   * The maximum length of the commit message subject line.
   *
   * @var int
   */
  private int $maxCommitMessageLength = 70;

  /**
   * The subject line of the git commit message.
   *
   * @var string
   */
  private string $message;

  /**
   * The entire git commit message.
   *
   * @var string
   */
  private string $subjectLine;

  /**
   * Validates the commit message against a maximum width constraint.
   */
  public function getValidateMessageWidth() {
    $lines = explode("\n", $this->message);
    $max = max(array_map('strlen', $lines));
    if ($max > 72) {
      $this->errors[] = sprintf('Wrap the body at 72 characters, current max: %d', $max);
    }
  }

  /**
   * Validates the commit message against body separator constraint.
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
   * Validates the commit message subject against string length constraint.
   */
  private function getValidateSubjectLength() {
    $length = strlen((string) $this->subjectLine);
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
   * Validates the commit message subject against character case constraints.
   */
  private function getValidateSubjectCapital() {
    if (ucfirst($this->subjectLine) != $this->subjectLine) {
      $this->errors[] = 'Capitalize subject line';
    }
    return;
  }

  /**
   * Validates if the commit message is empty.
   */
  private function getValidateIsEmpty() {
    if (trim($this->subjectLine) == '') {
      $this->errors[] = 'Subject line cannot be empty';
    }
  }

  /**
   * Validates if the commit message subject ends in a full stop.
   */
  private function getValidatePeriodEnding() {
    if (substr($this->subjectLine, -1) == '.') {
      $this->errors[] = 'Do not end the subject line with period';
    }
  }

  /**
   * Validates if the commit message is structured to reference a JIRA project.
   *
   * @return bool
   *   TRUE if a JIRA ticket ID is attached to the subject. FALSE otherwise.
   */
  private function getValidateProjectPrefix() : bool {
    // Regex for all project prefixes, including IN (infrastructure) as a default.
    $prefixes = '(IN|' . implode('|', $this->getProjectPrefixes()) . ')';
    return preg_match("/^$prefixes-[0-9]+ {1}[a-zA-Z0-9]{1}.*/", (string) $this->subjectLine);
  }

}
