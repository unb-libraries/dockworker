<?php

namespace Dockworker\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Dockworker\DockworkerException;
use Dockworker\DockworkerLogCheckerTrait;
use Dockworker\GitRepoTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;
use Robo\Robo;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Defines the commands used to interact with a Dockworker local application.
 */
class DockworkerLocalCommands extends DockworkerCommands implements CustomEventAwareInterface {

  const ERROR_BUILDING_IMAGE = 'Error reported building image!';
  const ERROR_CONTAINER_MISSING = 'The %s local deployment is not running. You can start it with \'dockworker deploy\'.';
  const ERROR_CONTAINER_STOPPED = 'The %s local deployment appears to be stopped.';
  const ERROR_PULLING_UPSTREAM_IMAGE = 'Error pulling upstream image %s';
  const ERROR_UPDATING_HOSTFILE = 'Error updating hostfile!';
  const WAIT_DEPLOYMENT_CYCLE_LENGTH = 1;
  const WAIT_DEPLOYMENT_MAX_REPEATS = 300;

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
   *
   * @usage docker:cleanup
   */
  public function localCleanup() {
    $this->say("Cleaning up dangling images and volumes:");
    $this->_exec('docker system prune -af');
  }

  /**
   * Halts this local application without removing its persistent data.
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
   * Halts this local application, removes its persistent data, and resets all repo files to the state at last commit.
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
   * @usage logs:local
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function printLocalLogs(array $options = ['all' => FALSE]) {
    $this->getlocalRunning();
    $this->io()->writeln(
      $this->getLocalLogs($options)
    );
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
   * Displays all of this local application's previous logs and outputs any new ones that accur.
   *
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $all
   *   Display logs from all local services, not only the web endpoint.
   * @option $timestamps
   *   Display a timestamp for each line of the logs.
   *
   * @command logs:tail:local
   * @aliases logs
   * @throws \Exception
   *
   * @usage logs:tail:local
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function tailLocalLogs(array $options = ['all' => FALSE, 'timestamps' => FALSE]) {
    $this->getLocalRunning();
    $log_dump_cmd = 'docker-compose logs';

    if ($options['timestamps']) {
      $log_dump_cmd .= ' --timestamps';
    }

    if ($options['all']) {
      $log_dump_cmd = "$log_dump_cmd --tail='all' --follow";
    }
    else {
      $log_dump_cmd = "$log_dump_cmd --tail='all' --follow {$this->instanceName}";
    }

    $handle = popen($log_dump_cmd, 'r');
    while(!feof($handle)) {
      $buffer = fgets($handle);
      echo "$buffer";
      flush();
    }
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
   *
   * @usage local:build
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
   *
   * @usage theme:build-all
   */
  public function buildThemes() {
  }

