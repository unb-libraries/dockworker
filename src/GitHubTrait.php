<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Robo\Robo;

/**
 * Provides methods to interact with GitHub repositories.
 */
trait GitHubTrait {

  /**
   * The owner of the GitHub Repository corresponding to this instance.
   *
   * @var string
   */
  protected $gitHubOwner;

  /**
   * The GitHub Client.
   *
   * @var object
   */
  protected $gitHubClient = NULL;

  /**
   * The GitHub Repository name corresponding to this instance.
   *
   * @var string
   */
  protected $gitHubRepo;

  /**
   * Retrieves the github repository name from config.
   *
   * @hook init @github
   * @throws \Dockworker\DockworkerException
   */
  public function setGitHubRepo() {
    $this->gitHubRepo = Robo::Config()->get('dockworker.github.repo');
    if (empty($this->gitHubRepo)) {
      throw new DockworkerException('The GitHub repo value (dockworker.github.repo) has not been set in the config file');
    }
  }

  /**
   * Sets the GitHub client.
   *
   * @hook init @github
   * @throws \Dockworker\DockworkerException
   */
  public function setGitHubClient() {
    if ($this->gitHubClient == NULL) {
      try{
        $this->gitHubClient = new \Github\Client();
        if($gh_token = getenv('GITHUB_AUTH_ACCESS_TOKEN')) {
          $this->gitHubClient->authenticate($gh_token, NULL, \Github\Client::AUTH_ACCESS_TOKEN);
        }
      }
      catch (\Exception $e) {
        throw new DockworkerException('The GitHub client could not be instantiated.');
      }
    }
  }

  /**
   * Retrieves the github owner from config.
   *
   * @hook init @github
   * @throws \Dockworker\DockworkerException
   */
  public function setGitHubOwner() {
    $this->gitHubOwner = Robo::Config()->get('dockworker.github.owner');
    if (empty($this->gitHubOwner)) {
      throw new DockworkerException('The GitHub owner value (dockworker.github.owner) has not been set in the config file');
    }
  }

}
