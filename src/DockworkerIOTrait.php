<?php

namespace Dockworker;

use Robo\Symfony\ConsoleIO;

/**
 * Provides IO methods for Dockworker applications.
 */
trait DockworkerIOTrait
{
    /**
     * Prints a standard, un-styled message to the CLI.
     *
     * @param \Robo\Symfony\ConsoleIO $io
     *   The console IO.
     * @param string[] $message
     *   The message to print.
     */
    protected function dockworkerSay(ConsoleIO $io, array $message): void
    {
        $io->text($message);
    }

    /**
     * Prints an emphasized 'notice' message to the CLI.
     *
     * @param \Robo\Symfony\ConsoleIO $io
     *   The console IO.
     * @param string $message
     *   The message to print.
     */
    protected function dockworkerNotice(ConsoleIO $io, string $message): void
    {
        $io->say($message);
    }

    /**
     * Prints a 'note' level message to the CLI.
     *
     * @param \Robo\Symfony\ConsoleIO $io
     *   The console IO.
     * @param string[] $message
     *   The message to print.
     */
    protected function dockworkerNote(ConsoleIO $io, array $message): void
    {
        $io->note($message);
    }

    /**
     * Prints a 'warning' level message to the CLI.
     *
     * @param \Robo\Symfony\ConsoleIO $io
     *   The console IO.
     * @param string[] $message
     *   The message to print.
     */
    protected function dockworkerWarn(ConsoleIO $io, array $message): void
    {
        $io->warning($message);
    }

    /**
     * Displays a message to the CLI an asks a user to confirm.
     *
     * @param \Robo\Symfony\ConsoleIO $io
     *   The console IO.
     * @param string $message
     *   The message to print.
     */
    protected function dockworkerConfirm(
        ConsoleIO $io,
        string $message,
        $default = false
    ): bool {
        return $io->confirm($message, $default);
    }

    /**
     * Asks a query to the end user.
     *
     * @param \Robo\Symfony\ConsoleIO $io
     *   The console IO.
     * @param string $query
     *   The query to print.
     * @param string $default
     *   Optional. The default response to the query.
     */
    protected function dockworkerAsk(
        ConsoleIO $io,
        string $query,
        string $default = ''
    ): string {
        return $io->ask($query, $default);
    }

    /**
     * Prints an unordered list of elements to the CLI.
     *
     * @param \Robo\Symfony\ConsoleIO $io
     *   The console IO.
     * @param string[] $list
     *   The items to display within the list.
     */
    protected function dockworkerListing(ConsoleIO $io, array $list): void
    {
        $io->listing($list);
    }

    /**
     * Prints lines as an output block to the CLI.
     *
     * @param \Robo\Symfony\ConsoleIO $io
     *   The console IO.
     * @param string[] $lines
     *   The lines to print within the block.
     */
    protected function dockworkerOutputBlock(ConsoleIO $io, array $lines): void
    {
        $io->block($lines);
    }

    /**
     * Prints a message as the title of a command's output.
     *
     * @param \Robo\Symfony\ConsoleIO $io
     *   The console IO.
     * @param string $title
     *   The message to print.
     */
    protected function dockworkerTitle(ConsoleIO $io, string $title): void
    {
        $io->title($title);
    }

    /**
     * Prints a message as the subtitle of a command's output.
     *
     * @param \Robo\Symfony\ConsoleIO $io
     *   The console IO.
     * @param string $subtitle
     *   The message to print.
     */
    protected function dockworkerSubTitle(ConsoleIO $io, string $subtitle): void
    {
        $io->section($subtitle);
    }
}
