<?php

namespace Dockworker;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

/**
 * Provides methods to ships logs to a aggregation interface.
 */
trait LogShippperTrait {

  /**
   * Ships logs into the remote aggregator.
   *
   * @param string $logs
   *   The log data to ship.
   * @param string $type
   * @param string $deployment_id
   * @param string $endpoint_url
   * @param bool $split_lines
   *
   * @return void
   */
  protected function shipLogs($logs, $type, $deployment_id, $endpoint_url, $split_lines = TRUE) {
    $log_array = [];
    $cur_time_ns = intval(microtime(TRUE)*1000*1000*1000);
    if ($split_lines) {
      foreach (preg_split("/\r\n|\n|\r/", $logs) as $log_line) {
        $log_array[] = [
          (string) $cur_time_ns,
          $log_line
        ];
        $cur_time_ns++;
      }
    }
    else {
      $log_array[] = [
        (string) $cur_time_ns,
        $logs
      ];
    }

    if (!empty($log_array)) {
      $log_format = [
        'streams' => [
          [
            'stream' => [
              'deployment' => $deployment_id,
              'type' => $type,
            ],
            'values' => $log_array,
          ]
        ]
      ];

      $client = new Client();
      try {
        $response = $client->post(
          $endpoint_url,
          [
            RequestOptions::JSON => $log_format
          ]
        );
      }
      catch (GuzzleException $ge) {
        // Don't involve the user in this failure unless debugging.
        $this->logger->debug('Log push to aggregator failed...');
      }
    }
  }

}
