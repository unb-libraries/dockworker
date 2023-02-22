<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Dockworker\Storage\DockworkerPersistentDataStorageTrait;
use Robo\Robo;

/**
 * Provides methods to authentical and interact with GitHub.
 */
trait GitHubClientTrait {

    use PersistentGlobalDockworkerConfigTrait;

    /**
     * The GitHub Client.
     *
     * @var \Github\Client
     */
    protected $gitHubClient = null;

    /**
     * Sets up the GitHub client.
     *
     * @hook init
     * @throws \Dockworker\DockworkerException
     */
    public function setGitHubClient() {
        if ($this->gitHubClient == null) {
            try{
                $this->gitHubClient = new \Github\Client();
                getSetDockworkerPersistentDataConfigurationItem
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

}
