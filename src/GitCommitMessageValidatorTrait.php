<?php

namespace Dockworker;

/**
 * Provides methods to validate git commit messages based on standards.
 */
trait GitCommitMessageValidatorTrait
{
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
    public function getValidateMessageWidth(): void
    {
        $lines = explode("\n", $this->message);
        $max = max(array_map('strlen', $lines));
        if ($max > 72) {
            $this->errors[] = sprintf('Wrap the body at 72 characters, current max: %d', $max);
        }
    }

    /**
     * Validates the commit message against a body separator constraint.
     */
    public function getValidateBodySeparation(): void
    {
        $lines = explode("\n", $this->message);
        if (count($lines) < 2) {
            return;
        }
        if ('' !== trim($lines[1])) {
            $this->errors[] = 'Separate subject from body with a blank line';
        }
    }

    /**
     * Validates the commit message subject against a string length constraint.
     */
    private function getValidateSubjectLength(): void
    {
        $length = strlen($this->subjectLine);
        if ($length > $this->maxCommitMessageLength) {
            $this->errors[] = sprintf(
                'Limit the subject line to %d characters, %d present',
                $this->maxCommitMessageLength,
                $length
            );
        }
    }

    /**
     * Validates the commit message subject against character case constraints.
     */
    private function getValidateSubjectCapital(): void
    {
        if (ucfirst($this->subjectLine) != $this->subjectLine) {
            $this->errors[] = 'Capitalize subject line';
        }
    }

    /**
     * Validates if the commit message is empty.
     */
    private function getValidateIsEmpty(): void
    {
        if (trim($this->subjectLine) == '') {
            $this->errors[] = 'Subject line cannot be empty';
        }
    }

    /**
     * Validates if the commit message subject ends in a full stop.
     */
    private function getValidatePeriodEnding(): void
    {
        if (str_ends_with($this->subjectLine, '.')) {
            $this->errors[] = 'Do not end the subject line with period';
        }
    }

    /**
     * Validates if the commit message is structured to reference a JIRA issue.
     *
     * @param array $project_keys
     *   An array of Jira project prefixes acceptable for this message.
     *
     * @return bool
     *   True if a JIRA ticket ID is attached to the subject. False otherwise.
     */
    private function getValidateProjectPrefix(array $project_keys): bool
    {
        $prefixes = '(' . implode('|', $project_keys) . ')';
        return preg_match("/^$prefixes-[0-9]+ {1}[a-zA-Z0-9]{1}.*/", (string) $this->subjectLine);
    }
}
