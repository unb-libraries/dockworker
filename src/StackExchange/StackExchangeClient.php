<?php

namespace Dockworker\StackExchange;

use Dockworker\Storage\DockworkerPersistentDataStorageTrait;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides a client to interface with Stack Exchange Teams.
 */
class StackExchangeClient extends GuzzleClient
{
    use DockworkerPersistentDataStorageTrait;

    protected const ENDPOINT_URI = 'https://api.stackoverflowteams.com/2.3';

    /**
     * The headers to send with the request.
     *
     * @var string[]
     */
    protected array $stackHeaders = [];

    /**
     * The URL parameters to send with the request.
     *
     * @var string[]
     */
    protected array $stackParams = [];

    /**
     * Creates a new Stack Exchange Teams API client.
     *
     * @param string $team_slug
     *   The team slug to use.
     * @param string $api_key
     *   The API key to use.
     *
     * @return static|null
     *   The new client, or NULL if the client could not be created.
     */
    public static function createClient(
        string $team_slug,
        string $api_key
    ): self|null {
        $obj = new self();
        $obj->addHeaderItem('X-API-Access-Token', $api_key);
        $obj->addParamItem('team', $team_slug);
        return $obj;
    }

    /**
     * Adds a header to the request.
     *
     * @param string $key
     *   The header key.
     * @param string $value
     *   The header value.
     */
    public function addHeaderItem(
        string $key,
        string $value
    ): void {
        $this->stackHeaders[$key] = $value;
    }

    /**
     * Adds a URL parameter to the request.
     *
     * @param string $key
     *   The parameter key.
     * @param string $value
     *   The parameter value.
     */
    public function addParamItem(
        string $key,
        string $value
    ): void {
        $this->stackParams[$key] = $value;
    }

    /**
     * Gets an article from the Stack Exchange Teams API.
     *
     * @param string $id
     *   The ID of the article to get.
     *
     * @return ResponseInterface
     *   The response from the API.
     *
     * @throws GuzzleException
     */
    public function getArticle(string $id): ResponseInterface
    {
        return $this->getStackResponse("articles/$id");
    }

    /**
     * Gets the response from the Stack Exchange Teams API.
     *
     * @param string $path
     *   The path within the API endpoint.
     *
     * @return ResponseInterface
     *   The response from the API.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getStackResponse(string $path): ResponseInterface
    {
        return $this->request(
            'GET',
            $this->constructRequestUri($path),
            [
                'headers' => $this->stackHeaders,
            ]
        );
    }

    /**
     * Constructs the full request URI.
     *
     * @param string $path
     *   The path within the API endpoint.
     *
     * @return string
     *   The full request URI.
     */
    private function constructRequestUri(string $path): string
    {
        $uri = self::ENDPOINT_URI . "/$path";
        if (!empty($this->stackParams)) {
            $uri .= '?' . http_build_query($this->stackParams);
        }
        return $uri;
    }

    /**
     * Gets a list of questions from the Stack Exchange Teams API.
     *
     * @return ResponseInterface
     *   The response from the API.
     *
     * @throws GuzzleException
     */
    public function getQuestions(): ResponseInterface
    {
        return $this->getStackResponse("questions");
    }
}
