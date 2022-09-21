<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerException;
use Dockworker\ElasticShipperTrait;
use Dockworker\GitHubActionsTrait;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Defines a class to interact ship CI Services results.
 */
class DockworkerCIStatusShipperCommands extends DockworkerCommands {

  use GitHubActionsTrait;
  use ElasticShipperTrait;

  /**
   * Ships details for a specific CI Services workflow run for this application into an aggregator.
   *
   * @param string $id
   *   The ID of the CI workflow to ship.
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $es-uri
   *   The URI (including port) to an elasticsearch instance. Defaults to localhost:9200.
   * @option $auth-user
   *   If basic authentication is needed, the username. Defaults to none.
   * @option $auth-pass
   *   If basic authentication is needed, the password. Defaults to none.
   *
   * @command ci:workflow:run:ship
   */
  public function shipCiBuildDetails($id, array $options = ['es-uri' => 'localhost:9200', 'auth-user' => '', 'auth-pass' => '']) {
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
    $this->initSetupGitHubActionsTrait();
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
