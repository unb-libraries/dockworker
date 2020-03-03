<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerException;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;
use Dockworker\TravisCliTrait;

/**
 * Defines the commands used to interact with travis.com for the application.
 */
class TravisCliCommands extends DockworkerCommands {

  use TravisCliTrait;

  /**
   * Restarts the latest travis build for the application.
   *
   * @param string $branch
   *   The branch of the repository to restart.
   *
   * @command travis:restart:latest
   * @throws \Exception
   *
   * @usage travis:restart:latest prod
   *
   * @return \Robo\ResultData
   *
   * @github
   * @travis
   */
  public function restartLatestTravisBuild($branch) {
    $latest_build_id = $this->getLatestTravisJobId($branch);
    return $this->restartTravisBuild($latest_build_id);
  }

  /**
   * Retrieves the latest travis build ID for the application.
   *
   * @param string $branch
   *   The branch of the repository to retrieve the ID for.
   *
   * @command travis:id:latest
   * @throws \Exception
   *
   * @usage travis:id:latest prod
   *
   * @return string
   *   The job ID, if it exists.
   *
   * @github
   * @travis
   */
  public function getLatestTravisJobId($branch) {
    $build_info = $this->getLatestTravisBuild($branch);
    preg_match('/Job #([0-9]+)\.[0-9]+\:/', $build_info, $matches);
    if (!empty($matches[1])) {
      return $matches[1];
    }
    return NULL;
  }

  /**
   * Retrieves the latest travis build details for the application.
   *
   * @param string $branch
   *   The branch of the repository to retrieve details from.
   *
   * @command travis:info:latest
   * @throws \Exception
   *
   * @usage travis:info:latest prod
   *
   * @return string
   *   The build details, if it exists.
   *
   * @github
   * @travis
   */
  public function getLatestTravisBuild($branch) {
    return $this->travisExec('show', [$branch], FALSE)->getMessage();
  }

  /**
   * Restarts a travis build for the application.
   *
   * @param string $build_id
   *   The build ID to restart.
   *
   * @command travis:restart
   * @throws \Exception
   *
   * @usage travis:restart 346
   *
   * @return \Robo\ResultData
   *
   * @github
   * @travis
   */
  public function restartTravisBuild($build_id) {
    return $this->travisExec('restart', [$build_id]);
  }

  /**
   * Performs rudimentary validation on the repository name.
   *
   * @param string $repository_name
   *   The repository namespace to test (i.e. unb-libraries/pmportal.org)
   *
   * @throws \Exception
   */
  public function getValidTravisRepository($repository_name) {
    $repository_parts = explode('/', $repository_name);
    if (count($repository_parts) != 2) {
      throw new DockworkerException(sprintf('The repository name, %s, does not appear to include the namespace. Please enter the full repository namespace (i.e. unb-libraries/pmportal.org).', $repository_name));
    }
  }

  /**
   * Retrieves logs for a travis build for the application.
   *
   * @param string $build_id
   *   The build ID to retrieve the logs from.
   *
   * @command travis:logs
   * @throws \Exception
   *
   * @usage travis:logs 346
   *
   * @return \Robo\ResultData
   *
   * @github
   * @travis
   */
  public function getTravisBuildLogs($build_id) {
    return $this->travisExec('logs', [$build_id]);
  }

  /**
   * Retrieves logs for the latest travis build for the application.
   *
   * @param string $branch
   *   The branch of the repository to retrieve the logs from.
   *
   * @command travis:logs:latest
   * @throws \Exception
   *
   * @usage travis:logs:latest prod
   *
   * @return bool
   *
   * @github
   * @travis
   */
  public function getLatestTravisBuildLogs($branch) {
    $build_id = $this->getLatestTravisBuild($branch);
    $logs = $this->travisExec('logs', [$build_id])->getMessage();
    $this->say($logs);
    return TRUE;
  }

}
