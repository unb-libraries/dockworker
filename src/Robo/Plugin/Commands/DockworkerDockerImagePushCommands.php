<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockerImagePushTrait;
use Dockworker\DockerImageTrait;
use Dockworker\DockworkerException;
use Dockworker\Robo\Plugin\Commands\DockworkerDockerImageBuildCommands;

/**
 * Defines the commands used to push a docker image to a repository.
 */
class DockworkerDockerImagePushCommands extends DockworkerDockerImageBuildCommands {

  use DockerImageTrait;
  use DockerImagePushTrait;

  /**
   * Pushes the docker image to its repository.
   *
   * @param string $tag
   *   The image tag to push.
   *
   * @option bool $no-cache
   *   Do not use any cached steps in the build.
   * @option string $cache-from
   *   The image to cache the build from.
   *
   * @command image:push
   * @throws \Exception
   *
   * @dockerimage
   * @dockerpush
   */
  public function pushImage($tag = NULL) {
    if (!empty($tag)) {
      $tag = 'latest';
    }
    $this->pushToRepository($tag);
  }

  /**
   * Builds the docker image, and pushes the docker image to its repository.
   *
   * @param string $tag
   *   The image tag to push.
   *
   * @option bool $no-cache
   *   Do not use any cached steps in the build.
   * @option string $cache-from
   *   The image to cache the build from.
   *
   * @command image:build-push
   * @throws \Exception
   *
   * @dockerimage
   * @dockerpush
   */
  public function buildAndPushImage($tag) {
    $this->setRunOtherCommand('image:build $tag');
    $this->pushToRepository($tag);
  }

  /**
   * Builds the docker image, tags it with a current timestamp, and deploys it.
   *
   * @param string $env
   *   The environment to target.
   *
   * @option bool $no-cache
   *   Do not use any cached steps in the build.
   * @option string $cache-from
   *   The image to cache the build from.
   *
   * @command image:deploy
   * @throws \Exception
   *
   * @dockerimage
   * @dockerpush
   */
  public function buildPushDeployEnv($env) {
    if ($this->environmentIsDeployable($env)) {
      $timestamp = date('YmdHis');
      $this->buildPushEnv($env, $timestamp);
      $this->setRunOtherCommand("deployment:image:update {$this->dockerImageName} $env-$timestamp $env");
      $this->setRunOtherCommand("deployment:status $env");
    }
    else {
      $this->say("Skipping deployment for environment [$env]. Deployable environments: " . implode(',', $this->getDeployableEnvironments()));
    }
  }

  /**
   * Builds the docker image, tags it with a current timestamp, and pushes it.
   *
   * This method is intended to be used as part of a build-push-deploy command,
   * usually in travis. In the vein, this cannot be called from a dirty git
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
    $this->setRunOtherCommand("image:build $env");
    $this->setRunOtherCommand("image:build $env-$timestamp");
    $this->pushToRepository($env);
    $this->pushToRepository("$env-$timestamp");
  }

}
