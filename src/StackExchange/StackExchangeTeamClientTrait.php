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

    protected function setStackTeamsClient(string $team_slug): void
    {
        $client_credentials_valid = false;
        $namespace = 'stackexchange';
        $auth_token_key = "stackexchange.auth.token.$team_slug";
        while ($client_credentials_valid == false) {
            $sot_token = $this->getSetDockworkerPersistentDataConfigurationItem(
                $namespace,
                $auth_token_key,
                "Enter the personal access token (PAT) that Dockworker should use to authenticate [$team_slug] in StackOverflowTeams",
                '',
                'Dockworker authenticates to Stack Overflow Teams using a Personal Access Token (PAT). This allows all Dockworker actions to be performed as your GitHub user. Personal access tokens are an alternative to using a traditional user/password authentication.',
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
                      $team_slug,
                      $sot_token
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
                        "Invalid Stack Overflow personal access token (PAT) detected for $team_slug. Please enter a valid token."
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

    protected function testStackTeamsClientConnectivity(
      StackExchangeClient $client
    ): void {
        $response = $client->getArticle('192');
        die(print_r($response, TRUE));
    }
}
