<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Dockworker\GitHubTrait;
use Dockworker\PersistentGlobalDockworkerConfigTrait;
use Robo\Robo;

/**
 * Provides methods to push to a remote docker repo.
 */
trait DockerContainerRegistryTrait {

  use GitHubTrait;
  use PersistentGlobalDockworkerConfigTrait;

  /**
   * The image repository.
   *
   * @var string
   */
  protected $dockerImageRepo;

  /**
   * Reads the deployment repository from config.
   *
   * @hook pre-init @dockerpush
   * @throws \Exception
   */
  public function setImageRepository() {
    $repository_key = 'dockworker.image.repository';
    $this->dockerImageRepo = Robo::Config()->get($repository_key);
    if (empty($this->dockerImageRepo)) {
      throw new DockworkerException("The docker image repository has not been defined in dockworker.yml [$repository_key]");
    }
  }

  /**
   * Reads the deployment repository image name from config.
   *
   * @hook post-init @dockerpush
   * @throws \Exception
   */
  public function authToRepository() {
    if ($this->dockerImageRepo == 'ghcr') {
      $this->authToGitHubContainerRegistry();
    }
    elseif ($this->dockerImageRepo == 'dockercloud') {
      $this->authToDockerCloudContainerRegistry();
    }
    else {
      throw new DockworkerException("The docker image repository type '{$this->dockerImageRepo}' is unsupported.");
    }
  }

  /**
   * Authenticates the user to docker cloud's container registry.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function authToDockerCloudContainerRegistry() {
    $user = $this->getSetGlobalDockworkerConfigItem(
      'dockworker.dockercloud.registry.user',
      "Enter the username for auth to the Docker Cloud container registry",
      $this->io(),
      '',
      'DOCKER_CLOUD_USER_NAME'
    );
    $pass = $this->getSetGlobalDockworkerConfigItem(
      'dockworker.dockercloud.registry.token',
      "Enter the access token for auth to the Docker Cloud container registry",
      $this->io(),
      '',
      'DOCKER_CLOUD_ACCESS_TOKEN'
    );
    exec("(echo \"$pass\" | docker login --username \"$user\" --password-stdin)", $output, $return);
    if ($return != '0') {
      throw new DockworkerException("DockerCloud auth failure.");
    }
  }

  /**
   * Authenticates the user to GitHub's container registry.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function authToGitHubContainerRegistry() {
    $user = $this->getSetGlobalDockworkerConfigItem(
      'dockworker.github.registry.user',
      "Enter the username for auth to the GitHub container registry",
      $this->io(),
      '',
      'GH_CONTAINER_REGISTRY_USER'
    );
    $token = $this->getSetGlobalDockworkerConfigItem(
      'dockworker.github.registry.token',
      "Enter the token for auth to the GitHub container registry",
      $this->io(),
      '',
      'GH_CONTAINER_REGISTRY_TOKEN'
    );
    exec("(echo \"$token\" | docker login ghcr.io -u \"$user\" --password-stdin)", $output, $return);
    if ($return != '0') {
      throw new DockworkerException("GitHub Container Registry auth failure.");
    }
  }

  /**
   * Pushes an image to the repository.
   *
   * @throws \Exception
   */
  protected function pushToRepository($tag) {
    $this->io()->title("Pushing {$this->dockerImageName}:$tag");
    return $this->taskExec('docker')
      ->printOutput(TRUE)
      ->arg('push')
      ->arg($this->dockerImageName . ":$tag")
      ->run();
  }

  /**
   * Determines if an environment is marked as pushable.
   *
   * @throws \Exception
   */
  protected function environmentIsPushable($env) {
    $pushable_environments = $this->getPushableEnvironments();
    if (empty($pushable_environments) || !in_array($env, $pushable_environments)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Retrieves the environments that are marked as pushable.
   *
   * @throws \Exception
   */
  protected function getPushableEnvironments() {
    return Robo::Config()
    ->get('dockworker.image.push_branches', []);
  }

  /**
   * Determines if a push command should continue.
   *
   * @throws \Exception
   */
  protected function pushCommandInit($env) {
    if ($this->environmentIsPushable($env)) {
      return;
    }
    else {
      $this->say("Skipping image push for environment [$env]. Pushable environments: " . implode(',', $this->getPushableEnvironments()));
      exit(0);
    }
  }

}
