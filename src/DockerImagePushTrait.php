<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Dockworker\GitHubTrait;
use Robo\Robo;

/**
 * Provides methods to push to a remote docker repo.
 */
trait DockerImagePushTrait {

  use GitHubTrait;

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
    $repository_key = 'dockworker.application.deployment.repository';
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
    if ($this->dockerImageRepo == 'dockercloud') {
      $user = getenv('DOCKER_CLOUD_USER_NAME');
      $pass = getenv('DOCKER_CLOUD_USER_PASS');
      exec("(echo \"$pass\" | docker login --username \"$user\" --password-stdin)", $output, $return);
      if ($return != '0') {
        throw new DockworkerException("DockerCloud auth failure. Have you provided DOCKER_CLOUD_USER_NAME and DOCKER_CLOUD_USER_PASS as environment variables?");
      }
    }
    else {
      throw new DockworkerException("The docker image repository type '{$this->dockerImageRepo}' is unsupported.");
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
   * Determines if an environment is marked as deployable.
   *
   * @throws \Exception
   */
  protected function environmentIsDeployable($env) {
    $deployable_environments = $this->getDeployableEnvironments();
    if (!in_array($env, $deployable_environments)) {
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
      ->get('dockworker.application.deployment.environments');
  }

}
