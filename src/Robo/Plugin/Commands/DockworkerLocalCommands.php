<?php

namespace Dockworker\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Dockworker\DockworkerException;
use Dockworker\DockworkerLogCheckerTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;
use Droath\RoboDockerCompose\Task\loadTasks;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Defines the commands used to interact with a Dockworker local application.
 */
class DockworkerLocalCommands extends DockworkerCommands implements CustomEventAwareInterface {

  const ERROR_BUILDING_IMAGE = 'Error reported building image!';
  const ERROR_PULLING_UPSTREAM_IMAGE = 'Error pulling upstream image %s';
  const ERROR_UPDATING_HOSTFILE = 'Error updating hostfile!';
  const ERROR_CONTAINER_MISSING = 'The %s local deployment does not appear to exist.';
  const ERROR_CONTAINER_STOPPED = 'The %s local deployment appears to be stopped.';

  use CustomEventAwareTrait;
  use DockworkerLogCheckerTrait;
  use loadTasks;

  /**
   * Removes unused (orphaned) docker images, volumes and networks.
   *
   * @command dockworker:docker:cleanup
   */
  public function localCleanup() {
    $this->say("Cleaning up dangling images and volumes:");
    $this->_exec('docker system prune -af');
  }

  /**
   * Halts the local application without removing any data.
   *
   * @command local:halt
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function localHalt() {
    return $this->taskDockerComposeDown()->run();
  }

  /**
   * Displays the local application logs.
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
   * Gets logs from the local application.
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
   * Tails the local application's logs.
   *
   * @param string[] $opts
   *   An array of options to pass to the builder.
   *
   * @option bool $all
   *   Display logs from all local services, not only the web endpoint.
   *
   * @command local:logs:tail
   * @aliases logs
   * @throws \Exception
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function tailLocalLogs(array $opts = ['all' => FALSE]) {
    $this->getLocalRunning();
    if ($opts['all']) {
      return $this->_exec('docker-compose logs -f');
    }
    else {
      return $this->_exec("docker-compose logs -f {$this->instanceName}");
    }
  }

  /**
   * Builds the local application.
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
   */
  public function build(array $opts = ['no-cache' => FALSE]) {
    // Build the theme.
    $this->setRunOtherCommand('theme:build-all');

    if ($opts['no-cache']) {
      $command = 'docker-compose build --no-cache';
    }
    else {
      $command = 'docker-compose build';
    }
    if (!$this->_exec($command)->wasSuccessful()) {
      throw new DockworkerException(
        self::ERROR_BUILDING_IMAGE
      );
    }
  }

  /**
   * Builds the local application theme files.
   *
   * @command theme:build-all
   * @aliases build-themes
   */
  public function buildThemes() {
  }

  /**
   * Opens the local application shell.
   *
   * @command local:shell
   * @aliases shell
   * @throws \Exception
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
   * Pulls upstream images for the local application.
   *
   * @command local:pull-upstream
   * @throws \Dockworker\DockworkerException
   */
  public function pullUpstreamImages() {
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
   * Brings down the local application and removes all persistent data.
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
      ->arg('rm')
      ->arg('-f')
      ->arg('-v');
  }

