<?php

namespace Dockworker\IO;

use Robo\Symfony\ConsoleIO;

/**
 * Provides IO methods for Dockworker applications.
 */
class DockworkerIO extends ConsoleIO
{
    /**
     * Displays a warning that a destructive action is about to be performed.
     *
     * @param string $prompt
     *   The prompt to display to the user.
     */
    protected function warnConfirmExitDestructiveAction(string $prompt): void
    {
        if (
            $this->warnConfirmDestructiveAction(
                $prompt
            ) !== true
        ) {
            exit(0);
        }
    }

    /**
     * Determines if the user wishes to proceed with a destructive action.
     *
     * @param string $prompt
     *   The prompt to display to the user.
     *
     * @return bool
     *   TRUE if the user wishes to continue. False otherwise.
     */
    protected function warnConfirmDestructiveAction(string $prompt): bool
    {
        $this->warnDestructiveAction();
        return ($this->confirm($prompt));
    }

    /**
     * Warns the user that a destructive action is about to be performed.
     */
    protected function warnDestructiveAction(): void
    {
        $this->warning('Destructive, Irreversible Actions Ahead!');
    }

    /**
     * Displays a table and prompts the user to select one of the rows.
     *
     * @param string $headers
     *   The table headers.
     * @param string $rows
     *   The table rows.
     * @param string $return_column_key
     *   The key of the row array whose value you wish to return.
     * @param string $title
     *   The title to display before the table.
     * @param string $prompt
     *   The prompt to display to the user.
     * @param int|null $default
     *   The default value to use in the prompt.
     *
     * @return string
     *   The value of the selected row's $return_column_key column.
     */
    protected function chooseOptionTable(
        string $headers,
        string $rows,
        string $return_column_key,
        string $title = '',
        string $prompt = 'Enter the ID of the item you wish to select:',
        ?int $default = null
    ) {
        array_unshift($headers, 'ID');
        if (!empty($title))) {
            $this->title($title);
        }
        $this->table(
            $headers,
            $this->addIdColumnToRows($rows)
        );
        $chosen_item = null;
        while (!isset($rows[$chosen_item - 1])) {
            $chosen_item = $this->ask(
                $prompt,
                $default == null ? null : $default + 1
            );
            if (!isset($rows[$chosen_item - 1])) {
                $this->warning('Invalid ID selected.');
            }
        }
        return $rows[$chosen_item - 1][$return_column_key];
    }

    /**
     * Adds an incrementing ID column to a table row.
     *
     * @param array $rows
     *   The table rows.
     */
    private function addIdColumnToRows(array &$rows): void
    {
        $id = 1;
        foreach ($rows as &$row) {
            array_unshift($row, $id);
            $id++;
        }
    }
}
