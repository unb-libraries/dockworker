<?php

namespace UnbLibraries\DockWorker\Robo;

use Symfony\Component\Finder\Finder;
use UnbLibraries\DockWorker\Robo\DockWorkerCommand;

/**
 * Defines commands in the DockWorkerDrupalCodeCommand namespace.
 */
class DockWorkerDrupalCodeCommand extends DockWorkerCommand {

  /**
   * The modules within the current repository.
   *
   * @var array
   */
  protected $drupalModules = [];

  /**
   * The themes within the current repository.
   *
   * @var array
   */
  protected $drupalThemes = [];

  /**
   * Set the current custom modules and themes in the current repository.
   *
   * @hook init
   */
  public function getCustomModulesThemes() {
    $projects = new Finder();
    $projects->files()->in($this->repoRoot . '/custom/')->files()->name('*info.yml');;

    foreach ($projects as $project) {
      $type = explode('/', $project->getRelativePath())[0];
      $name = $project->getBasename('.info.yml');
      if ($type == 'modules') {
        $this->drupalModules[] = $project;
      }
      if ($type == 'themes') {
        $this->drupalThemes[] = $project;
      }
    }
  }

}
