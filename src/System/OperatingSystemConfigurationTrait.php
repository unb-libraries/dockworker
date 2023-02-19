<?php

namespace Dockworker\System;

/**
 * Provides methods to interact with a local filesystem.
 */
trait OperatingSystemConfigurationTrait
{
    protected function getSedInlineInvocation(): string
    {
        if (PHP_OS == 'Darwin') {
            return 'sed -i ""';
        } else {
            return 'sed -i';
        }
    }
}
