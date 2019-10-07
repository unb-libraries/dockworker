<?php

namespace Dockworker;

use Robo\Robo;

/**
 * Class for TravisCliTrait.
 */
trait GitHubTrait {

  /**
   * The GitHub Repository corresponding to this instance.
   *
   * @var string
   */
  protected $gitHubRepo;

  /**
   * The owner of the GitHub Repository corresponding to this instance.
   *
   * @var string
   */
  protected $gitHubOwner;

  /**
   * Get the github repo from config.
   *
   * @throws \Exception
   *
   * @hook pre-init
   */
  public function setGitHubRepo() {
    $this->gitHubRepo = Robo::Config()->get('dockworker.instance.github.repo');
    if (empty($this->gitHubRepo)) {
      throw new \Exception('The GitHub repo value (dockworker.instance.github.repo) has not been set in the config file');
    }
  }

  /**
   * Get github owner from config.
   *
   * @throws \Exception
   *
   * @hook pre-init
   */
  public function setGitHubOwner() {
    $this->gitHubOwner = Robo::Config()->get('dockworker.instance.github.owner');
    if (empty($this->gitHubOwner)) {
      throw new \Exception('The GitHub owner value (dockworker.instance.github.owner) has not been set in the config file');
    }
  }

}
