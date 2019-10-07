<?php

namespace Dockworker;

use Robo\Robo;
use Dockworker\GitHubTrait;

/**
 * Class for TravisCliTrait.
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
   * Get travis CLI binary path from config.
   *
   * @throws \Exception
   *
   * @hook pre-init
   */
  public function setTravisBin() {
    $this->travisBin = Robo::Config()->get('dockworker.travis.bin');
    if (empty($this->travisBin)) {
      $this->travisBin = '/snap/bin/travis';
    }
  }

  /**
   * Set the travis GitHub repo string.
   *
   * @throws \Exception
   *
   * @hook init
   */
  public function setTravisGitHubRepo() {
    $this->travisGitHubRepo = $this->gitHubOwner . '/' . $this->gitHubRepo;
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
