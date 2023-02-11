<?php

namespace Dockworker;

use Consolidation\AnnotatedCommand\AnnotationData;
use Robo\Common\InputAwareTrait;
use Robo\Common\OutputAwareTrait;
use Robo\Symfony\ConsoleIO;
use Dockworker\DockworkerIO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides IO methods for Dockworker applications.
 */
trait DockworkerIOTrait
{
    use InputAwareTrait;
    use OutputAwareTrait;

    /**
     * Registers kubectl as a required CLI tool.
     *
     * @hook pre-init
     */
    public function initDockworkerIO(): void {
        $this->dockworkerIO = new DockworkerIO($this->input(), $this->output());
    }

}
