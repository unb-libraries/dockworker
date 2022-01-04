<?php

namespace Dockworker;

/**
 * Provides methods to compile SCSS files into CSS.
 */
trait ScssCompileTrait {

  private $scssCompiler;

  /**
   * Compiles a SCSS file to CSS.
   *
   * @param string $source_path
   *   The SCSS source file.
   * @param string $target_path
   *   The CSS target path.
   *
   * @return int
   *   The return code of the compile command.
   */
  protected function compileScss($source_path, $target_path) {
    $cmd = "$this->scssCompiler --style=compressed $source_path > $target_path";

    system($cmd, $return_code);
    if ($return_code != 0) {
      throw new DockworkerException("Compile of $source_path to $target_path failed!");
    }

    return $return_code;
  }

  /**
   * Sets the binary to the SCSS compiler.
   *
   * @param string $path
   *   The path to the compiler.
   */
  protected function setScssCompiler($path) {
    $this->scssCompiler = $path;
  }

}
