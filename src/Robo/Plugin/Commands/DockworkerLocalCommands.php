<?php

namespace Dockworker\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Dockworker\DockworkerException;
use Dockworker\DockworkerLogCheckerTrait;
use Dockworker\GitRepoTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;
use Droath\RoboDockerCompose\Task\loadTasks;
use Robo\Robo;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Defines the commands used to interact with a Dockworker local application.
 */
class DockworkerLocalCommands extends DockworkerCommands implements CustomEventAwareInterface {

  const ERROR_BUILDING_IMAGE = 'Error reported building image!';
  const ERROR_CONTAINER_MISSING = 'The %s local deployment does not appear to exist.';
  const ERROR_CONTAINER_STOPPED = 'The %s local deployment appears to be stopped.';
  const ERROR_PULLING_UPSTREAM_IMAGE = 'Error pulling upstream image %s';
  const ERROR_UPDATING_HOSTFILE = 'Error updating hostfile!';
  const WAIT_DEPLOYMENT_CYCLE_LENGTH = 1;
  const WAIT_DEPLOYMENT_MAX_REPEATS = 300;

  use CustomEventAwareTrait;
  use DockworkerLogCheckerTrait;
  use GitRepoTrait;
  use loadTasks;

  /**
   * The string in the logs that indicates the deployment has finished.
   *
   * @var string
   */
  private $localFinishMarker;

  /**
   * Clean up unused local docker assets.
   *
   * This command removes unused (orphaned) docker images, volumes and networks
   * with extreme prejudice. It does not restrict itself to aforementioned
   * assets associated with this application only, rather it removes them
   * system-wide.
   *
   * @command dockworker:docker:cleanup
   *
   * @usage dockworker:docker:cleanup
   */
  public function localCleanup() {
    $this->say("Cleaning up dangling images and volumes:");
    $this->_exec('docker system prune -af');
  }

  /**
   * Halts the local application without removing any persistent data.
   *
   * Following a halt, the application can be restarted with the 'start'
   * command, and all data will be preserved.
   *
   * @command local:halt
   *
   * @usage local:halt
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function localHalt() {
    $this->_exec('docker-compose stop --timeout 10');
  }

  /**
   * Halts the local application and removes any persistent data.
   *
   * @command local:destroy
   *
   * @usage local:destroy
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function localDestroy() {
    $this->_exec('docker-compose kill');
    $this->setRunOtherCommand('local:rm');
  }

  /**
   * Destroys the local application, and removes any uncommitted repo changes.
   *
   * @command local:hard-reset
   *
   * @usage local:hard-reset
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
   * Displays the local application's container logs.
   *
   * @param string[] $opts
   *   An array of options to pass to the builder.
   *
   * @option bool $all
   *   Display logs from all local services, not only the web endpoint.
   *
   * @command local:logs
   * @throws \Exception
   *
   * @usage local:logs
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function printLocalLogs(array $opts = ['all' => FALSE]) {
    $this->getlocalRunning();
    $result = $this->getLocalLogs($opts);
    $this->io()->writeln(
      $result->getMessage()
    );
    return $result;
  }

  /**
   * Checks if the local application is running.
   *
   * @throws \Dockworker\DockworkerException
   */
  public function getLocalRunning() {
    $container_name = $this->instanceName;

    exec(
      "docker inspect -f {{.State.Running}} $container_name 2>&1",
      $output,
      $return_code
    );

    // Check if container exists.
    if ($return_code > 0) {
      throw new DockworkerException(sprintf(self::ERROR_CONTAINER_MISSING, $container_name));
    }

    // Check if container stopped.
    if ($output[0] == "false") {
      throw new DockworkerException(sprintf(self::ERROR_CONTAINER_STOPPED, $container_name));
    }
  }

