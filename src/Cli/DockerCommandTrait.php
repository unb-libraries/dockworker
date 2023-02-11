<?php

namespace Dockworker\Cli;

use Consolidation\AnnotatedCommand\AnnotationData;
use Dockworker\CliCommand;
use Dockworker\DockerCommand;
use Dockworker\CliToolTrait;
use Robo\Symfony\ConsoleIO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
    public function registerDockerAsCliTool(
        InputInterface $input,
        OutputInterface $output,
        AnnotationData $annotationData
    ): void {
        $io = new ConsoleIO($input, $output);
        $file_path = "$this->applicationRoot/vendor/unb-libraries/dockworker/data/cli-tools/docker.yml";
        $this->registerCliToolFromYaml($io, $file_path);
    }

    protected function dockerCommand(array $command, string $description): CliCommand
    {
        array_unshift($command, $this->cliTools['docker']);
        return new CliCommand(
            $command,
            $description
        );
    }

    protected function dockerRun(array $command, string $description): void
    {
        $this->dockerCommand($command, $description)->runTty();
    }
}
