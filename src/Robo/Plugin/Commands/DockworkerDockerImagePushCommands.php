<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockerImagePushTrait;
use Dockworker\DockerImageTrait;
use Dockworker\DockworkerException;
use Dockworker\Robo\Plugin\Commands\DockworkerDockerImageBuildCommands;
use Robo\Robo;

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
      $image_name = "{$this->dockerImageName}:$env-$timestamp";
    }
    else {
      $image_name = "{$this->dockerImageName}:$env";
    }
    $this->applyKubeDeploymentUpdate($env, $image_name);
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

  /**
   * Tokenize the k8s deployment YAML and update the k8s deployment.
   *
   * @param string $env
   *   The environment to target.
   * @param string $image
   *   The image to update the deployment with.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function applyKubeDeploymentUpdate($env, $image) {
    if ($this->environmentIsDeployable($env)) {
      $deployment_file = $this->getTempKubeDeploymentFile($env, $image);
      $this->setRunOtherCommand("deployment:apply $deployment_file $env");
    }
    else {
      $this->say("Skipping deployment for environment [$env]. Deployable environments: " . implode(',', $this->getDeployableEnvironments()));
    }
  }

  /**
   * Tokenize the k8s deployment YAML.
   *
   * @param string $env
   *   The environment to target.
   * @param string $image
   *   The image to update the deployment with.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function getTempKubeDeploymentFile($env, $image) {
    $deployment_file = "{$this->repoRoot}/deployment/k8s/$env/deployment.yaml";
    if (!file_exists($deployment_file)) {
      throw new DockworkerException("Cannot find deployment file [$deployment_file]");
    }
    $tmp_yaml = tempnam(sys_get_temp_dir(), 'prefix') . '.yaml';
    $tokenized_deployment_yaml = file_get_contents($deployment_file);
    $full_deployment_yaml = str_replace('||DEPLOYMENTIMAGE||', $image, $tokenized_deployment_yaml);
    file_put_contents($tmp_yaml, $full_deployment_yaml);
    return $tmp_yaml;
  }

  /**
   * Determines if an environment is marked as deployable.
   *
   * @throws \Exception
   */
  protected function environmentIsDeployable($env) {
    $deployable_environments = $this->getDeployableEnvironments();
    if (empty($deployable_environments) || !in_array($env, $deployable_environments)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Retrieves the environments that are marked as deployable.
   *
   * @throws \Exception
   */
  protected function getDeployableEnvironments() {
    return Robo::Config()
      ->get('dockworker.deployment.environments', []);
  }

}