  /**
   * Opens a shell within this application's local deployment.
   *
   * @command shell:local
   * @aliases shell
   * @throws \Exception
   *
   * @usage shell:local
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function openLocalShell() {
    return $this->taskDockerExec($this->instanceName)
      ->interactive()
      ->option('-t')
      ->exec($this->applicationShell)
      ->run();
  }

  /**
   * Pulls the newest version of docker images required to deploy this application's local deployment.
   *
   * @command docker:image:pull-upstream
   * @throws \Dockworker\DockworkerException
   *
   * @usage docker:image:pull-upstream
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
      ->arg('down')
      ->arg('--rmi')
      ->arg('local')
      ->arg('-v');
  }

  /**
   * Builds and deploys this application's local deployment, and displays its logs.
   *
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $no-cache
   *   Do not use any cached steps in the build.
   * @option $no-tail-logs
   *   Do not tail the application logs after starting.
   * @option $no-update-hostfile
   *   Do not update the local hostfile with the application alias.
   * @option $no-upstream-pull
   *   Do not pull the upstream docker images before building.
   * @option $no-build
   *   Do not build any images before starting.
   * @option $only-start
   *   Alias for --no-update-hostfile --no-upstream-pull --no-build
   * @option $force-recreate
   *   Pass the --force-recreate option to docker-compose up.
   * @option $only-primary
   *   Only start the primary application container.
   *
   * @command local:start
   * @aliases start
   * @throws \Exception
   *
   * @usage local:start
   */
  public function start(array $options = ['no-cache' => FALSE, 'no-tail-logs' => FALSE, 'no-update-hostfile' => FALSE, 'no-upstream-pull' => FALSE, 'no-build' => FALSE, 'only-start' => FALSE, 'force-recreate' => FALSE, 'only-primary' => FALSE]) {
    if (!$options['no-update-hostfile'] && !$options['only-start']) {
      $this->setRunOtherCommand('hostfile:update');
    }

    if (!$options['no-cache'] && !$options['no-upstream-pull'] && !$options['only-start']) {
      $this->setRunOtherCommand('docker:image:pull-upstream');
    }

    if (!$options['no-build'] && !$options['only-start']) {
      $build_command = 'local:build';
      if ($options['no-cache']) {
        $build_command = $build_command . ' --no-cache';
      }
      $this->setRunOtherCommand(
        $build_command,
        self::ERROR_BUILDING_IMAGE
      );
    }

    $up_command = 'local:up';
    if ($options['force-recreate']) {
      $up_command = $up_command . ' --force-recreate';
    }

    if ($options['only-primary']) {
      $up_command = $up_command . ' --only-primary';
    }

    $this->say("Starting application...");
    $this->setRunOtherCommand($up_command);
    $this->waitForDeployment();
    $this->io()->newLine();
    $this->setRunOtherCommand('logs:check:local');

    if (!$options['no-tail-logs']) {
      $this->tailLocalLogs();
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
      [$status, $description] = $this->getLocalDeploymentStatus($this->localFinishMarker);
      if ($status < 100) {
        $progressBar->setMessage($description);
        $progressBar->setProgress($status);
      }
    }

    if ($counter == self::WAIT_DEPLOYMENT_MAX_REPEATS) {
      $this->_exec('docker-compose logs');
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
    $logs = $this->getLocalLogs([]);
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
    if (str_contains($log, $finish_marker)) {
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
   *
   * @usage logs:check:local
   */
  public function localLogsCheck(array $options = ['all' => FALSE]) {
    $this->getlocalRunning();
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

  /**
   * Builds and deploys this application's local deployment, and executes all tests within it.
   *
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $no-kill
   *   Do not use kill the container before starting over.
   * @option $no-rm
   *   Do not remove the existing assets before starting over.
   *
   * @command local:build-test
   * @throws \Exception
   *
   * @usage local:build-test
   */
  public function buildAndTest(array $options = ['no-kill' => FALSE, 'no-rm' => FALSE]) {
    if (!$options['no-kill']) {
      $this->_exec('docker-compose kill');
    }
    if (!$options['no-rm']) {
      $this->setRunOtherCommand('local:rm');
    }
    $start_command = 'local:start --no-cache --no-tail-logs';
    $this->setRunOtherCommand($start_command);
    $this->setRunOtherCommand('tests:all');
  }

  /**
   * Stops this application's local deployment, deletes any persistent data, rebuilds its image, and redeploys it.
   *
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $no-cache
   *   Do not use any cached steps in the build.
   * @option $no-kill
   *   Do not use kill the container before starting over.
   * @option $no-rm
   *   Do not remove the existing assets before starting over.
   *
   * @command local:start-over
   * @aliases start-over, deploy
   * @throws \Exception
   *
   * @usage local:start-over
   */
  public function startOver(array $options = ['no-cache' => FALSE, 'no-kill' => FALSE, 'no-rm' => FALSE]) {
    if (!$options['no-kill']) {
      $this->io()->title("Killing application");
      $this->_exec('docker-compose kill');
    }

    if (!$options['no-rm']) {
      $this->setRunOtherCommand('local:rm');
    }

    $start_command = 'local:start';
    if ($options['no-cache']) {
      $start_command = $start_command . ' --no-cache';
    }
    $this->setRunOtherCommand($start_command);
  }

    /**
     * Stops this application's local deployment, preserves persistent data, rebuilds its image, and redeploys it.
     *
     * @param string[] $options
     *   The array of available CLI options.
     *
     * @option $no-cache
     *   Do not use any cached steps in the build.
     *
     * @command local:rebuild
     * @aliases rebuild
     * @throws \Exception
     *
     * @usage local:rebuild
     */
    public function rebuild(array $options = ['no-cache' => FALSE]) {
      $this->io()->title("Rebuilding $this->instanceName");
      $this->say('Stopping application container...');
      $this->taskExec('docker-compose')
        ->dir($this->repoRoot)
        ->arg('stop')
        ->arg($this->instanceName)
        ->run();

      $this->say('Removing application container data...');
      $this->taskExec('docker-compose')
        ->dir($this->repoRoot)
        ->arg('rm')
        ->arg('-f')
        ->arg('-v')
        ->arg($this->instanceName)
        ->run();

        $start_command = 'local:start --only-primary --no-update-hostfile --no-upstream-pull';
        if ($options['no-cache']) {
            $start_command = $start_command . ' --no-cache ';
        }
        $this->setRunOtherCommand($start_command);
    }

  /**
   * Starts this application's already-build local deployment.
   *
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $force-recreate
   *   Pass the --force-recreate option to docker-compose up.
   * @option $only-primary
   *   Only start the primary application container.
   *
   * @command local:up
   * @aliases up
   *
   * @usage local:up
   */
  public function up(array $options = ['force-recreate' => FALSE, 'only-primary' => FALSE] ) {
    $this->io()->title("Starting local containers");
    $cmd_string = 'docker-compose up -d';

    if ($options['force-recreate']) {
      $cmd_string .= ' --force-recreate';
    }

    if ($options['only-primary']) {
      $cmd_string .= " $this->instanceName";
    }

    $this->_exec($cmd_string);
  }

  /**
   * Adds this application's information into the local development computer's hostfile. Requires sudo.
   *
   * @command hostfile:update
   * @throws \Dockworker\DockworkerException
   *
   * @usage hostfile:update
   */
  public function setHostFileEntries() {
    $hostnames = $this->getHostFileHostnames();
    $this->io()->title("Configuring Local Hostfile");
    $this->say("If you are asked for a password, you should enable passwordless sudo for your user.");
    foreach ($hostnames as $hostname) {
      $delete_command = "sudo $this->applicationShell -c 'sed -i '' -e \"/$hostname/d\" /etc/hosts'";
      $add_command = "sudo $this->applicationShell -c 'echo \"127.0.0.1       $hostname\" >> /etc/hosts'";

      $this->say("Updating hostfile with entry for $hostname...");
      exec($delete_command, $delete_output, $delete_return);
      exec($add_command, $add_output, $add_return);

      if ($delete_return > 0 || $add_return > 0) {
        throw new DockworkerException(sprintf(self::ERROR_UPDATING_HOSTFILE));
      }
    }
  }

  /**
   * Removes this application's information from the local development system's hostfile. Requires sudo.
   *
   * @command hostfile:revert
   * @throws \Dockworker\DockworkerException
   *
   * @usage hostfile:revert
   */
  public function unSetHostFileEntries() {
    $hostnames = $this->getHostFileHostnames();
    $this->io()->title("Reverting Local Hostfile");
    $this->say("If you are asked for a password, you should enable passwordless sudo for your user.");
    foreach ($hostnames as $hostname) {
      $this->say("Removing hostfile entry for $hostname...");
      $delete_command = "sudo $this->applicationShell -c 'sed -i '' -e \"/$hostname/d\" /etc/hosts'";
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

  /**
   * Retrieves and sets any log error triggers provided downstream.
   *
   * @param $type
   *   The type of k8s resource logs being checked.
   */
  protected function getCustomLogTriggersExceptions($type) : void {
    // Allow modules to implement custom handlers to trigger errors.
    $handlers = $this->getCustomEventHandlers("dockworker-$type-log-error-triggers");
    foreach ($handlers as $handler) {
      $this->addLogErrorTriggers($handler());
    }

    // Allow modules to implement custom handlers to add exceptions.
    $handlers = $this->getCustomEventHandlers("dockworker-$type-log-error-exceptions");
    foreach ($handlers as $handler) {
      $this->addLogErrorExceptions($handler());
    }
  }

}
