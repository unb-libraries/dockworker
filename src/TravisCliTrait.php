<?php

namespace Dockworker;

use Robo\Robo;

/**
 * Class for TravisCliTrait.
 */
trait TravisCliTrait {

  /**
   * The path to the travis binary.
   *
   * @var string
   */
  protected $travisBin;

  /**
   * The current repositories to exec commands in.
   *
   * @var object[]
   */
  protected $travisCurRepos = [];

  /**
   * Get travis CLI binary path from config.
   *
   * @throws \Exception
   *
   * @hook pre-init
   */
  public function setTravisBin() {
    $this->travisBin = Robo::Config()->get('dockworker.travis.bin');
    if (empty($this->travisBin)) {
      $this->travisBin = '/usr/local/bin/travis';
    }
  }

  /**
   * Get if the travis binary defined in the config file can be executed.
   *
   * @throws \Exception
   *
   * @hook init
   */
  public function setTravisBinExists() {
    if (!is_executable($this->travisBin)) {
      throw new \Exception(sprintf('The travis binary, %s, cannot be executed.', $this->travisBin));
    }
  }

  /**
   * Get travis CLI binary path from config.
   *
   * @throws \Exception
   *
   * @hook post-init
   */
  public function setTravisLogin() {
    $this->say(sprintf('Testing authentication to travis...'));
    $travis = $this->taskExec($this->travisBin)
      ->printOutput(FALSE)
      ->arg('accounts')
      ->run();
    if ($travis->getExitCode() > 0) {
      throw new \Exception(sprintf('The travis client is unauthorized. Run "travis login" AND "travis login --pro"'));
    }
  }

  /**
   * Restart the latest travis build job in a branch of a repository.
   *
   * @param string $repository
   *   The fully namespaced Github repository (i.e. unb-libraries/pmportal.org)
   * @param string $branch
   *   The branch
   *
   * @throws \Exception
   *
   * @command travis:build:restart-latest
   *
   * @return \Robo\ResultData
   */
  public function restartLatestTravisBuild($repository, $branch) {
    $latest_build_id = $this->getLatestTravisJobId($repository, $branch);
    return $this->restartTravisBuild($repository, $latest_build_id);
  }

  /**
   * Get the latest travis build job ID for a repository.
   *
   * @param string $repository
   *   The fully namespaced Github repository (i.e. unb-libraries/pmportal.org)
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
  public function getLatestTravisJobId($repository, $branch) {
    $build_info = $this-> getLatestTravisBuild($repository, $branch);
    preg_match('/Job #([0-9]+)\.[0-9]+\:/', $build_info, $matches);
    if (!empty($matches[1])) {
      return $matches[1];
    }
    return NULL;
  }

  /**
   * Get the latest travis build job details for a repository.
   *
   * @param string $repository
   *   The fully namespaced Github repository (i.e. unb-libraries/pmportal.org)
   * @param string $branch
   *   The branch of the repository
   *
   * @throws \Exception
   *
   * @command travis:build:get-latest
   *
   * @return string
   *   The build job details, if it exists.
   */
  public function getLatestTravisBuild($repository, $branch) {
    return $this->travisExec($repository, 'show', [$branch], FALSE)->getMessage();
  }

  /**
   * Execute a travis command via the CLI.
   *
   * @param string $repository
   *   The fully namespaced Github repository (i.e. unb-libraries/pmportal.org)
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
  private function travisExec($repository, $command, $args = [], $print_output = TRUE) {
    $this->getValidTravisRepository($repository);
    $travis = $this->taskExec($this->travisBin)
      ->printOutput($print_output)
      ->arg($command)
      ->arg("--repo=$repository");

    if (!empty($args)) {
      foreach ($args as $arg) {
        $travis->arg($arg);
      }
    }
    $this->say(sprintf('Executing travis %s in %s...', $command, $repository));
    return $travis->run();
  }

  /**
   * Get rudimentary validation on the repository.
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
   * Restart a travis build job.
   *
   * @param string $repository
   *   The fully namespaced Github repository (i.e. unb-libraries/pmportal.org)
   * @param string $build_id
   *   The build job ID
   *
   * @throws \Exception
   *
   * @command travis:build:restart
   *
   * @return \Robo\ResultData
   */
  public function restartTravisBuild($repository, $build_id) {
    return $this->travisExec($repository, 'restart', [$build_id]);
  }

}
