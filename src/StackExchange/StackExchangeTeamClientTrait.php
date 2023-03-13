<?php

namespace Dockworker\StackExchange;

use Dockworker\Storage\DockworkerPersistentDataStorageTrait;
use Exception;

/**
 * Provides methods to authentical and interact with GitHub.
 *
 * @INTERNAL This trait is intended only to be used by Dockworker commands. It
 * references the Dockworker application root, which is not in its own scope.
 */
trait StackExchangeTeamClientTrait
{
    use DockworkerPersistentDataStorageTrait;

    /**
     * The Stack Exchange Teams Cients.
     *
     * @var object[]
     */
    protected array $stackExchangeTeamClients = [];

  /**
   * Sets up a Stack Exchange Teams client.
   *
   * @param string $team_slug
   *   The Stack Exchange Teams slug to set the client for.
   */
    protected function setStackTeamsClient(string $team_slug): void
    {
        $client_credentials_valid = false;
        $namespace = 'stackexchange';
        $auth_token_key = "stackexchange.auth.token.$team_slug";
        while ($client_credentials_valid == false) {
            $sot_token = $this->getSetDockworkerPersistentDataConfigurationItem(
                $namespace,
                $auth_token_key,
                sprintf(
                    'Enter the Stack Overflow Teams Personal Access Token (PAT) for %s',
                    $team_slug
                ),
                '',
                'Dockworker authenticates to Stack Overflow Teams using a Personal Access Token (PAT). This
                    allows all Dockworker actions to be performed as your GitHub user. Personal access tokens are an
                    alternative to using a traditional user/password authentication.',
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
                    "Empty Stack Overflow personal access token (PAT) detected. Please enter a valid token."
                );
            } else {
                try {
                    $client = StackExchangeClient::createClient(
                        $team_slug,
                        $sot_token
                    );
                    $this->testStackTeamsClientConnectivity(
                        $client,
                    );

                    # Credentials were valid, write them.
                    $this->setDockworkerPersistentDataConfigurationItem(
                        $namespace,
                        $auth_token_key,
                        $sot_token
                    );
                    $this->stackExchangeTeamClients[$team_slug] = $client;
                    $client_credentials_valid = true;
                } catch (Exception $e) {
                    $this->dockworkerIO->warning(
                        sprintf(
                            'Invalid Stack Overflow personal access token (PAT) detected for %s.',
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
   * Tests the connectivity of a Stack Exchange Teams client.
   *
   * @param StackExchangeClient $client
   *   The Stack Exchange Teams client to test.
   *
   * @return void
   * @throws \Exception
   */
    protected function testStackTeamsClientConnectivity(
        StackExchangeClient $client
    ): void {
        $response = $client->getQuestions();
        if ($response->getStatusCode() != 200) {
            throw new Exception();
        }
    }
}
