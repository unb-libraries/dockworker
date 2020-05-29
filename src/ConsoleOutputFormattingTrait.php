<?php

namespace Dockworker;

use Symfony\Component\Console\Helper\Table;

/**
 * Provides methods to format Symfony console output.
 */
trait ConsoleOutputFormattingTrait {

  /**
   * Prints a table of values to the console.
   *
   * @param string $title
   *   The title to print above the table.
   * @param string[] $headers
   *   The headers to use for the table.
   * @param string[] $rows
   *   The row values.
   */
  private function printConsoleTable($title, $headers, $rows) {
    $this->io()->title($title);
    $table = new Table($this->output());
    $table
      ->setHeaders($headers)
      ->setRows($rows);
    $table->render();
  }

}
