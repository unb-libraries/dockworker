<?php

namespace Dockworker\Robo\Plugin\Commands;

use DateTime;
use DateTimeZone;
use Dockworker\CIServicesTrait;
use Dockworker\DockworkerException;
use Dockworker\ElasticShipperTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Defines a class to interact ship CI Services results.
 */
class DockworkerCIStatusShipperCommands extends DockworkerCommands {

  use CIServicesTrait;
  use ElasticShipperTrait;

  /**
   * Ships the build details for a CI run into the aggregator.
   *
   * @param string $id
   *   The ID of the CI workflow to ship.
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option string $es-uri
   *   The URI (including port) to elasticsearch. Defaults to localhost:9200.
   * @option string $auth-user
   *   If basic authentication is needed, the username. Defaults to none.
   * @option string $auth-pass
   *   If basic authentication is needed, the password. Defaults to none.
   * @option string $ci-build-status
   *   Sets the build status. Defaults to retrieved status.
   * @option string $ci-build-conclusion
   *   Sets the build conclusion. Defaults to retrieved conclusion.
   *
   * @command ci:ship:build-details
   *
   * @usage ci:ship:build-details
   */
  public function shipCiBuildDetails($id, array $options = ['es-uri' => 'localhost:9200', 'auth-user' => '', 'auth-pass' => '', 'ci-build-status' => '', 'ci-build-conclusion' => '']) {
    $this->options = $options;
    $this->initSetupCommand();
    $this->say("Finding build, id=$id...");
    $run = $this->getCIServicesWorkflowRunById($id);
    if (!empty($run)) {
      $this->say("Build id=$id found!");
      $this->shipRunDetails($run);
    }
    else {
      $this->say("Stopping, no [$this->instanceName] build found with id=$id...");
    }
  }

  /**
   * Initializes and sets up the CI Shipping command.
   *
   * @return void
   */
  private function initSetupCommand() {
    $this->initSetupGitHubTrait();
    $this->initSetupCIServicesTrait();
  }

  /**
   * Ships the build details for a CI run into the aggregator.
   *
   * @param string[] $run
   *   The workflow run detail array retrieved from the GitHub API.
   *
   * @return void
   */
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
    if (empty($this->options['ci-build-status'])) {
      $ci_build_status = $run['ci-build-status'];
    }
    if (empty($this->options['ci-build-conclusion'])) {
      $ci_build_conclusion = $run['ci-build-conclusion'];
    }

    $this->shipElasticSearchDocument(
      [
        'id' => $run['id'],
        'timestamp' => round(microtime(TRUE) * 1000),
        'repo_owner' => $run['repository']['owner']['login'],
        'commit_hash' => $run['head_commit']['id'],
        'committer' => $run['head_commit']['committer']['email'],
        'env' => $run['head_branch'],
        'instance' => $this->instanceName,
        'status' => $ci_build_status,
        'conclusion' => $ci_build_conclusion,
      ],
      $index,
      $this->options['es-uri'],
      $this->options['auth-user'],
      $this->options['auth-pass']
    );
  }

}
