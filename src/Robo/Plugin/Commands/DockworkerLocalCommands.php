<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerException;
use Dockworker\DockworkerLogCheckerTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Defines commands for a Dockworker local application.
 */
class DockworkerLocalCommands extends DockworkerCommands {

  const ERROR_BUILDING_IMAGE = 'Error reported building image!';
  const ERROR_PULLING_UPSTREAM_IMAGE = 'Error pulling upstream image %s';
  const ERROR_UPDATING_HOSTFILE = 'Error updating hostfile!';
  const ERROR_CONTAINER_MISSING = 'The %s local deployment does not appear to exist.';
  const ERROR_CONTAINER_STOPPED = 'The %s local deployment appears to be stopped.';

  use \Droath\RoboDockerCompose\Task\loadTasks;
  use DockworkerLogCheckerTrait;

  /**
   * Clean up any leftover docker assets not being used.
   *
   * @command docker:cleanup
   */
  public function localCleanup() {
    $this->say("Cleaning up dangling images and volumes:");
    $this->_exec('docker system prune -af');
  }

  /**
   * Halt the instance without removing any data.
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
   * Display the local instance logs.
   *
   * @param array $opts
   *   An array of options to pass to the builder.
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
   * Tail the local instance logs.
   *
   * @param array $opts
   *   An array of options to pass to the builder.
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
   * Get logs from the local container.
   *
   * @param array $opts
   *   An array of options to pass to the builder.
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  private function getLocalLogs(array $opts = ['all' => FALSE]) {
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
   * Check the local logs for errors.
   *
   * @param array $opts
   *   An array of options to pass to the builder.
   *
   * @command local:logs:check
   * @throws \Dockworker\DockworkerException
   * @return \Robo\Result
   *   The result of the command.
   */
  public function localLogsCheck(array $opts = ['all' => FALSE]) {
    $this->getlocalRunning();
    $result = $this->getLocalLogs($opts);
    $local_logs = $result->getMessage();
    if (!empty($local_logs)) {
      $this->checklogForErrors('local', $local_logs);
    }
    else {
      $this->io()->title("No logs for local instance!");
    }
    $this->auditProcessedLogs();
    $this->say("No errors found in logs.");
  }

  /**
   * Build the local application.
   *
   * @param array $opts
   *   An array of options to pass to the builder.
   *
   * @command local:build
   * @aliases build
   * @throws \Dockworker\DockworkerException
   */
  public function build(array $opts = ['no-cache' => FALSE]) {
    // Build the theme.
    $this->setRunOtherCommand('theme:build-all');

    if ($opts['no-cache']) {
      $this->setRunOtherCommand('docker:build --no-cache');
    }
    else {
      $this->setRunOtherCommand('docker:build');
    }
  }

  /**
   * Build the docker images.
   *
   * @param array $opts
   *   An array of options to pass to the builder.
   *
   * @command docker:build
   *
   * @throws \Dockworker\DockworkerException
   */
  public function buildDockerImages(array $opts = ['no-cache' => FALSE]) {
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
   * Build all themes for the local.
   *
   * @command theme:build-all
   * @aliases build-themes
   */
  public function buildThemes() {
  }

  /**
   * Open the local's shell.
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
   * Pull upstream images for this instance.
   *
   * @command docker:pull-upstream
   * @throws \Dockworker\DockworkerException
   */
  public function pullUpstreamImages() {
    $upstream_images = $this->getUpstreamImages();
    foreach ($upstream_images as $upstream_image) {
      $result= $this->taskDockerPull($upstream_image)->run();
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
   * Bring down the instance and remove all persistent data.
   *
   * @command local:rm
   * @aliases rm
   *
   * @return \Robo\Result
   *   The result of the removal command.
   */
  public function removeData() {
    return $this->_exec('docker-compose rm -f -v');
  }

  /**
   * Bring up the instance and display the logs.
   *
   * @param array $opts
   *   An array of options to pass to the builder.
   *
   * @command local:start
   * @aliases start
   * @throws \Exception
   */
  public function start(array $opts = ['no-cache' => FALSE, 'no-tail-logs' => FALSE]) {
    // $this->setRunOtherCommand('dockworker:update');
    $this->setRunOtherCommand('local:update-hostfile');
    $this->setRunOtherCommand('docker:pull-upstream');

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
    $this->say('Checking startup logs for errors...');
    $this->setRunOtherCommand('local:logs:check');

    if (!$opts['no-tail-logs']) {
      $this->setRunOtherCommand('local:logs:tail');
    }
  }

  /**
   * Build the instance from scratch and run tests.
   *
   * @command local:build-test
   * @throws \Exception
   */
  public function buildAndTest() {
    $this->_exec('docker-compose kill');
    $this->setRunOtherCommand('local:rm');
    $this->setRunOtherCommand('local:start --no-cache --no-tail-logs');
    $this->setRunOtherCommand('test:all');
  }

  /**
   * Bring down the instance, remove all persistent data and start it again.
   *
   * @param array $opts
   *   An array of options to pass to the builder.
   *
   * @command local:start-over
   * @aliases start-over, deploy
   * @throws \Exception
   */
  public function startOver($opts = ['no-cache' => FALSE]) {
      $this->_exec('docker-compose kill');
      $this->setRunOtherCommand('local:rm');
      $start_command = 'local:start';
      if ($opts['no-cache']) {
          $start_command = $start_command . ' --no-cache';
      }
      $this->setRunOtherCommand($start_command);
  }

    /**
     * Bring down the instance and start it again, preserving persistent data.
     *
     * @param array $opts
     *   An array of options to pass to the builder.
     *
     * @command local:rebuild
     * @aliases rebuild
     *
     * @throws \Exception
     */
    public function rebuild($opts = ['no-cache' => FALSE]) {
        $this->_exec('docker-compose kill');
        $start_command = 'local:start';
        if ($opts['no-cache']) {
            $start_command = $start_command . ' --no-cache';
        }
        $this->setRunOtherCommand($start_command);
    }

  /**
   * Bring up the instance.
   *
   * @command local:up
   * @aliases up
   */
  public function up() {
    return $this->taskDockerComposeUp()
      ->detachedMode()
      ->removeOrphans()
      ->run();
  }

  /**
   * Update the system hostfile with a local URL for the local application. Requires sudo.
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

  /**
   * Check if the container is running.
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
   * Get the deployment status.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function getLocalDeploymentStatus() {
    $this->getlocalRunning();
    $result = $this->getLocalLogs([]);
    $logs = $result->getMessage();
    return $this->parseLocalLogForStatus($logs);
  }

  /**
   * Parse a log to determine the current status of the local deployment.
   *
   * @param $log
   *
   * @return array
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
   * Wait for the local deployment to finish and report status to the user.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function waitForDeployment() {
    $counter = 0;
    $delay = 5;
    $max = 80;
    $status = 0;

    $this->say('Deploying local application. This can take several minutes...');
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

}
