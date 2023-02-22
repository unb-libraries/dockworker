<?php

namespace Dockworker\Cli;

use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to interact with Jira for this dockworker application.
 */
trait KubectlCliTrait
{
    use CliToolTrait;

    /**
     * Registers kubectl as a required CLI tool.
     */
    public function registerKubectlCliTool(DockworkerIO $io): void
    {
        $file_path = "$this->applicationRoot/vendor/unb-libraries/dockworker/data/cli-tools/kubectl.yml";
        $this->registerCliToolFromYaml($file_path, $io);
    }
}
