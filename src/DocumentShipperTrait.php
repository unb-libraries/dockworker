<?php

namespace Dockworker;

use Elasticsearch\ClientBuilder;
use RuntimeException;

/**
 * Provides methods to ships metrics to a aggregation interface.
 */
trait DocumentShipperTrait {

  protected $elasticsearchShipperClient;

  /**
   * @param string $host
   *
   * @return void
   */
  protected function setUpElasticSearchClient(string $host) {
    $hosts = [
      $host,
    ];
    $this->elasticsearchShipperClient = ClientBuilder::create()
      ->setHosts($hosts)
      ->build();
  }

  /**
   * @param array $index
   *
   * @return void
   */
  protected function createElasticSearchIndex(array $index) {
    if (!$this->elasticsearchShipperClient->indices()->exists(['index' => $index['index']])) {
      $response = $this->elasticsearchShipperClient->indices()->create($index);
    }
  }

  /**
   * @param array $document
   * @param array $index
   * @param string $endpoint_url
   *
   * @return void
   */
  protected function shipElasticSearchDocument(array $document, array $index, string $endpoint_url) {
    try {
      $this->setUpElasticSearchClient($endpoint_url);
      $this->createElasticSearchIndex($index);
      $doc_params = [
        'index' => $index['index'],
        'body' => $document
      ];
      $response = $this->elasticsearchShipperClient->index($doc_params);
    }
    catch (\Exception $e) {
      // Don't involve the user in this failure unless debugging.
      $this->logger->debug('Document push to aggregator failed...');
    }
  }

}
