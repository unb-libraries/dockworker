<?php

namespace Dockworker;

use Consolidation\AnnotatedCommand\AnnotationData;
use Dockworker\DockworkerIO;
use Robo\Common\InputAwareTrait;
use Robo\Common\OutputAwareTrait;
use Robo\Symfony\ConsoleIO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides IO methods for Dockworker applications.
 */
trait DockworkerIOTrait
{
    protected DockworkerIO $dockworkerIO;

    /**
     * Registers IO.
     *
     * @hook pre-init
     */
    public function initDockworkerIO(): void {
        $this->dockworkerIO = new DockworkerIO(
            $this->io()->input(),
            $this->io()->output()
        );
    }

}
