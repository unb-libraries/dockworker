<?php

namespace Dockworker\StackOverflow;

use Dockworker\IO\DockworkerIO;
use Dockworker\Storage\DockworkerPersistentDataStorageTrait;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides a client to interface with StackOverflow Teams.
 *
 * @phpstan-ignore-next-line
 */
class StackOverflowTeamsClient extends GuzzleClient
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
     * Creates a new StackOverflow Teams API client.
     *
     * @param string $team_slug
     *   The team slug to use.
     * @param string $api_key
     *   The API key to use.
     *
     * @return static
     *   The new client, or NULL if the client could not be created.
     */
    public static function setCreateClient(
        string $team_slug,
        string $api_key
    ): self {
        $obj = new self();
        $obj->addHeaderItem('X-API-Access-Token', $api_key);
        $obj->addParamItem('team', $team_slug);
        // @phpstan-ignore-next-line
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
     * Gets a list of questions from the StackOverflow Teams API.
     *
     * @return ResponseInterface
     *   The response from the API.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getQuestions(): ResponseInterface
    {
        return $this->stackGetRequest("questions");
    }

    /**
     * Retrieves a GET response from the StackOverflow Teams API.
     *
     * @param string $path
     *   The path within the API endpoint.
     *
     * @return ResponseInterface
     *   The response from the API.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function stackGetRequest(string $path): ResponseInterface
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
     * Updates an existing article's body in the StackOverflow Teams API.
     *
     * @param $id
     *   The ID of the article to update.
     * @param $body
     *   The new body to set.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateArticleBody(
        DockworkerIO $io,
        int $id,
        string $body
    ): void {
        $article = $this->getArticle($id);
        $response = $this->writeArticle(
            $id,
            $article->title,
            $body,
            implode(';`', $article->tags),
            $article->article_type
        );
        if ($response->getStatusCode() != 200) {
            throw new Exception(
                sprintf(
                    'Error updating article %s in StackOverflow Teams API. Status code: %s',
                    $id,
                    $response->getStatusCode()
                )
            );
        }
        $io->say(sprintf('Updated StackOverflow Teams Article ID#%s', $id));
    }

    /**
     * Gets an article from the StackOverflow Teams API.
     *
     * @param int $id
     *   The article ID to retrieve.
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getArticle(int $id): mixed
    {
        $article_list = $this->getArticles([$id]);
        if (!empty($article_list[0])) {
            return $article_list[0];
        }
        return null;
    }

    /**
     * Gets a list of articles from the StackOverflow Teams API.
     *
     * @param int[] $ids
     *   The IDs of the articles to get.
     *
     * @return object[]|null
     *   An array of article objects.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getArticles(array $ids): mixed
    {
        $id_string = implode(';', $ids);
        $response = $this->stackGetRequest("articles/$id_string");
        if ($response->getStatusCode() != 200) {
            throw new Exception(
                sprintf(
                    'Error retrieving article %s from StackOverflow Teams API. Status code: %s',
                    $id_string,
                    $response->getStatusCode()
                )
            );
        }
        $response_data = json_decode(
            $response->getBody()->getContents()
        );
        if (!empty($response_data->items)) {
            return $response_data->items;
        }
        return null;
    }

    /**
     * Updates an existing article's content in the StackOverflow Teams API.
     *
     * @param int $id
     *   The ID of the article to update.
     * @param string $title
     *   The article's title to set.
     * @param string $body
     *   The article's body to set.
     * @param string $tags
     *   The article's associated tags to set. Comma-separated.
     * @param string $article_type
     *   The article's type to set. One of 'knowledge-article', 'announcement',
     *   'policy', 'how-to-guide'.
     *
     * @return ResponseInterface
     *   The response from the API.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function writeArticle(
        int $id,
        string $title,
        string $body,
        string $tags,
        string $article_type
    ): ResponseInterface {
        return $this->stackPostRequest(
            "articles/$id/edit",
            [
                'title' => $title,
                'body' => $body,
                'tags' => $tags,
                'article_type' => $article_type,
            ]
        );
    }

    /**
     * POSTs a request to the StackOverflow Teams API.
     *
     * @param string $path
     *   The path within the API endpoint.
     * @param string[] $data
     *   An associative array of form param data.
     *
     * @return ResponseInterface
     *   The response from the API.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function stackPostRequest(
        string $path,
        array $data
    ): ResponseInterface {
        return $this->request(
            'POST',
            $this->constructRequestUri($path),
            [
                'headers' => $this->stackHeaders,
                'form_params' => $data,
            ]
        );
    }
}
