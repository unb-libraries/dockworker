<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerException;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;
use Dockworker\GitRepoTrait;
use Dockworker\DockerImageTrait;

/**
 * Defines the commands used to build a docker image from the repository.
 */
class DockworkerDockerImageBuildCommands extends DockworkerCommands {

  use DockerImageTrait;
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
   * @option bool $allow-dirty
   *   Skips the warning prompt if the git repository is dirty.
   *
   * @command image:build
   * @throws \Exception
   *
   * @return \Robo\ResultData
   *
   * @dockerimage
   */
  public function buildImage($tag = NULL, array $opts = ['no-cache' => FALSE, 'cache-from' => '', 'allow-dirty' => FALSE]) {
    $this->io()->title("Building {$this->dockerImageName}:$tag");
    if (!$this->gitRepoIsClean($this->repoRoot) && !$opts['allow-dirty']) {
      $continue = $this->confirm('Warning: You are attempting to build an image in a dirty git repo. Would you like to proceed?');
      if (!$continue) {
        throw new DockworkerException(sprintf(self::ERROR_UNCLEAN_REPO));
      }
    }

    $build = $this->taskDockerBuild($this->repoRoot);

    // Determine the tag to use.
    if (!empty($tag)) {
      $build->tag("{$this->dockerImageName}:$tag");
    }
    else {
      $build->tag("{$this->dockerImageName}:latest");
    }

    // Build with no caching.
    if ($opts['no-cache']) {
      $build->arg('--no-cache');
    }

    // Set the image to cache from.
    if (!empty($opts['cache-from'])) {
      $build->arg('--cache-from');
      $build->arg($opts['cache-from']);
    }

    return $build->run();
  }

}
