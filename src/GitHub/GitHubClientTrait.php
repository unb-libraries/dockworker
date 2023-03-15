<?php

namespace Dockworker\GitHub;

use Dockworker\Core\PreFlightCheckTrait;
use Dockworker\Storage\DockworkerPersistentDataStorageTrait;
use Exception;
use Github\AuthMethod;
use Github\Client as GitHubClient;

/**
 * Provides methods to authenticate and interact with a GitHub repo.
 */
trait GitHubClientTrait
{
    use DockworkerPersistentDataStorageTrait;
    use PreFlightCheckTrait;

    /**
     * The GitHub Client.
     *
     * @var GitHubClient
     */
    protected GitHubClient $gitHubClient;

    /**
     * Tests the GitHub client's connectivity to the current application's repository.
     */
    public function testGitHubRepoAccess(
        string $owner,
        string $name
    ): void {
        $this->gitHubClient->api('repo')->show(
            $owner,
            $name
        );
    }

    /**
     * Initializes, sets up a GitHub client for the application's repository.
     */
    protected function initGitHubClientApplicationRepo(
        string $owner,
        string $name
    ): void {
        $this->setGitHubClient($owner);
        $this->registerNewPreflightCheck(
            sprintf(
                "Testing GitHub API connectivity for %s/%s",
                $owner,
                $name
            ),
            $this,
            'testGitHubRepoAccess',
            [
                $owner,
                $name,
            ],
            '',
            [],
            '',
            sprintf(
                "Unable to access github://%s/%s. Please check your credentials and try again.",
                $owner,
                $name
            )
        );
    }

    /**
     * Configures and sets up the GitHub client, registering any credentials.
     */
    protected function setGitHubClient($owner): void
    {
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
                        AuthMethod::ACCESS_TOKEN
                    );

                    # Test the client
                    $this->testGitHubClientConnectivity($owner);

                    # Credentials were valid, write them.
                    $this->setDockworkerPersistentDataConfigurationItem(
                        $namespace,
                        $auth_token_key,
                        $gh_token
                    );
                    $client_credentials_valid = true;
                } catch (Exception $e) {
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

    /**
     * Tests the GitHub client's connectivity to the GitHub API.
     */
    protected function testGitHubClientConnectivity($owner): void
    {
        $this->gitHubClient->api('user')->repositories($owner);
    }
}
