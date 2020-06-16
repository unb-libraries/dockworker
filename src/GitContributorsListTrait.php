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
   * Get the list of authors to a git repository, sorted by contributions.
   *
   * @param string $path
   *   The path to the git repo.
   *
   * @throws \Exception
   */
  protected function getContributors($owner, $repository) {
    $client = new \Github\Client();
    return $client->api('repo')->contributors($owner, $repository);
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
    $client = new \Github\Client();
    return $client->api('user')->show($username);
  }

  /**
   * Get the list of authors to a git repository, sorted by contributions.
   *
   * @param string $path
   *   The path to the git repo.
   *
   * @throws \Exception
   */
  protected function getContributorHTMLList($owner, $repository, $size = '128') {
    $html = NULL;
    $contributors = $this->getContributors($owner, $repository);
    foreach ($contributors as $contributor) {
      $html .= sprintf(
        '<a href="https://github.com/%s"><img src="https://avatars.githubusercontent.com/u/%s?v=3" title="%s" width="%s" height="%s"></a>',
        $contributor['login'],
        $contributor['id'],
        $this->getUserDisplayName($contributor),
        $size,
        $size
      ) . "\n";
    }
    return $html;
  }

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
