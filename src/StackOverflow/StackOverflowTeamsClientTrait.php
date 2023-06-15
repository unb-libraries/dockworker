<?php

namespace Dockworker\StackOverflow;

use Dockworker\Core\PreFlightCheckTrait;
use Dockworker\Storage\DockworkerPersistentDataStorageTrait;
use Exception;

/**
 * Provides methods to interact with the StackOverflow Teams API.
 *
 * @INTERNAL This trait is intended only to be used by Dockworker commands. It
 * references the Dockworker application root, which is not in its own scope.
 */
trait StackOverflowTeamsClientTrait
{
    use DockworkerPersistentDataStorageTrait;
    use PreFlightCheckTrait;

    /**
     * The StackOverflow Teams Clients.
     *
     * @var \Dockworker\StackOverflow\StackOverflowTeamsClient[]
     */
    protected array $stackOverflowTeamsClients = [];

    /**
     * Sets a preflight check for the StackOverflow Teams API.
     *
     * @param \Dockworker\StackOverflow\StackOverflowTeamsClient $client
     *   The client to check connectivity for.
     */
    public function setStackTeamsClientPreflightCheck(StackOverflowTeamsClient $client): void
    {
        $this->registerNewPreflightCheck(
            "Testing StackOverflow Teams API connectivity",
            $this,
            'setTestStackTeamsClientConnectivity',
            [
                $client,
            ],
            '',
            [],
            '',
            "Unable to access the StackOverflow Teams API. Please check your credentials and try again."
        );
    }

    /**
    * Sets up a StackOverflow Teams client.
    *
    * @param string $team_slug
    *   The StackOverflow Teams slug to set the client for.
    *
    * @throws \Exception
    * @throws \GuzzleHttp\Exception\GuzzleException
    */
    protected function setStackTeamsClient(string $team_slug): void
    {
        $client_credentials_valid = false;
        $namespace = 'stackoverflow';
        $auth_token_key = "$namespace.auth.token.$team_slug";
        while ($client_credentials_valid == false) {
            $sot_token = $this->getSetDockworkerPersistentDataConfigurationItem(
                $namespace,
                $auth_token_key,
                sprintf(
                    'Enter the StackOverflow Teams API Personal Access Token (PAT) for %s',
                    $team_slug
                ),
                '',
                'Dockworker authenticates to StackOverflow Teams using a Personal Access Token (PAT). This allows all Dockworker actions to be performed as your GitHub user. Personal access tokens are an alternative to using a traditional user/password authentication.',
                [
                    [
                        'label' => 'HOWTO',
                        'uri' => 'https://stackoverflow.help/en/articles/4385859-stack-overflow-for-teams-api',
                    ],
                ],
                'STACK_OVERFLOW_TEAMS_AUTH_TOKEN_' . strtoupper($team_slug)
            );
            if (empty($sot_token)) {
                $this->dockworkerIO->warning(
                    "Empty StackOverflow Teams API personal access token (PAT) detected. Please enter a valid token."
                );
            } else {
                try {
                    $client = StackOverflowTeamsClient::setCreateClient(
                        $team_slug,
                        $sot_token
                    );
                    $this->setTestStackTeamsClientConnectivity(
                        $client,
                    );

                    # Credentials were valid, write them.
                    $this->setDockworkerPersistentDataConfigurationItem(
                        $namespace,
                        $auth_token_key,
                        $sot_token
                    );
                    $this->stackOverflowTeamsClients[$team_slug] = $client;
                    $client_credentials_valid = true;
                } catch (Exception $e) {
                    $this->dockworkerIO->warning(
                        sprintf(
                            'Invalid StackOverflow Teams API personal access token (PAT) detected for %s.',
                            $team_slug
                        )
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
     * Tests the connectivity of a StackOverflow Teams client.
     *
     * @param StackOverflowTeamsClient $client
     *   The StackOverflow Teams client to test.
     *
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function setTestStackTeamsClientConnectivity(
        StackOverflowTeamsClient $client
    ): void {
        $response = $client->getQuestions();
        if ($response->getStatusCode() != 200) {
            throw new Exception();
        }
    }
}