  /**
   * Gets logs from the local application container.
   *
   * @param string[] $opts
   *   An array of options to pass to the builder.
   *
   * @option bool $all
   *   Return logs from all local services, not only the web endpoint.
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  protected function getLocalLogs(array $opts = ['all' => FALSE]) {
    $result = $this->taskExec('docker-compose')
      ->dir($this->repoRoot)
      ->silent(TRUE)
      ->printOutput(FALSE)
      ->arg('logs');

    if (isset($opts['all']) && !$opts['all']) {
      $result->arg($this->instanceName);
    }

    return $result->run();
  }

  /**
   * Display previous local application container logs and monitor for new ones.
   *
   * @param string[] $opts
   *   An array of options to pass to the builder.
   *
   * @option bool $all
   *   Display logs from all local services, not only the web endpoint.
   * @option bool $timestamps
   *   Display a timestamp for each line of the logs.
   *
   * @command local:logs:tail
   * @aliases logs
   * @throws \Exception
   *
   * @usage local:logs:tail
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function tailLocalLogs(array $opts = ['all' => FALSE, 'timestamps' => FALSE]) {
    $this->getLocalRunning();
    $log_dump_cmd = 'docker-compose logs';

    if ($opts['timestamps']) {
      $log_dump_cmd .= ' --timestamps';
    }

    if ($opts['all']) {
      passthru("$log_dump_cmd --tail='all'");
      return passthru("$log_dump_cmd --tail=0 --follow");
    }
    else {
      passthru("$log_dump_cmd --tail='all' {$this->instanceName}");
      return passthru("$log_dump_cmd  --tail=0 --follow {$this->instanceName}");
    }
  }

  /**
   * Builds the local application's docker image.
   *
   * @param string[] $opts
   *   An array of options to pass to the builder.
   *
   * @option bool $no-cache
   *   Do not use any cached steps in the build.
   *
   * @command local:build
   * @aliases build
   * @throws \Dockworker\DockworkerException
   *
   * @usage local:build
   */
  public function build(array $opts = ['no-cache' => FALSE]) {
    $this->io()->title("Building application theme");
    $this->setRunOtherCommand('theme:build-all');

    if ($opts['no-cache']) {
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
   * Builds the local application's deployable theme assets from source.
   *
   * @command theme:build-all
   * @aliases build-themes
   *
   * @usage theme:build-all
   */
  public function buildThemes() {
  }

  /**
   * Opens the local application container's shell.
   *
   * @command local:shell
   * @aliases shell
   * @throws \Exception
   *
   * @usage local:shell
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function openLocalShell() {
    return $this->taskDockerExec($this->instanceName)
      ->interactive()
      ->option('-t')
      ->exec('sh')
      ->run();
  }

  /**
   * Pulls any upstream images used in building the local application image.
   *
   * @command local:pull-upstream
   * @throws \Dockworker\DockworkerException
   *
   * @usage local:pull-upstream
   */
  public function pullUpstreamImages() {
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
   * Removes removes all persistent data from the local docker application.
   *
   * @command local:rm
   * @aliases rm
   *
   * @usage local:rm
   *
   * @return \Robo\Result
   *   The result of the removal command.
   */
  public function removeData() {
    $this->unSetHostFileEntries();
    $this->io()->title("Removing application data");
    return $this->taskExec('docker-compose')
      ->dir($this->repoRoot)
      ->arg('rm')
      ->arg('-f')
      ->arg('-v');
  }

  /**
   * Builds and deploys the local application, displaying the application logs.
   *
   * @param string[] $opts
   *   An array of options to pass to the builder.
   *
   * @option bool $no-cache
   *   Do not use any cached steps in the build.
   * @option bool $no-tail-logs
   *   Do not tail the application logs after starting.
   * @option bool $no-update-dockworker
   *   Do not update dockworker as part of the startup process.
   * @option bool $no-update-hostfile
   *   Do not update the local hostfile with the application alias.
   * @option bool $no-upstream-pull
   *   Do not pull the upstream docker images before building.
   * @option bool $no-build
   *   Do not build any images before starting.
   * @option bool $only-start
   *   Alias for --no-update-dockworker --no-update-hostfile --no-upstream-pull --no-build
   * @option bool $force-recreate
   *   Pass a --force-recreate option to docker-compose up.
   *
   * @command local:start
   * @aliases start
   * @throws \Exception
   *
   * @usage local:start
   */
  public function start(array $opts = ['no-cache' => FALSE, 'no-tail-logs' => FALSE, 'no-update-dockworker' => FALSE, 'no-update-hostfile' => FALSE, 'no-upstream-pull' => FALSE, 'no-build' => FALSE, 'only-start' => FALSE, 'force-recreate' => FALSE]) {
    if (!$opts['no-update-dockworker'] && !$opts['only-start']) {
      $this->setRunOtherCommand('dockworker:update');
    }

    if (!$opts['no-update-hostfile'] && !$opts['only-start']) {
      $this->setRunOtherCommand('local:update-hostfile');
    }

    if (!$opts['no-cache'] && !$opts['no-upstream-pull'] && !$opts['only-start']) {
      $this->setRunOtherCommand('local:pull-upstream');
    }

    if (!$opts['no-build'] && !$opts['only-start']) {
      $build_command = 'local:build';
      if ($opts['no-cache']) {
        $build_command = $build_command . ' --no-cache';
      }
      $this->setRunOtherCommand(
        $build_command,
        self::ERROR_BUILDING_IMAGE
      );
    }

    $up_command = 'local:up';
    if ($opts['force-recreate']) {
      $up_command = $up_command . ' --force-recreate';
    }

    $this->say("Starting application...");
    $this->setRunOtherCommand($up_command);
    $this->waitForDeployment();
    $this->io()->newLine();
    $this->setRunOtherCommand('local:logs:check');

    if (!$opts['no-tail-logs']) {
      $this->setRunOtherCommand('local:logs:tail');
    }
  }

  /**
   * Waits for local application deployment to finish and reports its status.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function waitForDeployment() {
    $counter = 0;
    $status = 0;

    $progressBar = new ProgressBar($this->output(), 100);
    ProgressBar::setFormatDefinition('minimal', 'Progress: %percent%% [%message%]');
    $progressBar->setFormat('minimal');
    $progressBar->setMessage('Starting...');
    $progressBar->setProgress(0);
    $progressBar->start();

    $this->setLocalFinishMarker();
    while ($status < 100 and ($counter < self::WAIT_DEPLOYMENT_MAX_REPEATS)) {
      $counter++;
      sleep(self::WAIT_DEPLOYMENT_CYCLE_LENGTH);
      list($status, $description) = $this->getLocalDeploymentStatus($this->localFinishMarker);
      if ($status < 100) {
        $progressBar->setMessage($description);
        $progressBar->setProgress($status);
      }
    }

    if ($counter == self::WAIT_DEPLOYMENT_MAX_REPEATS) {
      throw new DockworkerException("Timeout waiting for local application deployment!");
    }

    $progressBar->setProgress(100);
    $progressBar->setMessage('Finished');
    $progressBar->finish();
    $this->io()->newLine(1);
  }

  /**
   * Get the local instance's finish marker from config.
   *
   * @throws \Dockworker\DockworkerException
   */
  private function setLocalFinishMarker() {
    $this->localFinishMarker = Robo::Config()->get('dockworker.application.finish_marker');

    if (empty($this->localFinishMarker)) {
      throw new DockworkerException(self::ERROR_FINISH_MARKER_UNSET);
    }
  }

  /**
   * Gets the local application deployment status.
   *
   * This approximates the local application deployment status by checking the
   * logs to determine the step in pre-init.d that the application is executing.
   *
   * @throws \Dockworker\DockworkerException
   *
   * @return array
   *   An array containing two items. The first item is a percentage deployment
   *   value, the second the current pre-init.d step that is running.
   */
  protected function getLocalDeploymentStatus($finish_marker) {
    $this->getlocalRunning();
    $result = $this->getLocalLogs([]);
    $logs = $result->getMessage();
    return $this->parseLocalLogForStatus($logs, $finish_marker);
  }

  /**
   * Parses a log to determine the deployment status of the local application.
   *
   * @param string $log
   *   The raw log from the local application.
   *
   * @return string[]
   *   An array containing two items. The first item is a percentage deployment
   *   value, the second the current pre-init.d step that is running.
   */
  protected function parseLocalLogForStatus($log, $finish_marker) {
    if (strpos($log, $finish_marker) !== FALSE) {
      return [
        '100',
        'Complete',
      ];
    }
    preg_match_all('/pre-init.d - ([0-9]{1,2})_(.*)/', $log, $matches);
    if (!empty($matches[1])) {
      return [
        end($matches[1]),
        end($matches[2]),
      ];
    }
    return [
      '0',
      'Starting',
    ];
  }

  /**
   * Checks the local application's container logs for errors.
   *
   * @param string[] $opts
   *   An array of options to pass to the builder.
   *
   * @option bool $all
   *   Check logs from all local services, not only the web endpoint.
   *
   * @command local:logs:check
   * @throws \Dockworker\DockworkerException
   *
   * @usage local:logs:check
   */
  public function localLogsCheck(array $opts = ['all' => FALSE]) {
    $this->getlocalRunning();

    // Allow modules to implement custom handlers to trigger errors.
    $handlers = $this->getCustomEventHandlers('dockworker-local-log-error-triggers');
    foreach ($handlers as $handler) {
      $this->addLogErrorTriggers($handler());
    }

    // Allow modules to implement custom handlers to add exceptions.
    $handlers = $this->getCustomEventHandlers('dockworker-local-log-error-exceptions');
    foreach ($handlers as $handler) {
      $this->addLogErrorExceptions($handler());
    }

    $result = $this->getLocalLogs($opts);
    $local_logs = $result->getMessage();
    if (!empty($local_logs)) {
      $this->checkLogForErrors('local', $local_logs);
    }
    else {
      $this->io()->title("No logs for local instance!");
    }
    try {
      $this->auditStartupLogs(FALSE);
      $this->say("No errors found in logs.");
    }
    catch (DockworkerException $e) {
      $this->printLocalLogs();
      $this->printStartupLogErrors();
      throw new DockworkerException("Error(s) found in local startup logs!");
    }
  }

  /**
   * Builds the application image, starts a local container, and runs all tests.
   *
   * @option bool $no-kill
   *   Do not use kill the container before starting over.
   * @option bool $no-rm
   *   Do not remove the existing assets before starting over.
   * @option bool $no-update-dockworker
   *   Do not update dockworker as part of the startup process.
   *
   * @command local:build-test
   * @throws \Exception
   *
   * @usage local:build-test
   */
  public function buildAndTest($opts = ['no-kill' => FALSE, 'no-rm' => FALSE, 'no-update-dockworker' => FALSE]) {
    if (!$opts['no-kill']) {
      $this->_exec('docker-compose kill');
    }
    if (!$opts['no-rm']) {
      $this->setRunOtherCommand('local:rm');
    }
    $start_command = 'local:start --no-cache --no-tail-logs';
    if ($opts['no-update-dockworker']) {
      $start_command = "$start_command --no-update-dockworker";
    }
    $this->setRunOtherCommand($start_command);
    $this->setRunOtherCommand('tests:all');
  }

  /**
   * Kills the local container, removes persistent data, and rebuilds/restarts.
   *
   * @param string[] $opts
   *   An array of options to pass to the builder.
   *
   * @option bool $no-cache
   *   Do not use any cached steps in the build.
   * @option bool $no-kill
   *   Do not use kill the container before starting over.
   * @option bool $no-rm
   *   Do not remove the existing assets before starting over.
   * @option bool $no-update-dockworker
   *   Do not update dockworker as part of the startup process.
   *
   * @command local:start-over
   * @aliases start-over, deploy
   * @throws \Exception
   *
   * @usage local:start-over
   */
  public function startOver($opts = ['no-cache' => FALSE, 'no-kill' => FALSE, 'no-rm' => FALSE, 'no-update-dockworker' => FALSE]) {
    if (!$opts['no-kill']) {
      $this->io()->title("Killing application");
      $this->_exec('docker-compose kill');
    }

    if (!$opts['no-rm']) {
      $this->setRunOtherCommand('local:rm');
    }

    $start_command = 'local:start';
    if ($opts['no-cache']) {
      $start_command = $start_command . ' --no-cache';
    }
    if ($opts['no-update-dockworker']) {
      $start_command = $start_command . ' --no-update-dockworker';
    }
    $this->setRunOtherCommand($start_command);
  }

    /**
     * Stops the local container and re-starts it, preserving persistent data.
     *
     * @param string[] $opts
     *   An array of options to pass to the builder.
     *
     * @option bool $no-cache
     *   Do not use any cached steps in the build.
     *
     * @command local:rebuild
     * @aliases rebuild
     * @throws \Exception
     *
     * @usage local:rebuild
     */
    public function rebuild($opts = ['no-cache' => FALSE]) {
        $this->setRunOtherCommand('local:halt');
        $start_command = 'local:start';
        if ($opts['no-cache']) {
            $start_command = $start_command . ' --no-cache';
        }
        $this->setRunOtherCommand($start_command);
    }

  /**
   * Brings up the local application container.
   *
   * @option bool $force-recreate
   *   Pass a --force-recreate option to docker-compose up.
   *
   * @command local:up
   * @aliases up
   *
   * @usage local:up
   */
  public function up($opts = ['force-recreate' => FALSE]) {
    $this->io()->title("Starting local containers");
    $cmd = $this->taskDockerComposeUp()
      ->detachedMode()
      ->printOutput(FALSE)
      ->silent(TRUE);

    if ($opts['force-recreate']) {
      $cmd->forceRecreate();
    }

    return $cmd->run();
  }

  /**
   * Updates the local system hostfile for the local application. Requires sudo.
   *
   * @command local:update-hostfile
   * @throws \Dockworker\DockworkerException
   *
   * @usage local:update-hostfile
   */
  public function setHostFileEntries() {
    $hostnames = $this->getHostFileHostnames();
    $this->say("Updating hostfile with entries. If you are asked for a password, you should enable passwordless sudo for your user.");
    foreach ($hostnames as $hostname) {
      $delete_command = "sudo sh -c 'sed -i '' -e \"/$hostname/d\" /etc/hosts'";
      $add_command = "sudo sh -c 'echo \"127.0.0.1       $hostname\" >> /etc/hosts'";

      $this->say("Updating hostfile with entry for $hostname...");
      exec($delete_command, $delete_output, $delete_return);
      exec($add_command, $add_output, $add_return);

      if ($delete_return > 0 || $add_return > 0) {
        throw new DockworkerException(sprintf(self::ERROR_UPDATING_HOSTFILE));
      }
    }
  }

  /**
   * Reverts the local system hostfile for the local application. Requires sudo.
   *
   * @command local:revert-hostfile
   * @throws \Dockworker\DockworkerException
   *
   * @usage local:revert-hostfile
   */
  public function unSetHostFileEntries() {
    $hostnames = $this->getHostFileHostnames();
    $this->say("Removing hostfile entries. If you are asked for a password, you should enable passwordless sudo for your user.");
    foreach ($hostnames as $hostname) {
      $this->say("Removing hostfile entry for $hostname...");
      $delete_command = "sudo sh -c 'sed -i '' -e \"/$hostname/d\" /etc/hosts'";
      exec($delete_command, $delete_output, $delete_return);
      if ($delete_return > 0) {
        throw new DockworkerException(sprintf(self::ERROR_UPDATING_HOSTFILE));
      }
    }
  }

  /**
   * Get the list of hostnames that should be localhost for dev purposes.
   *
   * @return array
   */
  private function getHostFileHostnames() {
    $hostnames = [escapeshellarg('local-' . $this->instanceName)];

    $additional_hostnames = Robo::Config()->get('dockworker.deployment.local.localhost_hostnames');
    if (!empty($additional_hostnames)) {
      $hostnames = array_merge($hostnames, $additional_hostnames);
    }
    return $hostnames;
  }

}
