<?php

namespace Dockworker;

use Dockworker\IO\DockworkerIOTrait;

/**
 * Provides methods to validate git commit messages based on standards.
 */
trait GitCommitMessageValidatorTrait
{
    use DockworkerIOTrait;

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
     * The sample valid commit message.
     *
     * @var string[]
     */
    private array $sampleCommitMessage = [
        'Example Valid Commit Message:',
        'HERB-135 Add the new picture field to the article feature'
    ];

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
        if ($max > $this->maxCommitMessageLength) {
            $this->errors[] = sprintf(
                'Body line length (%d) exceeds %d characters',
                $max,
                $this->maxCommitMessageLength
            );
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
            $this->errors[] = 'Subject line is not separated from body with a blank line';
        }
    }

    /**
     * Displays a sample valid commit message.
     */
    protected function showSampleCommitMessage(): void
    {
        $this->dockworkerIO->note(
            $this->sampleCommitMessage
        );
    }

    /**
     * Validates the commit message subject against a string length constraint.
     */
    private function getValidateSubjectLength(): void
    {
        $length = strlen($this->subjectLine);
        if ($length > $this->maxCommitMessageLength) {
            $this->errors[] = sprintf(
                'Subject line length (%d) exceeds %d characters',
                $length,
                $this->maxCommitMessageLength
            );
        }
    }

    /**
     * Validates the commit message subject against character case constraints.
     */
    private function getValidateSubjectCapital(): void
    {
        if (ucfirst($this->subjectLine) != $this->subjectLine) {
            $this->errors[] = 'Message component of subject line does not start with a capital letter';
        }
    }

    /**
     * Validates if the commit message is empty.
     */
    private function getValidateIsEmpty(): void
    {
        if (trim($this->subjectLine) == '') {
            $this->errors[] = 'Subject line is empty';
        }
    }

    /**
     * Validates if the commit message subject ends in a full stop.
     */
    private function getValidatePeriodEnding(): void
    {
        if (str_ends_with($this->subjectLine, '.')) {
            $this->errors[] = 'Subject line ends with a period';
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
        return preg_match("/^$prefixes-[0-9]+ {1}[a-zA-Z0-9]{1}.*/", $this->subjectLine);
    }
}
