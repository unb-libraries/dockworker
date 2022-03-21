<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Elasticsearch\ClientBuilder;

/**
 * Provides methods to ships metrics to an elastic aggregation interface.
 */
trait ElasticShipperTrait {

  protected $elasticsearchShipperClient;

  /**
   * Sets up the elasticsearch client for use.
   *
   * @param $uri
   *   The URI to the elasticsearch instance, including the port.
   * @option $auth-user
   *   If basic authentication is needed, the username. Defaults to none.
   * @option $auth-pass
   *   If basic authentication is needed, the password. Defaults to none.
   *
   * @param string $auth_pass
   *
   * @return void
   */
  protected function setUpElasticSearchClient(string $uri, string $auth_user = '', string $auth_pass = '') {
    $client_builder = ClientBuilder::create()
      ->setHosts([
        $uri,
      ]);

    // Password could be empty. Only check for user.
    if (!empty($auth_user)) {
      $client_builder->setBasicAuthentication($auth_user, $auth_pass);
    }

    $this->elasticsearchShipperClient = $client_builder->build();
  }

  /**
   * Creates an elasticsearch index if one does not already exist.
   *
   * @param string[] $index
   *   The index properties.
   *
   * @return void
   */
  protected function createElasticSearchIndex(array $index) {
    if (!$this->elasticsearchShipperClient->indices()->exists(['index' => $index['index']])) {
      $response = $this->elasticsearchShipperClient->indices()->create($index);
    }
  }

  /**
   * Indexes a document in the target elasticsearch instance.
   *
   * @param string[] $document
   *   An associative array defining the document that will be indexed as JSON.
   * @param string[] $index
   *   The index properties.
   * @param string $uri
   *   The URI to the elasticsearch instance, including the port.
   *
   * @option $auth-user
   *   If basic authentication is needed, the username. Defaults to none.
   * @option $auth-pass
   *   If basic authentication is needed, the password. Defaults to none.
   *
   * @return void
   */
  protected function shipElasticSearchDocument(array $document, array $index, string $uri, string $auth_user = '', string $auth_pass = '') {
    try {
      $this->setUpElasticSearchClient($uri, $auth_user, $auth_pass);
      $this->createElasticSearchIndex($index);
      $doc_params = [
        'index' => $index['index'],
        'body' => $document
      ];
      $response = $this->elasticsearchShipperClient->index($doc_params);
      $this->say('Eh');
    }
    catch (\Exception $e) {
      throw new DockworkerException('Document push to elasticsearch failed!');
    }
  }

}
