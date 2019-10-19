<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Robo\Robo;

/**
 * Provides methods to interact with a local docker image.
 */
trait DockerImageTrait {

  /**
   * The image name.
   *
   * @var string
   */
  protected $dockerImageName;

  /**
   * Reads the deployment repository image name from config.
   *
   * @hook pre-init @dockerimage
   * @throws \Exception
   */
  public function setImageName() {
    $name_key = 'dockworker.application.deployment.image.name';
    $this->dockerImageName = Robo::Config()->get($name_key);
    if (empty($this->dockerImageName)) {
      throw new DockworkerException("The docker repository image name has not been defined in dockworker.yml [$name_key]");
    }
  }

}
