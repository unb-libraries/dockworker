<?php

namespace Dockworker\GitHub;

use Dockworker\DockworkerException;
use Dockworker\Storage\DockworkerPersistentDataStorageTrait;
use Github\Client as GitHubClient;
use Robo\Robo;

/**
 * Provides methods to authentical and interact with GitHub.
 *
 * @internal Not intended for use by anything other than a Dockworker command.
 */
trait GitHubClientTrait {

    use DockworkerPersistentDataStorageTrait;

    protected function initGitHubClient() {
        $this->setGitHubClient();
        $this->registerNewPreflightCheck(
          sprintf(
            "Testing GitHub API connectivity for %s/%s",
            $this->applicationGitHubRepoOwner,
            $this->applicationGitHubRepoName
          ),
          $this,
          'testGitHubAplicationRepoAccess',
          '',
          '',
          sprintf(
            "Unable to access github://%s/%s. Please check your credentials and try again.",
            $this->applicationGitHubRepoOwner,
            $this->applicationGitHubRepoName
          )
        );
    }

    /**
     * The GitHub Client.
     *
     * @var \Github\Client
     */
    protected $gitHubClient = null;

    /**
     * Sets up the GitHub client and checks the credentials.
     */
    protected function setGitHubClient() {
        $client_credentials_valid = false;
        $namespace = 'github';
        $auth_token_key = 'github.auth.token';
        while ($client_credentials_valid == false) {
            $gh_token = $this->getSetDockworkerPersistentDataConfigurationItem(
                $namespace,
                $auth_token_key,
                "Enter the personal access token (classic) that Dockworker should use to authenticate to GitHub",
                '',
                'Dockworker authenticates to GitHub using a Personal access token (classic). This allows all Dockworker actions to be performed as your GitHub user. Personal access tokens are an alternative to using a traditional user/password authentication.',
                [
                    [
                        'label' => 'HOWTO',
                        'uri' => 'https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token',
                    ],
                ]
            );
            if (empty($gh_token)) {
                $this->dockworkerIO->warning(
                  "Empty GitHub personal access token detected. Please enter a valid token."
                );
            } else {
                try {
                    # Create the client using the credentials.
                    $this->gitHubClient = new GitHubClient();
                    $this->gitHubClient->authenticate(
                      $gh_token,
                      null,
                      GitHubClient::AUTH_ACCESS_TOKEN
                    );

                    # Test the client
                    $this->testGitHubClientConnectivity();

                    # Credentials were valid, write them.
                    $this->setDockworkerPersistentDataConfigurationItem(
                      $namespace,
                      $auth_token_key,
                      $gh_token
                    );
                    $client_credentials_valid = true;
                }
                catch (\Exception $e) {
                    $this->dockworkerIO->warning(
                      "Invalid GitHub personal access token detected. Please enter a valid token."
                    );
                    # Token was invalid, clear it.
                    $this->setDockworkerPersistentDataConfigurationItem(
                      $namespace,
                      $auth_token_key,
                      ''
                    );
                }
            }
        }
    }

    protected function testGitHubClientConnectivity() {
        $this->gitHubClient->api('user')->repositories('unb-libraries');
    }

    public function testGitHubAplicationRepoAccess() {
        $this->gitHubClient->api('repo')->show(
          $this->applicationGitHubRepoOwner,
          $this->applicationGitHubRepoName
        );
    }
}
