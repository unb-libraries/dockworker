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
