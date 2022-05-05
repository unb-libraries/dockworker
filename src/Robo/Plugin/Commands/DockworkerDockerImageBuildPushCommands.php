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
   * @command image:build-push
   * @throws \Exception
   *
   * @usage image:build-push prod
   *
   * @dockerimage
   * @dockerpush
   */
  public function buildAndPushImage($tag) {
    $this->setRunOtherCommand('image:build $tag');
    $this->pushToRepository($tag);
  }

  /**
   * Builds this application's docker image, pushes it to the container registry, and updates its k8s deployment with it.
   *
   * @param string $env
   *   The environment to target.
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $use-tag
   *   Skip building and deploy with the specified tag.
   *
   * @command image:deploy
   * @throws \Exception
   *
   * @usage image:deploy prod
   *
   * @dockerimage
   * @dockerpush
   */
  public function buildPushDeployEnv($env, array $options = ['use-tag' => NULL]) {
    $this->pushCommandInit($env);
    if (empty($options['use-tag'])) {
      $timestamp = date('YmdHis');
      $this->buildPushEnv($env, $timestamp);

      if ($this->dockerImageTagDateStamp) {
        $image_name = "{$this->dockerImageName}:$env-$timestamp";
      }
      else {
        $image_name = "{$this->dockerImageName}:$env";
      }
    }
    else {
      $image_name = "{$this->dockerImageName}:{$options['use-tag']}";
    }

    $this->say('Updating deployment configuration..');
    $deployment_file = $this->applyKubeDeploymentUpdate($this->repoRoot, $env, $image_name);

    $cron_file = static::getKubernetesFileNameFromBranch($this->repoRoot, $env, 'cron');
    if (file_exists($cron_file)) {
      $this->say('Updating cron configuration..');
      $cron_file = $this->getTokenizedKubeFile($this->repoRoot, $env, $image_name, 'cron');
      $this->setRunOtherCommand("deployment:delete-apply $cron_file");
    }

    $backup_file = static::getKubernetesFileNameFromBranch($this->repoRoot, $env, 'backup');
    if (file_exists($backup_file)) {
      $this->say('Updating backup configuration..');
      $this->setRunOtherCommand("deployment:delete-apply $backup_file");
    }

    $this->say('Checking for successful deployment..');
    $deploy_namespace = static::getKubernetesDeploymentFileNamespace($deployment_file);
    $this->setRunOtherCommand("deployment:status $deploy_namespace");
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
    $this->setRunOtherCommand("image:build $env");
    $this->pushToRepository($env);

    if ($this->dockerImageTagDateStamp) {
      $this->setRunOtherCommand("image:build $env-$timestamp");
      $this->pushToRepository("$env-$timestamp");
    }
  }

}
