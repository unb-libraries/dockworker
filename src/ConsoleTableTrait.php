<?php

namespace Dockworker;

use Symfony\Component\Console\Helper\Table;

/**
 * Provides methods to display a table in the Symfony console.
 */
trait ConsoleTableTrait {

  /**
   * The output to use.
   *
   * @var object
   */
  protected $consoleTableTraitOutput;

  /**
   * The title to use.
   *
   * @var string
   */
  protected $consoleTableTraitTitle;

  /**
   * The headers to use.
   *
   * @var array
   */
  protected $consoleTableTraitHeaders;

  /**
   * The rows to use.
   *
   * @var array
   */
  protected $consoleTableTraitRows;

  /**
   * Reads the deployment repository image name from config.
   *
   * @throws \Exception
   */
  protected function setDisplayConsoleTable($output, $headers, $rows, $title = '') {
    $this->consoleTableTraitOutput = $output;
    $this->consoleTableTraitHeaders = $headers;
    $this->consoleTableTraitRows = $rows;
    $this->consoleTableTraitTitle = $title;
    $this->renderTable();
  }

  protected function renderTable() {
    $table = new Table($this->consoleTableTraitOutput);
    $table->setHeaders($this->consoleTableTraitHeaders);
    $table->setRows($this->consoleTableTraitRows);
    if (!empty($this->consoleTableTraitTitle)) {
      $this->consoleTableTraitOutput->title($this->consoleTableTraitTitle);
    }
    $table->render();
  }

}
