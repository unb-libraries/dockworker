<?php

namespace Dockworker;

use GuzzleHttp\Exception\GuzzleException;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use PrometheusPushGateway\PushGateway;


/**
 * Provides methods to ships metrics to a aggregation interface.
 */
trait MetricsShipperTrait {

  protected array $prometheusMetricTags = [];

  /**
   * @param array $prometheusMetricTags
   */
  protected function setPrometheusMetricTags($prometheusMetricTags) {
    $this->prometheusMetricTags = $prometheusMetricTags;
  }

  /**
   * Ships one or more metrics into a prometheus instance.
   *
   * @param array $metrics
   * @param \Dockworker\string $endpoint_url
   *
   * @return void
   * @throws \Prometheus\Exception\MetricsRegistrationException
   */
  protected function shipPrometheusMetrics(array $metrics, string $endpoint_url) {
    $adapter = new InMemory();
    $registry = new CollectorRegistry($adapter);
    $pushGateway = new PushGateway($endpoint_url);

    foreach ($metrics as $metric) {
      $gauge = $registry->getOrRegisterGauge(
        'dockworker',
        $metric['name'],
        $metric['help_text'],
        array_keys($this->prometheusMetricTags)
      );
      $gauge->set(
        $metric['value'],
        array_values($this->prometheusMetricTags)
      );
    }

    try {
      $pushGateway->push($registry, 'dockworker', $this->prometheusMetricTags);
    }
    catch (GuzzleException $ge) {
      // Don't involve the user in this failure unless debugging.
      $this->logger->debug('Metrics push to aggregator failed...');
    }
  }

}
