<?php

namespace Dockworker;

use Dockworker\LocalDockerContainerTrait;

/**
 * Defines trait for interacting with local docker Drupal containers.
 */
trait DrupalLocalDockerContainerTrait {

  use LocalDockerContainerTrait;

  /**
   * Execute a drush command in a local docker container.
   *
   * @param string $name
   *   The name of the container to execute the command in.
   * @param $command
   *   The drush command to execute.
   *
   * @return mixed
   * @throws \Exception
   */
  protected function localDockerContainerDrushCommand($name, $command) {
    return $this->localDockerContainerExecCommand(
      $name,
      sprintf('drush --yes --root=/app/html %s',
        $command
      )
    );
  }

}
