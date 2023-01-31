<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockerContainerRegistryTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerDockerImageBuildCommands;

/**
 * Defines the commands used to build and push a docker image.
 */
class DockworkerDockerImagePushCommands extends DockworkerDockerImageBuildCommands {

  use DockerContainerRegistryTrait;

  /**
   * Builds this application's docker image, and pushes the image to the container registry.
   *
   * @param string $tag
   *   The tag to use when building and pushing the image.
   *
   * @option $no-cache
   *   Do not use any cached steps in the build.
   * @option $cache-from
   *   The image to cache the build from.
   *
   * @command docker:image:build-push
   * @throws \Exception
   *
   * @usage prod
   *
   * @dockerimage
   * @dockerpush
   */
  public function buildAndPushImage($tag) {
    $this->setRunOtherCommand('docker:image:build $tag');
    $this->pushToRepository($tag);
  }

  /**
   * Builds the docker image, tags it with a current timestamp, and pushes it.
   *
   * This method is intended to be used as part of a build-push-deploy command,
   * usually in deploy. In this vein, this cannot be called from a dirty git
   * repository.
   *
   * @param string $env
   *   The environment to target.
   * @param string $timestamp
   *   The timestamp string to use when tagging the image.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function buildPushEnv($env, $timestamp) {
    $this->pushCommandInit($env);
    $this->setRunOtherCommand("docker:image:build $env");
    $this->pushToRepository($env);

    if ($this->dockerImageTagDateStamp) {
      $this->setRunOtherCommand("docker:image:build $env-$timestamp");
      $this->pushToRepository("$env-$timestamp");
    }
  }

}
