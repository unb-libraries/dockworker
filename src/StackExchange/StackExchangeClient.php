<?php

namespace Dockworker\StackExchange;

use Dockworker\Storage\DockworkerPersistentDataStorageTrait;
use SendGrid\Client as SendGridClient;

/**
 * Provides methods to execute commands in deployed docker containers.
 */
class StackExchangeClient extends SendGridClient
{
    use DockworkerPersistentDataStorageTrait;

    protected array $stackHeaders = [];
    protected array $stackParams = [];

    public static function createClient(
        string $team_slug,
        string $api_key
    ): self|null {
        $obj = new static(
            'https://api.stackoverflowteams.com',
            [],
            '/2.3'
        );
        $obj->addHeaderItem('X-API-Access-Token', $api_key);
        $obj->addParamItem('team', $team_slug);
        return $obj;
    }

    public function addHeaderItem($key, $value): void
    {
        $this->stackHeaders[] = "$key: $value";
    }

    public function addParamItem($key, $value): void
    {
        $this->stackParams[$key] = $value;
    }

    public function getArticle($id)
    {
        return $this->articles()->_($id)
            ->get(
                null,
                $this->stackParams,
                $this->stackHeaders
            );
    }
}
