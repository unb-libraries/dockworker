<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Dockworker\GitHubTrait;

/**
 * Provides methods to execute commands inside Kubernetes Pods.
 */
trait GitContributorsListTrait {

  use GitHubTrait;

  /**
   * Gets the list of authors of a GitHub repository.
   *
   * @param string $owner
   *   The repository owner.
   * @param string $repository
   *   The repository name.
   *
   * @return string[]
   *   An array of contributor names, sorted by their contribution count.
   *
   * @throws \Exception
   */
  protected function getGitHubContributors($owner, $repository) {
    $this->say("Querying GitHub API [repo:contributors:$owner:$repository]");
    $contributors = $this->gitHubClient->api('repo')->contributors($owner, $repository);
    if (!empty($contributors)) {
      array_walk($contributors,array($this, 'addGithubUserName'));
    }
    return $contributors;
  }

  /**
   * Add the contributor name to the contributor object.
   */
  private function addGithubUserName(&$contributor, $key) {
    $contributor['name'] = $this->getUserDisplayName($contributor);
  }

  /**
   * Get the list of authors to a git repository, sorted by contributions.
   *
   * @param string $path
   *   The path to the git repo.
   *
   * @throws \Exception
   */
  protected function getUserFromUserName($username) {
    $this->say("Querying GitHub API [user:show:$username]");
    return $this->gitHubClient->api('user')->show($username);
  }

  /**
   * Set a user display name from a contributor.
   */
  private function getUserDisplayName($contributor) {
    $user = $this->getUserFromUserName($contributor['login']);
    if (empty($user['name'])) {
      return $contributor['login'];
    }
    else {
      return $user['name'];
    }
  }

}
