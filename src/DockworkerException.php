<?php

namespace Dockworker;

use Exception;

/**
 * Provides an exception class for Dockworker commands.
 */
class DockworkerException extends Exception implements \Stringable {

  /**
   * {@inheritdoc}
   */
  public function __construct($message, $code = 0, Exception $previous = NULL) {
    parent::__construct($message, $code, $previous);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return self::class . ": [{$this->code}]: {$this->message}\n";
  }

}
