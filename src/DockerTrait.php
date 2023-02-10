<?php

namespace Dockworker;

use Consolidation\AnnotatedCommand\AnnotationData;
use Robo\Symfony\ConsoleIO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides methods to interact with docker.
 */
trait DockerTrait
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
}
