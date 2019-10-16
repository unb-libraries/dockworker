<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerException;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;
use Dockworker\GitRepoTrait;

/**
 * Defines the commands used to build a docker image from the repository.
 */
class DockworkerDockerImageCommands extends DockworkerCommands {

  use GitRepoTrait;

  const ERROR_UNCLEAN_REPO = 'Aborted build due to unclean repo';

  /**
   * Builds the docker image for this repository.
   *
   * @param string $tag
   *   The tag to build.
   * @param string[] $opts
   *   An array of options to pass to the builder.
   *
   * @option bool $no-cache
   *   Do not use any cached steps in the build.
   * @option string $cache-from
   *   The image to cache the build from.
   *
   * @command image:build
   * @throws \Exception
   *
   * @return \Robo\ResultData
   */
  public function buildImage($tag = NULL, array $opts = ['no-cache' => FALSE, 'cache-from' => '']) {
    if (!$this->gitRepoIsClean($this->repoRoot)) {
      $continue = $this->confirm('Warning: You are attempting to build an image in a dirty git repo. Would you like to proceed?');
      if (!$continue) {
        throw new DockworkerException(sprintf(self::ERROR_UNCLEAN_REPO));
      }
    }
    $build = $this->taskDockerBuild($this->repoRoot);

    if (!empty($tag)) {
      $build->tag($tag);
    }

    if ($opts['no-cache']) {
      $build->arg('--no-cache');
    }

    if (!empty($opts['cache-from'])) {
      $build->arg('--cache-from');
      $build->arg($opts['cache-from']);
    }

    return $build->run();
  }

}
