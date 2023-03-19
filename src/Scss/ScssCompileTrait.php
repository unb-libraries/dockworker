<?php

namespace Dockworker\Scss;

use Dockworker\Cli\CliCommandTrait;
use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to compile SCSS files into CSS.
 */
trait ScssCompileTrait
{
    use CliCommandTrait;

    /**
     * The path to the SCSS compiler.
     *
     * @var string
     */
    private string $scssCompiler;

    /**
     * Compiles a SCSS file to CSS.
     *
     * @param string $source_path
     *   The SCSS source file.
     * @param string $target_path
     *   The CSS target path.
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param string|null $cwd
     *   The working directory to use for the command.
     */
    protected function compileScss(
        string $source_path,
        string $target_path,
        DockworkerIO $io,
        string|null $cwd
    ): void {
        $this->executeCliCommand(
            [
                $this->scssCompiler,
                '--style=compressed',
                $source_path,
                $target_path
            ],
            $io,
            $cwd,
            '',
            "Compiling $source_path to $target_path...",
            false
        );
    }

    /**
     * Sets the path to the SCSS compiler.
     *
     * @param string $path
     *   The path to the compiler.
     */
    protected function setScssCompiler(string $path): void
    {
        $this->scssCompiler = $path;
    }
}
