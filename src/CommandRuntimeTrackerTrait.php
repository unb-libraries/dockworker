<?php

namespace Dockworker;

use DateTime;

/**
 * Provides methods to track the runtime of a command.
 */
trait CommandRuntimeTrackerTrait
{
  /**
   * The timestamp the command was started.
   *
   * @var string
   */
    protected string $commandStartTime;

    /**
     * Should the command display its total runtime when complete?
     *
     * @var bool
     */
    protected bool $displayCommandRunTime = false;

    /**
     * Sets the 'start time' of the current command.
     */
    protected function setCommandStartTime(): void
    {
        $this->commandStartTime = time();
    }

    /**
     * Gets the time elapsed since the command started.
     *
     * @return string
     *   The command's total run time, formatted for humans.
     */
    public function getTimeSinceCommandStart(): string
    {
        date_default_timezone_set('UTC');
        $start = new DateTime("@$this->commandStartTime");
        $end = new DateTime();
        $diff = $start->diff($end);
        return $diff->format('%H:%I:%S');
    }

    /**
     * Enable this command's total run time display upon completion.
     */
    protected function enableCommandRunTimeDisplay(): void
    {
        $this->displayCommandRunTime = true;
    }

    /**
     * Disables this command's total run time display upon completion.
     */
    protected function disableCommandRunTimeDisplay(): void
    {
        $this->displayCommandRunTime = false;
    }
}
