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
      $this->travisBin = '/usr/local/bin/travis';
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

}
