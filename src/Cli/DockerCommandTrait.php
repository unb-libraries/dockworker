<?php

namespace Dockworker\Cli;

use Dockworker\CliToolTrait;
use Dockworker\Cli\DockerCommand;

/**
 * Provides methods to interact with docker.
 */
trait DockerCommandTrait
{
    use CliToolTrait;

    /**
     * Registers docker as a required CLI tool.
     *
     * @hook interact @docker
     */
    public function registerDockerAsCliTool(): void
    {
        $file_path = "$this->applicationRoot/vendor/unb-libraries/dockworker/data/cli-tools/docker.yml";
        $this->registerCliToolFromYaml($file_path);
    }

    protected function dockerCommand(array $command, string $description): DockerCommand
    {
        array_unshift($command, $this->cliTools['docker']);
        return new DockerCommand(
            $command,
            $description
        );
    }

    protected function dockerRun(array $command, string $description): void
    {
        $this->dockerCommand($command, $description)->runTty();
    }
}
