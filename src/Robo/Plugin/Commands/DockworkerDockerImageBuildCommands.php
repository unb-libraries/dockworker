<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockerImageTrait;
use Dockworker\DockworkerException;
use Dockworker\GitRepoTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerBaseCommands;

/**
 * Defines the commands used to build a docker image from the repository.
 */
class DockworkerDockerImageBuildCommands extends DockworkerBaseCommands {

  use DockerImageTrait;
  use GitRepoTrait;

  const ERROR_UNCLEAN_REPO = 'Aborted build due to unclean repo';

  /**
   * Builds this application's docker image.
   *
   * @param string $tag
   *   The tag to use when building the image.
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $no-cache
   *   Do not use any cached steps in the build.
   * @option $cache-from
   *   The image to cache the build from.
   * @option $allow-dirty
   *   Skips the warning prompt if the git repository is dirty.
   *
   * @command docker:image:build
   * @throws \Exception
   *
   * @usage prod
   *
   * @return \Robo\ResultData
   *
   * @dockerimage
   */
  public function buildImage($tag = '', array $options = ['no-cache' => FALSE, 'cache-from' => '', 'allow-dirty' => FALSE]) {
    $this->buildRepoCleanCheck($options);
    $this->io()->title("Building {$this->dockerImageName}:$tag");
    $build = $this->taskDockerBuild($this->repoRoot);
    $this->addMetadataBuildArgs($build);

    if (!empty($tag)) {
      $build->tag("{$this->dockerImageName}:$tag");
    }
    else {
      $build->tag("{$this->dockerImageName}:latest");
    }

    if ($options['no-cache']) {
      $build->arg('--no-cache');
    }

    if (!empty($options['cache-from'])) {
      $build->arg('--cache-from');
      $build->arg($options['cache-from']);
    }

    return $build->run();
  }

  /**
   * Confirms if the user wishes to build a dirty repo.
   *
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @throws \Dockworker\DockworkerException
   */
  private function buildRepoCleanCheck(array $options) {
    if (!$this->gitRepoIsClean($this->repoRoot) && !$options['allow-dirty']) {
      $continue = $this->confirm('Warning: You are attempting to build an image in a dirty git repo. Would you like to proceed?');
      if (!$continue) {
        throw new DockworkerException(sprintf(self::ERROR_UNCLEAN_REPO));
      }
    }
  }

  /**
   * Add build arguments to populate metadata for image.
   *
   * @param \Robo\Task\Docker\Build $build
   *   The docker build task.
   *
   * @throws \Exception
   */
  private function addMetadataBuildArgs($build) {
    $commit_hash = $this->gitRepoLatestCommitHash($this->repoRoot);
    $arguments = [
      'BUILD_DATE' => date("c"),
      'VERSION' => $commit_hash,
      'VCS_REF' => $commit_hash,
    ];

    if (!$this->gitRepoIsClean($this->repoRoot)) {
      $arguments['VERSION'] .= '-dirty';
    }

    foreach ($arguments as $arg_name => $argument) {
      $build->arg('--build-arg')->arg("$arg_name=$argument");
    }
  }

}
