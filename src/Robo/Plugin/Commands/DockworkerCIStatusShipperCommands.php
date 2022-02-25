<?php

namespace Dockworker\Robo\Plugin\Commands;

use DateTime;
use DateTimeZone;
use Dockworker\DockworkerException;
use Dockworker\CIServicesTrait;
use Dockworker\ElasticShipperTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Defines a class to interact with CI Services.
 */
class DockworkerCIStatusShipperCommands extends DockworkerCommands {

  use CIServicesTrait;
  use ElasticShipperTrait;

  const ELASTICSEARCH_ENDPOINT_HOST = 'http://documents.lib.unb.ca:9200';

  /**
   * Ships the build details for a CI run into the aggregator.
   *
   * @param string $id
   *   The ID of the workflow to ship.
   *
   * @command ci:ship:build-details
   *
   * @usage ci:ship:build-details
   */
  public function shipCiBuildDetails($id) {
    $this->initSetupCommand();
    $this->say("Finding build, id=$id...");
    $run = $this->getCIServicesWorkflowRunById($id);
    $this->shipRunDetails($run);
  }

  private function initSetupCommand() {
    $this->initSetupGitHubTrait();
    $this->initSetupCIServicesTrait();
  }

  private function shipRunDetails($run) {
    $index = [
      'index' => 'github_actions_builds',
      'body' => [
        'mappings' => [
          'properties' => [
            'id' => [
              'type' => 'keyword'
            ],
            'timestamp' => [
              'type' => 'date'
            ],
            'repo_owner' => [
              'type' => 'keyword'
            ],
            'commit_hash' => [
              'type' => 'keyword'
            ],
            'committer' => [
              'type' => 'keyword'
            ],
            'env' => [
              'type' => 'keyword'
            ],
            'instance' => [
              'type' => 'keyword'
            ],
            'status' => [
              'type' => 'keyword'
            ],
            'conclusion' => [
              'type' => 'keyword'
            ],
          ],
        ]
      ]
    ];
    $this->shipElasticSearchDocument(
      [
        'id' => $run['id'],
        'timestamp' => round(microtime(TRUE) * 1000),
        'repo_owner' => $run['repository']['owner']['login'],
        'commit_hash' => $run['head_commit']['id'],
        'committer' => $run['head_commit']['committer']['email'],
        'env' => $run['head_branch'],
        'instance' => $this->instanceName,
        'status' => $run['status'],
        'conclusion' => $run['conclusion'],
      ],
      $index,
      self::ELASTICSEARCH_ENDPOINT_HOST
    );
  }

}
