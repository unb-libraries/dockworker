<?php

namespace Dockworker\Core;

use DateTime;
use Dockworker\IO\DockworkerIOTrait;

/**
 * Provides methods to track the runtime of a command.
 */
trait CommandRuntimeTrackerTrait
{
    use DockworkerIOTrait;

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
     * Sets the command runtime start.
     *
     * @hook init
     */
    public function initCommandStartTime(): void
    {
        $this->setCommandStartTime();
    }

    /**
     * Sets the 'start time' of the current command.
     */
    protected function setCommandStartTime(): void
    {
        $this->commandStartTime = time();
    }

    /**
     * Trigger the display of the command's total run time.
     *
     * @hook post-process
     */
    public function triggerDisplayCommandRunTime(): void
    {
        $this->displayCommandRunTime();
    }

    /**
     * Displays the command's total run time.
     */
    protected function displayCommandRunTime(): void
    {
        if ($this->displayCommandRunTime) {
            $this->dockworkerIO->note(
                [
                    sprintf(
                        'Command Runtime: %s',
                        $this->getTimeSinceCommandStart()
                    )
                ]
            );
        }
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
