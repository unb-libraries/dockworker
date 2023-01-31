<?php

namespace Dockworker\Robo\Plugin\Commands;

use DateTime;
use DateTimeZone;
use Dockworker\ConsoleTableTrait;
use Dockworker\GitHubActionsTrait;
use Symfony\Component\Console\Helper\ProgressBar;
use Dockworker\Robo\Plugin\Commands\DockworkerBaseCommands;

/**
 * Defines a class to interact with CI Services.
 */
class DockworkerCIServicesCommands extends DockworkerBaseCommands {

  use ConsoleTableTrait;
  use GitHubActionsTrait;

  /**
   * The current progress bar.
   *
   * @var \Symfony\Component\Console\Helper\ProgressBar
   */
  protected ProgressBar $gitHubWorkflowRunProgressBar;

  /**
   * The usage stats for the workflow runs.
   *
   * @var array
   */
  protected array $gitHubWorkflowRunUsage = [];

  /**
   * Restarts the most recent CI workflow run for this application.
   *
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $branch
   *   The environment/branch to target. Defaults to 'dev'.
   *
   * @command ci:workflow:run:latest:restart
   * @aliases restart-latest-build
   * @aliases cirl
   *
   * @usage --branch=dev
   *
   * @github
   * @ci
   */
  public function getRestartLatestCiBuild(
    array $options = ['branch' => 'dev'])
  : void {
    $this->say("Finding latest build, branch={$options['branch']}...");
    $run = $this->getGitHubActionsWorkflowLatestRunByBranch($options['branch']);
    $this->setRestartGitHubActionsWorkflowRun($run['id']);
  }

  /**
   * Displays runtimes for recent CI workflow runs for this application.
   *
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $branch
   *   The environment/branch to target. Defaults to 'prod'.
   *
   * @command ci:workflow:run:times
   * @aliases ci-deploy-times
   * @aliases cidt
   *
   * @usage --branch=prod
   *
   * @github
   * @ci
   */
  public function getCIServicesDeployTimes(
    array $options = ['branch' => 'prod']
  ) : void {
    $runs = $this->getGitHubActionsRunsByBranch($options['branch']);
    if (!empty($runs)) {
      $num_runs = count($runs);
      $this->setInitProgressBar($num_runs);
      foreach ($runs as $run) {
        $this->setCIServicesRunUsageData($run);
      }
      $this->gitHubWorkflowRunProgressBar->setMessage("Done!");
      $this->gitHubWorkflowRunProgressBar->finish();
      $this->setDisplayConsoleTable(
        $this->io(),
        $this->getCIServicesRunUsageDataTableHeaders(),
        $this->getCIServicesRunUsageDataTableRows(),
        "Latest $num_runs {$this->gitHubRepo} [{$options['branch']}] Builds"
      );
    }
    else {
      $this->say("No workflow runs found for env: {$options['branch']}!");
    }
  }

  /**
   * Initializes the progress bar for iterations.
   *
   * @param int $max
   *   The number of values to iterate over.
   */
  protected function setInitProgressBar(int $max) : void {
    $this->gitHubWorkflowRunProgressBar = new ProgressBar($this->io(), $max);
    $this->gitHubWorkflowRunProgressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% -- %message%');
    $this->gitHubWorkflowRunProgressBar->start();
  }

  /**
   * Sets the usage data for a CI Services run.
   *
   * @param array $run
   *   The CI Services run ID
   */
  protected function setCIServicesRunUsageData(array $run) : void {
    $this->gitHubWorkflowRunProgressBar->setMessage(
      "Querying Workflow Run #{$run['id']}"
    );
    $usage = $this->gitHubClient->api('repo')->workflowRuns()
      ->usage($this->gitHubOwner, $this->gitHubRepo, $run['id']);
    $usage['head_sha'] = $run['head_sha'];
    $usage['created_at'] = $run['created_at'];
    $usage['id'] = $run['id'];
    $this->gitHubWorkflowRunUsage[] = $usage;
    $this->gitHubWorkflowRunProgressBar->advance();
  }

  /**
   * Formats the run usage data headers for display in a console table.
   *
   * @return array
   *   The properly formatted headers.
   */
  protected function getCIServicesRunUsageDataTableHeaders() : array {
    $zone = date_default_timezone_get();
    return ['Run ID', "Time ($zone)", 'Commit', 'Time(s)', 'Δ'];
  }

  /**
   * Formats the run usage data for display in a console table.
   *
   * @return array
   *   The properly formatted usage data.
   */
  protected function getCIServicesRunUsageDataTableRows() : array {
    $rows = [];
    $prev_run_time = 0;
    $local_tz = new DateTimeZone(date_default_timezone_get());
    foreach (array_reverse($this->gitHubWorkflowRunUsage) as $usage) {
      $rows[] = [
        $usage['id'],
        $this->getFormattedTimeString($usage['created_at'], $local_tz),
        "https://github.com/{$this->gitHubOwner}/{$this->gitHubRepo}/commit/{$usage['head_sha']}",
        $usage['run_duration_ms'] / 1000,
        $prev_run_time == 0 ? "--" : $this->getFormattedPercentageDifference(
          $usage['run_duration_ms'],
          $prev_run_time
        ),
      ];
      $prev_run_time = $usage['run_duration_ms'];
    }
    return $rows;
  }

  /**
   * Calculates and formats the change in runtime vs a previous run.
   *
   * @param int $cur_time
   *   The current runtime, in seconds.
   * @param int $prev_time
   *   The previous runtime, in seconds.
   *
   * @return string
   *   The difference, expressed as a percentage.
   */
  protected function getFormattedPercentageDifference(
    int $cur_time,
    int $prev_time
  ) : string {
    return sprintf(
      "%+d%%",
      (($cur_time / $prev_time) - 1) * 100,
    );
  }

  /**
   * Formats and displays a Zulu time string in the local timezone.
   *
   * @param string $time_string
   *   The time string, in ISO08601 Format (Zulu).
   *   The local timezone.
   *
   * @return string
   *   The difference, expressed as a percentage.
   */
  protected function getFormattedTimeString(
    string $time_string,
    DateTimeZone $local_tz
  ) : string {
    $utc_time = DateTime::createFromFormat(
      DateTime::ISO8601,
      $time_string,
      new DateTimeZone('UTC')
    );
    $local_tz = new DateTimeZone(date_default_timezone_get());
    $local_time = $utc_time->setTimezone($local_tz);
    return $local_time->format('Y-m-d H:i:s');
  }

}
