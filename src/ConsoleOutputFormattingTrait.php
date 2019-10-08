<?php

namespace Dockworker;

use Symfony\Component\Console\Helper\Table;

/**
 * Defines trait for formatting console output.
 */
trait ConsoleOutputFormattingTrait {

  /**
   * Print a table of values to the console.
   *
   * @param $title
   * @param $headers
   * @param $values
   */
  private function printConsoleTable($title, $headers, $values) {
    $this->io()->title($title);
    $table = new Table($this->output());
    $table
      ->setHeaders($headers)
      ->setRows($values);
    $table->render();
  }

}
