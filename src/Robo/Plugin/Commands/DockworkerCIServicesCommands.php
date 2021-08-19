<?php

namespace Dockworker\Robo\Plugin\Commands;

use DateTime;
use DateTimeZone;
use Dockworker\ConsoleTableTrait;
use Dockworker\DockworkerException;
use Dockworker\CIServicesTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Defines a class to interact with CI Services.
 */
class DockworkerCIServicesCommands extends DockworkerCommands {

  use ConsoleTableTrait;
  use CIServicesTrait;

  /**
   * The current progress bar.
   *
   * @var \Symfony\Component\Console\Helper\ProgressBar
   */
  protected object $gitHubWorkflowRunProgressBar;

  /**
   * The usage stats for the workflow runs.
   *
   * @var array
   */
  protected array $gitHubWorkflowRunUsage = [];

  /**
   * Gets the latest CI Services build/deploy times for the repository.
   *
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option string $branch
   *   The environment/branch to target. Defaults to 'prod'.
   *
   * @command ci:deploy-times
   * @aliases ci-deploy-times
   * @aliases cidt
   *
   * @usage ci:deploy-times --branch=prod
   *
   * @github
   * @ci
   */
  public function getCIServicesDeployTimes(array $options = ['branch' => 'prod']) {
    $runs = $this->getCIServicesWorkflowRunsByBranch($options['branch']);
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
   * @param $max
   *   The number of values to iterate over.
   */
  protected function setInitProgressBar($max) {
    $this->gitHubWorkflowRunProgressBar = new ProgressBar($this->io(), $max);
    $this->gitHubWorkflowRunProgressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% -- %message%');
    $this->gitHubWorkflowRunProgressBar->start();
  }

  /**
   * Sets the usage data for a CI Services run.
   *
   * @param array $run
   *   The CI Services run associative array.
   */
  protected function setCIServicesRunUsageData(array $run) {
    $this->gitHubWorkflowRunProgressBar->setMessage("Querying Workflow Run #{$run['id']}");
    $usage = $this->gitHubClient->api('repo')->workflowRuns()->usage($this->gitHubOwner, $this->gitHubRepo, $run['id']);
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
  protected function getCIServicesRunUsageDataTableHeaders() {
    $zone = date_default_timezone_get();
    return ['Run ID', "Time ($zone)", 'Commit', 'Time(s)', 'Δ'];
  }

  /**
   * Formats the run usage data for display in a console table.
   *
   * @return array
   *   The properly formatted usage data.
   */
  protected function getCIServicesRunUsageDataTableRows() {
    $rows = [];
    $prev_run_time = 0;
    $local_tz = new DateTimeZone(date_default_timezone_get());
    foreach (array_reverse($this->gitHubWorkflowRunUsage) as $usage) {
      $rows[] = [
        $usage['id'],
        $this->getFormattedTimeString($usage['created_at'], $local_tz),
        "https://github.com/{$this->gitHubOwner}/{$this->gitHubRepo}/commit/{$usage['head_sha']}",
        $usage['run_duration_ms'] / 1000,
        $prev_run_time == 0 ? "--" : $this->getFormattedPercentageDifference($usage['run_duration_ms'], $prev_run_time),
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
  protected function getFormattedPercentageDifference($cur_time, $prev_time) {
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
   * @param \DateTimeZone $local_tz
   *   The local timezone.
   *
   * @return string
   *   The difference, expressed as a percentage.
   */
  protected function getFormattedTimeString($time_string, DateTimeZone $local_tz) {
    $utc_time = DateTime::createFromFormat(DateTime::ISO8601, $time_string, new DateTimeZone('UTC'));
    $local_tz = new DateTimeZone(date_default_timezone_get());
    $local_time = $utc_time->setTimezone($local_tz);
    return $local_time->format('Y-m-d H:i:s');
  }

}
