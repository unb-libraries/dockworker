<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Dockworker\PersistentGlobalDockworkerConfigTrait;
use Robo\Robo;

/**
 * Provides methods to interact with GitHub repositories.
 */
trait GitHubTrait {

  use PersistentGlobalDockworkerConfigTrait;

  /**
   * The owner of the GitHub Repository corresponding to this instance.
   *
   * @var string
   */
  protected $gitHubOwner;

  /**
   * The GitHub Client.
   *
   * @var \Github\Client
   */
  protected $gitHubClient = NULL;

  /**
   * The GitHub Repository name corresponding to this instance.
   *
   * @var string
   */
  protected $gitHubRepo;

  /**
   * Retrieves the GitHub repository name from config.
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
        $gh_token = $this->getSetGlobalDockworkerConfigItem(
          'dockworker.github.token',
          "Enter a personal access token for auth to GitHub",
          $this->io(),
          '',
          'GITHUB_AUTH_ACCESS_TOKEN'
        );
        if(!empty($gh_token)) {
          $this->gitHubClient->authenticate($gh_token, '', \Github\Client::AUTH_ACCESS_TOKEN);
        }
      }
      catch (\Exception) {
        throw new DockworkerException('The GitHub client could not be instantiated.');
      }
    }
  }

  /**
   * Retrieves the GitHub owner from config.
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

  /**
   * Initializes the trait's properties.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function initSetupGitHubTrait() {
    $this->setGitHubRepo();
    $this->setGitHubClient();
    $this->setGitHubOwner();
  }

}
