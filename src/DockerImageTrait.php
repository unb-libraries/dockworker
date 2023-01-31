<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Robo\Robo;

/**
 * Provides methods to interact with a local docker image.
 */
trait DockerImageTrait {

  /**
   * Should the image tag be datestamped?
   *
   * @var bool
   */
  protected $dockerImageTagDateStamp = TRUE;

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
    $name_key = 'dockworker.image.name';
    $this->dockerImageName = Robo::Config()->get($name_key);
    if (empty($this->dockerImageName)) {
      throw new DockworkerException("The docker repository image name has not been defined in dockworker.yml [$name_key]");
    }
  }

  /**
   * Reads the deployment repository image name from config.
   *
   * @hook pre-init @dockerimage
   * @throws \Exception
   */
  public function setImageTagDatestamp() {
    $date_tag_value = 'dockworker.image.date_tag_image';
    $this->dockerImageTagDateStamp = (bool) Robo::Config()->get($date_tag_value, TRUE);
  }

}
