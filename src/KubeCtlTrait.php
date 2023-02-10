<?php

namespace Dockworker;

use Consolidation\AnnotatedCommand\AnnotationData;
use Robo\Symfony\ConsoleIO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides methods to interact with Jira for this dockworker application.
 */
trait KubeCtlTrait
{
    use CliToolTrait;

    /**
     * Registers kubectl as a required CLI tool.
     *
     * @hook interact @kubectl
     */
    public function registerKubeCtlCliTool(
        InputInterface $input,
        OutputInterface $output,
        AnnotationData $annotationData
    ): void {
        $io = new ConsoleIO($input, $output);
        $file_path = "$this->applicationRoot/vendor/unb-libraries/dockworker/data/cli-tools/kubectl.yml";
        $this->registerCliToolFromYaml($io, $file_path);
    }

}
