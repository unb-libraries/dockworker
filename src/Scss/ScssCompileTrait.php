<?php

namespace Dockworker\Scss;

use Dockworker\Cli\SassCliTrait;
use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to compile SCSS files into CSS.
 */
trait ScssCompileTrait
{
    use SassCliTrait;

    /**
     * Compiles a SCSS file to CSS.
     *
     * @param string $source_path
     *   The SCSS source file.
     * @param string $target_path
     *   The CSS target path.
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     */
    protected function compileScss(
        string $source_path,
        string $target_path,
        DockworkerIO $io
    ): void {
        $this->sassRun(
            [
                '--style=compressed',
                $source_path,
                $target_path
            ],
            "Compiling $source_path to $target_path...",
            $io
        );
    }
}
