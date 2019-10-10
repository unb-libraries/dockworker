<?php

namespace Dockworker;

use Exception;

/**
 * Provides an exception class for Dockworker commands.
 */
class DockworkerException extends Exception {

  /**
   * {@inheritdoc}
   */
  public function __construct($message, $code = 0, Exception $previous = null) {
    parent::__construct($message, $code, $previous);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
  }

}
