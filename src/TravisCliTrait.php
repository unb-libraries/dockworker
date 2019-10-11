<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Dockworker\GitHubTrait;
use Robo\Robo;

/**
 * Provides methods to interact with travis via the CLI client.
 */
trait TravisCliTrait {

  use GitHubTrait;

  /**
   * The path to the travis binary.
   *
   * @var string
   */
  protected $travisBin;

  /**
   * The GitHub Repository corresponding to this instance.
   *
   * @var string
   */
  protected $travisGitHubRepo;

  /**
   * The current repositories to exec commands in.
   *
   * @var object[]
   */
  protected $travisCurRepos = [];

  /**
   * Determines the travis CLI binary path from config.
   *
   * @hook pre-init
   * @throws \Exception
   */
  public function setTravisBin() {
    $this->travisBin = Robo::Config()->get('dockworker.travis.bin');
    if (empty($this->travisBin)) {
      $this->travisBin = '/snap/bin/travis';
    }
  }

  /**
   * Sets the travis/GitHub repo string.
   *
   * @hook init
   * @throws \Exception
   */
  public function setTravisGitHubRepo() {
    $this->travisGitHubRepo = $this->gitHubOwner . '/' . $this->gitHubRepo;
  }

  /**
   * Determines if the travis binary can be executed.
   *
   * @hook init
   * @throws \Exception
   */
  public function setTravisBinExists() {
    if (!is_executable($this->travisBin)) {
      throw new DockworkerException(sprintf('The travis binary, %s, cannot be executed.', $this->travisBin));
    }
  }

  /**
   * Determines the travis CLI binary path.
   *
   * @hook post-init
   * @throws \Exception
   */
  public function setTravisLogin() {
    $travis = $this->taskExec($this->travisBin)
      ->printOutput(FALSE)
      ->silent(TRUE)
      ->arg('accounts')
      ->run();
    if ($travis->getExitCode() > 0) {
      throw new DockworkerException(sprintf('The travis client is unauthorized. Run "travis login" AND "travis login --pro"'));
    }
  }

  /**
   * Executes a travis command via the CLI.
   *
   * @param string $command
   *   The command to execute (i.e. ls)
   * @param string[] $args
   *   A list of arguments to pass to the command.
   * @param bool $print_output
   *   TRUE if the command should output results. False otherwise.
   *
   * @throws \Exception
   *
   * @return \Robo\ResultData
   *   The result of the execution.
   */
  private function travisExec($command, $args = [], $print_output = TRUE) {
    $this->getValidTravisRepository($this->travisGitHubRepo);
    $travis = $this->taskExec($this->travisBin)
      ->printOutput($print_output)
      ->arg($command)
      ->arg('--pro')
      ->arg("--repo={$this->travisGitHubRepo}");

    if (!empty($args)) {
      foreach ($args as $arg) {
        $travis->arg($arg);
      }
    }
    $this->say(sprintf('Executing travis %s in %s...', $command, $this->travisGitHubRepo));
    return $travis->run();
  }

}
