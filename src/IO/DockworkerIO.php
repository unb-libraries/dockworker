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
     * Displays a table.
     *
     * @param array $headers
     *   The table headers.
     * @param array $rows
     *   The table rows.
     * @param string $title
     *   The title to display before the table.
     * @param string $section
     *   A section header to display before the table.
     * @param string $description
     *   A description to display after the table.
     */
    public function setDisplayTable(
        array $headers,
        array $rows,
        string $title = '',
        string $section = '',
        string $description = ''
    ): void {
        if (!empty($title)) {
            $this->title($title);
        }
        if (!empty($section)) {
            $this->section($section);
        }
        $this->table(
            $headers,
            $rows
        );
        if (!empty($description)) {
            $this->block($description);
        }
    }

    /**
     * Displays a table and prompts the user to select one of the rows.
     *
     * @param array $headers
     *   The table headers.
     * @param array $rows
     *   The table rows.
     * @param string $return_column_key
     *   The key of the row array whose value you wish to return.
     * @param string $title
     *   The title to display before the table.
     * @param string $section
     *   A section header to display before the table.
     * @param string $description
     *   A description to display after the table.
     * @param string $prompt
     *   The prompt to display to the user.
     * @param int|null $default
     *   The default value to use in the prompt.
     *
     * @return string
     *   The value of the selected row's $return_column_key column.
     */
    public function setChooseOptionTable(
        array $headers,
        array $rows,
        string $return_column_key,
        string $title = '',
        string $section = '',
        string $description = '',
        string $prompt = 'Enter the ID of the item you wish to select:',
        ?int $default = null
    ): string {
        array_unshift($headers, 'ID');
        $this->addIdColumnToRows($rows);
        $this->displayTable(
            $headers,
            $rows,
            $title,
            $section,
            $description
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

    /**
     * Asks the user for a value, but only permit answers from a list of values.
     *
     * @param string $prompt
     *   The prompt to display to the user.
     * @param array $allowed_values
     *   The list of permitted values.
     * @param string|null $default_value
     *   The default value to use in the prompt.
     *
     * @return string
     *   The value entered by the user.
     */
    public function askRestricted(
        string $prompt,
        array $allowed_values = [],
        ?string $default_value = null
    ): string {
        $value = null;
        while (!in_array($value, $allowed_values)) {
            $value = $this->ask($prompt, $default_value);
            if (!in_array($value, $allowed_values)) {
                $this->warning(
                    'Invalid value selected. Permitted values are: ' .
                    implode(', ', $allowed_values) . '.'
                );
            }
        }
        return $value;
    }
}
