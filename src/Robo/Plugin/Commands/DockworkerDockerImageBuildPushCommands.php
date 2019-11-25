<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockerImagePushTrait;
use Dockworker\DockerImageTrait;
use Dockworker\KubernetesDeploymentTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerDockerImageBuildCommands;

/**
 * Defines the commands used to build and push a docker image.
 */
class DockworkerDockerImageBuildPushCommands extends DockworkerDockerImageBuildCommands {

  use DockerImagePushTrait;
  use DockerImageTrait;
  use KubernetesDeploymentTrait;

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
    $this->pushCommandInit($env);
    $timestamp = date('YmdHis');
    $this->buildPushEnv($env, $timestamp);

    if ($this->dockerImageTagDateStamp) {
      $image_name = "{$this->dockerImageName}:$env-$timestamp";
    }
    else {
      $image_name = "{$this->dockerImageName}:$env";
    }
    $deployment_file = $this->applyKubeDeploymentUpdate($this->repoRoot, $env, $image_name);
    $deploy_namespace = $this->getKubernetesDeploymentFileNamespace($deployment_file);
    $this->setRunOtherCommand("deployment:status $deploy_namespace");
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
    $this->pushCommandInit($env);
    $this->setRunOtherCommand("image:build $env");
    $this->pushToRepository($env);

    if ($this->dockerImageTagDateStamp) {
      $this->setRunOtherCommand("image:build $env-$timestamp");
      $this->pushToRepository("$env-$timestamp");
    }
  }

}
