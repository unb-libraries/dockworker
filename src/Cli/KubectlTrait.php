<?php

namespace Dockworker\Cli;

/**
 * Provides methods to interact with Jira for this dockworker application.
 */
trait KubectlTrait
{
    use CliToolTrait;

    /**
     * Registers kubectl as a required CLI tool.
     *
     * @hook interact @kubectl
     */
    public function registerKubectlCliTool(): void
    {
        $file_path = "$this->applicationRoot/vendor/unb-libraries/dockworker/data/cli-tools/kubectl.yml";
        $this->registerCliToolFromYaml($file_path);
    }
}