  /**
   * Brings up the local application and displays the logs.
   *
   * @param string[] $opts
   *   An array of options to pass to the builder.
   *
   * @option bool $no-cache
   *   Do not use any cached steps in the build.
   * @option bool $no-tail-logs
   *   Do not tail the application logs after starting.
   *
   * @command local:start
   * @aliases start
   * @throws \Exception
   */
  public function start(array $opts = ['no-cache' => FALSE, 'no-tail-logs' => FALSE]) {
    // $this->setRunOtherCommand('dockworker:update');
    $this->io()->title("Initializing application");
    $this->setRunOtherCommand('local:update-hostfile');
    $this->setRunOtherCommand('local:pull-upstream');

    $this->io()->newLine();
    $this->io()->title("Building application");
    $build_command = 'local:build';
    if ($opts['no-cache']) {
      $build_command = $build_command . ' --no-cache';
    }

    $this->setRunOtherCommand(
        $build_command,
      self::ERROR_BUILDING_IMAGE
    );

    $this->setRunOtherCommand('local:up');
    $this->waitForDeployment();
    sleep(3);

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
    $delay = 5;
    $max = 80;
    $status = 0;

    $progressBar = new ProgressBar($this->output(), 100);
    ProgressBar::setFormatDefinition('minimal', 'Progress: %percent%% [%message%]');
    $progressBar->setFormat('minimal');
    $progressBar->setMessage('Starting...');
    $progressBar->setProgress(0);
    $progressBar->start();

    while ($status < 100 and ($counter < $max)) {
      $counter++;
      sleep($delay);
      list($status, $description) = $this->getLocalDeploymentStatus();
      if ($status < 100) {
        $progressBar->setMessage($description);
        $progressBar->setProgress($status);
      }
    }

    if ($counter == $max) {
      throw new DockworkerException("Timeout waiting for local application deployment!");
    }

    $progressBar->setProgress(100);
    $progressBar->setMessage('Finished');
    $progressBar->finish();
    $this->io()->newLine(1);
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
  protected function getLocalDeploymentStatus() {
    $this->getlocalRunning();
    $result = $this->getLocalLogs([]);
    $logs = $result->getMessage();
    return $this->parseLocalLogForStatus($logs);
  }

  /**
   * Checks the local application logs for errors.
   *
   * @param string[] $opts
   *   An array of options to pass to the builder.
   *
   * @option bool $all
   *   Check logs from all local services, not only the web endpoint.
   *
   * @command local:logs:check
   * @throws \Dockworker\DockworkerException
   */
  public function localLogsCheck(array $opts = ['all' => FALSE]) {
    $this->getlocalRunning();

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
    $this->auditStartupLogs();
    $this->say("No errors found in logs.");
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
  protected function parseLocalLogForStatus($log) {
    if (strpos($log, '99_z_notify_user_URI') !== FALSE) {
      return [
        '100',
        'Complete',
      ];
    }
    preg_match_all('/pre-init\.d - processing \/scripts\/pre-init.d\/([0-9]{1,2})_(.*)/', $log, $matches);
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
   * Builds the local application from scratch and runs its tests.
   *
   * @command local:build-test
   * @throws \Exception
   */
  public function buildAndTest() {
    $this->_exec('docker-compose kill');
    $this->setRunOtherCommand('local:rm');
    $this->setRunOtherCommand('local:start --no-cache --no-tail-logs');
    $this->setRunOtherCommand('tests:all');
  }

  /**
   * Kills the local application, removes all persistent data, and restarts it.
   *
   * @param string[] $opts
   *   An array of options to pass to the builder.
   *
   * @option bool $no-cache
   *   Do not use any cached steps in the build.
   *
   * @command local:start-over
   * @aliases start-over, deploy
   * @throws \Exception
   */
  public function startOver($opts = ['no-cache' => FALSE]) {
      $this->io()->title("Killing application");
      $this->_exec('docker-compose kill');

      $this->setRunOtherCommand('local:rm');
      $start_command = 'local:start';
      if ($opts['no-cache']) {
          $start_command = $start_command . ' --no-cache';
      }
      $this->setRunOtherCommand($start_command);
  }

    /**
     * Stops the local application, re-starts it, preserving persistent data.
     *
     * @param string[] $opts
     *   An array of options to pass to the builder.
     *
     * @option bool $no-cache
     *   Do not use any cached steps in the build.
     *
     * @command local:rebuild
     * @aliases rebuild
     *
     * @throws \Exception
     */
    public function rebuild($opts = ['no-cache' => FALSE]) {
        $this->io()->title("Stopping application");
        $this->_exec('docker-compose kill');
        $start_command = 'local:start';
        if ($opts['no-cache']) {
            $start_command = $start_command . ' --no-cache';
        }
        $this->setRunOtherCommand($start_command);
    }

  /**
   * Brings up the local application.
   *
   * @command local:up
   * @aliases up
   */
  public function up() {
    $this->io()->title("Starting local containers");
    return $this->taskDockerComposeUp()
      ->detachedMode()
      ->removeOrphans()
      ->printOutput(FALSE)
      ->silent(TRUE)
      ->run();
  }

  /**
   * Updates the local system hostfile for the local application. Requires sudo.
   *
   * @command local:update-hostfile
   * @throws \Dockworker\DockworkerException
   */
  public function setHostFileEntry() {
    $hostname = escapeshellarg('local-' . $this->instanceName);

    $delete_command = "sudo sh -c 'sed -i '' -e \"/$hostname/d\" /etc/hosts'";
    $add_command = "sudo sh -c 'echo \"127.0.0.1       $hostname\" >> /etc/hosts'";

    $this->say("Updating hostfile with entry for $hostname. If you are asked for a password, you should enable passwordless sudo for your user.");
    exec($delete_command, $delete_output, $delete_return);
    exec($add_command, $add_output, $add_return);

    if ($delete_return > 0 || $add_command > 0) {
      throw new DockworkerException(sprintf(self::ERROR_UPDATING_HOSTFILE));
    }
  }

}
