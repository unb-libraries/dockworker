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
   * @command travis:build:restart-latest
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
   * @command travis:build:get-latest-id
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
   * @command travis:build:get-latest
   *
   * @return string
   *   The build details, if it exists.
   */
  public function getLatestTravisBuild($branch) {
    return $this->travisExec('show', [$branch], FALSE)->getMessage();
  }

  /**
   * Execute a travis command via the CLI.
   *
   * @param string $command
   *   The command to execute (i.e. ls)
   * @param string[] $args
   *   A list of arguments to pass to the command.
   * @param bool $print_output
   *   TRUE if the command should output results. False otherwise.
   *
   * @return \Robo\ResultData
   *   The result of the execution.
   * @throws \Exception
   */
  private function travisExec($command, $args = [], $print_output = TRUE) {
    $this->getValidTravisRepository($this->travisGitHubRepo);
    $travis = $this->taskExec($this->travisBin)
      ->printOutput($print_output)
      ->arg($command)
      ->arg("--repo={$this->travisGitHubRepo}");

    if (!empty($args)) {
      foreach ($args as $arg) {
        $travis->arg($arg);
      }
    }
    $this->say(sprintf('Executing travis %s in %s...', $command, $this->travisGitHubRepo));
    return $travis->run();
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

}
