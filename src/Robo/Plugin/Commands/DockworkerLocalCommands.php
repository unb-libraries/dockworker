<?php

namespace Dockworker\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Dockworker\DockworkerException;
use Dockworker\DockworkerLogCheckerTrait;
use Dockworker\GitRepoTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerBaseCommands;

/**
 * Defines the commands used to interact with a Dockworker local application.
 */
class DockworkerLocalCommands extends DockworkerBaseCommands implements CustomEventAwareInterface {

  const ERROR_BUILDING_IMAGE = 'Error reported building image!';
  const ERROR_PULLING_UPSTREAM_IMAGE = 'Error pulling upstream image %s';

  use CustomEventAwareTrait;
  use DockworkerLogCheckerTrait;
  use GitRepoTrait;

  /**
   * The string in the logs that indicates the deployment has finished.
   */
  private string $localFinishMarker;

  /**
   * Cleans up any unused local docker container assets.
   *
   * This command removes unused (orphaned) docker images, volumes and networks
   * with extreme prejudice. It does not restrict itself to aforementioned
   * assets associated with this application only, rather it removes them
   * system-wide.
   *
   * @command docker:cleanup
   */
  public function localCleanup() {
    $this->say("Cleaning up dangling images and volumes:");
    $this->_exec('docker system prune -af');
  }

  /**
   * Provides log checker with ignored log exception items for local.
   *
   * @hook on-event dockworker-local-log-error-exceptions
   */
  public function getCoreErrorLogLocalExceptions() {
    return [
      'Operation CREATE USER failed' => 'Creating a local user failing is expected in deployment',
      'errors=0' => 'A report of zero errors is not an error',
    ];
  }

  /**
   * Halts this local application and removes its persistent data permanently.
   *
   * @command local:destroy
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function localDestroy() {
    $this->_exec('docker-compose kill');
    $this->setRunOtherCommand('local:rm');
  }

  /**
   * Halts this local application, removes its persistent data, and resets all repo files to the state at last commit.
   *
   * @command local:hard-reset
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function localHardReset() {
    $this->setRunOtherCommand('local:destroy');
    $this->setRunOtherCommand('dockworker:permissions:fix');
    $this->_exec('git reset --hard HEAD');
    $this->_exec('git clean -df');
  }

  /**
   * Displays all of this local application's previous logs.
   *
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $all
   *   Display logs from all local services, not only the web endpoint.
   *
   * @command logs:local
   * @throws \Exception
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function printLocalLogs(array $options = ['all' => FALSE]) {
    $this->io()->writeln(
      $this->getLocalLogs($options)
    );
  }

  /**
   * Retrieves all of this local application's previous logs.
   *
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $all
   *   Return logs from all local services, not only the web endpoint.
   *
   * @return string
   *   The logs.
   */
  protected function getLocalLogs(array $options = ['all' => FALSE]) {
    $cmd = $this->taskExec('docker-compose')
      ->dir($this->repoRoot)
      ->silent(TRUE)
      ->printOutput(FALSE)
      ->arg('logs');

    if (isset($options['all']) && !$options['all']) {
      $cmd->arg($this->instanceName);
    }
    $result = $cmd->run();
    return $result->getMessage();
  }

  /**
   * Builds this application's docker image, including modifications specified by its local docker-compose file.
   *
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $no-cache
   *   Do not use any cached steps in the build.
   *
   * @command local:build
   * @aliases build
   * @throws \Dockworker\DockworkerException
   */
  public function build(array $options = ['no-cache' => FALSE]) {
    $this->io()->title("Building application theme");
    $this->setRunOtherCommand('theme:build-all');

    if ($options['no-cache']) {
      $command = 'docker-compose build --no-cache';
    }
    else {
      $command = 'docker-compose build';
    }

    $this->io()->title("Building image");
    if (!$this->_exec($command)->wasSuccessful()) {
      throw new DockworkerException(
        self::ERROR_BUILDING_IMAGE
      );
    }
  }

  /**
   * Builds this application's theme assets into a distributable state.
   *
   * @command theme:build-all
   * @aliases build-themes
   */
  public function buildThemes() {
  }

  /**
   * Pulls the newest version of docker images required to deploy this application's local deployment.
   *
   * @command docker:image:pull-upstream
   * @throws \Dockworker\DockworkerException
   */
  public function pullUpstreamImages() {
    $this->io()->title("Fetching Upstream Images");
    $this->_exec('docker-compose pull --quiet --include-deps');
    $upstream_images = $this->getUpstreamImages();
    foreach ($upstream_images as $upstream_image) {
      $result= $this->taskDockerPull($upstream_image)->silent(TRUE)->run();
      if ($result->getExitCode() > 0) {
        throw new DockworkerException(
          sprintf(
            self::ERROR_PULLING_UPSTREAM_IMAGE,
            $upstream_image
          )
        );
      }
    }
  }

  /**
   * Deletes any persistent data from this application's stopped local deployment.
   *
   * @command local:rm
   * @aliases rm
   *
   * @return \Robo\Result
   *   The result of the removal command.
   */
  public function removeData() {
    $this->io()->title("Removing application data");
    return $this->taskExec('docker-compose')
      ->dir($this->repoRoot)
      ->arg('down')
      ->arg('--rmi')
      ->arg('local')
      ->arg('-v');
  }

  /**
   * Determines if this application's local deployment logs contain errors.
   *
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $all
   *   Check logs from all local services, not only the web endpoint.
   *
   * @command logs:check:local
   * @throws \Dockworker\DockworkerException
   */
  public function localLogsCheck(array $options = ['all' => FALSE]) {
    $this->getCustomLogTriggersExceptions('local');
    $local_logs = $this->getLocalLogs($options);
    if (!empty($local_logs)) {
      $this->checkLogForErrors('local', $local_logs);
    }
    else {
      $this->io()->title("No logs for local instance!");
    }
    try {
      $this->auditApplicationLogs(FALSE);
      $this->say("No errors found in logs.");
    }
    catch (DockworkerException) {
      $this->printLocalLogs();
      $this->printApplicationLogErrors();
      throw new DockworkerException("Error(s) found in local logs!");
    }
  }

}
