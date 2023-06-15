<?php

namespace Dockworker\IO;

/**
 * Provides IO methods for Dockworker applications.
 */
trait DockworkerIOTrait
{
    protected DockworkerIO $dockworkerIO;

    /**
     * Registers IO.
     *
     * @hook init
     */
    public function initDockworkerIO(): void
    {
        $this->dockworkerIO = new DockworkerIO(
            // @phpstan-ignore-next-line
            $this->io()->input(),
            // @phpstan-ignore-next-line
            $this->io()->output()
        );
    }
}
