<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Robo\Robo;

/**
 * Provides methods to push images to Docker Cloud.
 */
trait DockerCloudPushTrait {

  /**
   * Login to docker cloud.
   *
   * @throws \Dockworker\DockworkerException
   */
  private function loginDockerCloud() {
    $login_result = $this->taskExec('docker')
      ->interactive(TRUE)
      ->printOutput(TRUE)
      ->arg('login')
      ->run();
    if (!$login_result->wasSuccessful()) {
      throw new DockworkerException('Failed to authenticate to Docker Cloud!');
    }
  }

}
