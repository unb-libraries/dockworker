<?php

namespace Dockworker;

use Robo\Common\IO;

/**
 * Provides IO methods for Dockworker applications.
 */
trait DockworkerIO
{
    use IO;

    /**
     * Prints a standard message to the CLI.
     *
     * @param string[] $message
     *   The message to print.
     */
    protected function dockworkerSay(array $message): void
    {
        $this->io()->text($message);
    }

    /**
     * Prints a 'note' level message to the CLI.
     *
     * @param string[] $message
     *   The message to print.
     */
    protected function dockworkerNote(array $message): void
    {
        $this->io()->note($message);
    }

    /**
     * Prints an unordered list of elements to the CLI.
     *
     * @param string[] $list
     *   The items to display as a list.
     */
    protected function dockworkerListing(array $list): void
    {
        $this->io()->listing($list);
    }

    /**
     * Prints a message as the title of a command's output.
     *
     * @param string $title
     *   The message to print.
     */
    protected function dockworkerTitle(string $title): void
    {
        $this->io()->title($title);
    }

    /**
     * Prints a message as the subtitle of a command's output.
     *
     * @param string $subtitle
     *   The message to print.
     */
    protected function dockworkerSubTitle(string $subtitle): void
    {
        $this->io()->section($subtitle);
    }
}
