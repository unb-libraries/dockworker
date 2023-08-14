<?php

namespace Dockworker\Cli;

use Dockworker\Cli\CliCommandTrait;
use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to interact with the rsync CLI application.
 *
 * @INTERNAL This trait is intended only to be used by Dockworker commands. It
 * references user properties which are not in its own scope.
 */
trait RSyncCliTrait
{
    use CliToolTrait;
    use CliCommandTrait;

    /**
     * Registers rsync as a required CLI tool.
     */
    protected function registerRSyncCliTool(DockworkerIO $io): void
    {
        $file_path = "$this->applicationRoot/vendor/unb-libraries/dockworker/data/cli-tools/rsync.yml";
        $this->registerCliToolFromYaml($file_path, $io);
    }
}
