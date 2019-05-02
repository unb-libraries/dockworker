<?php

namespace Dockworker;

use Symfony\Component\Finder\Finder;
use Dockworker\Robo\Plugin\Commands\DockworkerApplicationCommands;

/**
 * Defines trait for building SCSS files
 */
trait ScssCompileTrait {

  private $scssCompiler = NULL;

  /**
   * Compile SCSS to CSS.
   */
  protected function compileScss($source_path, $target_path) {
    $cmd = "$this->scssCompiler lint -f crunched $source_path > $target_path";
    $return_code = 0;
    system($cmd, $return_code);
    return $return_code;
  }

  protected function setScssCompiler($path) {
    $this->scssCompiler = $path;
  }

}
