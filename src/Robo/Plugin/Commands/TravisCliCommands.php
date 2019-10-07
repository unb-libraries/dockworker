<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Robo\Plugin\Commands\DockworkerCommands;
use Dockworker\TravisCliTrait;

/**
 * Git commands.
 */
class TravisCliCommands extends DockworkerCommands {

  use TravisCliTrait;

  /**
   * Restart the latest travis build for this instance.
   *
   * @param string $branch
   *   The branch
   *
   * @throws \Exception
   *
   * @command travis:build:restart:latest
   *
   * @return \Robo\ResultData
   */
  public function restartLatestTravisBuild($branch) {
    $latest_build_id = $this->getLatestTravisJobId($branch);
    return $this->restartTravisBuild($latest_build_id);
  }

  /**
   * Get the latest travis build ID for this instance.
   *
   * @param string $branch
   *   The branch of the repository
   *
   * @throws \Exception
   *
   * @command travis:build:id:latest
   *
   * @return string
   *   The job ID, if it exists.
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
   * Get the latest travis build details for this instance..
   *
   * @param string $branch
   *   The branch of the repository
   *
   * @throws \Exception
   *
   * @command travis:build:info:latest
   *
   * @return string
   *   The build details, if it exists.
   */
  public function getLatestTravisBuild($branch) {
    return $this->travisExec('show', [$branch], FALSE)->getMessage();
  }

  /**
   * Get rudimentary validation on the repository name.
   *
   * @param string $repository_name
   *   The repository namespace to test (i.e. unb-libraries/pmportal.org)
   *
   * @throws \Exception
   */
  public function getValidTravisRepository($repository_name) {
    $repository_parts = explode('/', $repository_name);
    if (count($repository_parts) != 2) {
      throw new \Exception(sprintf('The repository name, %s, does not appear to include the namespace. Please enter the full repository namespace (i.e. unb-libraries/pmportal.org).', $repository_name));
    }
  }

  /**
   * Restart a travis build.
   *
   * @param string $build_id
   *   The build ID
   *
   * @throws \Exception
   *
   * @command travis:build:restart
   *
   * @return \Robo\ResultData
   */
  public function restartTravisBuild($build_id) {
    return $this->travisExec('restart', [$build_id]);
  }

  /**
   * Get logs for a travis build.
   *
   * @param string $build_id
   *   The build ID
   *
   * @throws \Exception
   *
   * @command travis:build:logs
   *
   * @return \Robo\ResultData
   */
  public function getTravisBuildLogs($build_id) {
    return $this->travisExec('logs', [$build_id]);
  }

  /**
   * Get logs for the latest travis build.
   *
   * @param string $branch
   *   The branch of the repository
   *
   * @throws \Exception
   *
   * @command travis:build:logs:latest
   *
   * @return \Robo\ResultData
   */
  public function getLatestTravisBuildLogs($branch) {
    $build_id = $this->getLatestTravisBuild($branch);
    $logs = $this->travisExec('logs', [$build_id])->getMessage();
    $this->say($logs);
    return TRUE;
  }

}
