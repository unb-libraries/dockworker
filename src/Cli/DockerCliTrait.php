<?php

namespace Dockworker\Cli;

/**
 * Provides methods to interact with docker.
 */
trait DockerCliTrait
{
    use CliToolTrait;

    /**
     * Registers docker as a required CLI tool.
     *
     * @hook interact
     */
    public function registerDockerAsCliTool(): void
    {
        $file_path = "$this->applicationRoot/vendor/unb-libraries/dockworker/data/cli-tools/docker.yml";
        $this->registerCliToolFromYaml($file_path);
    }

    protected function dockerCli(array $command, string $description): DockerCli
    {
        array_unshift($command, $this->cliTools['docker']);
        return new DockerCli(
            $command,
            $description
        );
    }

    protected function dockerRun(array $command, string $description): void
    {
        $this->dockerCli($command, $description)
            ->setWorkingDirectory($this->applicationRoot)
            ->runTty($this->dockworkerIO);
    }

    protected function dockerComposeCli(array $command, string $description): DockerCli
    {
        array_unshift(
            $command,
            $this->cliTools['docker'],
            'compose'
        );
        return new DockerCli(
            $command,
            $description
        );
    }

    protected function dockerComposeRun(array $command, string $description): void
    {
        $this->dockerComposeCli($command, $description)
            ->setWorkingDirectory($this->applicationRoot)
            ->runTty($this->dockworkerIO);
    }
}
