<?php

namespace Dockworker;

use Exception;
use Stringable;

/**
 * Provides an exception class for Dockworker commands.
 */
class DockworkerException extends Exception implements Stringable
{
    /**
     * Constructor.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(
        string $message,
        int $code = 0,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return self::class . ": [$this->code]: $this->message\n";
    }
}
