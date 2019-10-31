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
    $this->pushCommandShouldContinue($env);
    $timestamp = date('YmdHis');
    $this->buildPushEnv($env, $timestamp);

    if ($this->dockerImageTagDateStamp) {
      $this->setRunOtherCommand("deployment:image:update {$this->dockerImageName} $env-$timestamp $env");
    }
    else {
      $this->setRunOtherCommand("deployment:image:update {$this->dockerImageName} $env $env");
    }
    $this->setRunOtherCommand("deployment:status $env");
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
    $this->pushCommandShouldContinue($env);
    $this->setRunOtherCommand("image:build $env");
    $this->pushToRepository($env);

    if ($this->dockerImageTagDateStamp) {
      $this->setRunOtherCommand("image:build $env-$timestamp");
      $this->pushToRepository("$env-$timestamp");
    }
  }

  /**
   * Determines if a push command should continue.
   *
   * @throws \Exception
   */
  protected function pushCommandShouldContinue($env) {
    if ($this->environmentIsPushable($env)) {
      return;
    }
    else {
      $this->say("Skipping image push for environment [$env]. Pushable environments: " . implode(',', $this->getPushableEnvironments()));
      exit(0);
    }
  }

}
