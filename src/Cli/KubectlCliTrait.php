<?php

namespace Dockworker\Cli;

/**
 * Provides methods to interact with Jira for this dockworker application.
 */
trait KubectlCliTrait
{
    use CliToolTrait;

    /**
     * Registers kubectl as a required CLI tool.
     */
    public function registerKubectlCliTool(): void
    {
        $file_path = "$this->applicationRoot/vendor/unb-libraries/dockworker/data/cli-tools/kubectl.yml";
        $this->registerCliToolFromYaml($file_path);
    }
}
