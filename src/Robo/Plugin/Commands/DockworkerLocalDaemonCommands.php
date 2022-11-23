<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\ApplicationShellTrait;
use Dockworker\DockworkerException;
use Dockworker\LocalDockerContainerTrait;
use Dockworker\WorkstationShellTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerLocalCommands;
use Robo\Robo;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Yaml\Yaml;

/**
 * Defines the commands used to interact with a Dockworker local application.
 */
class DockworkerLocalDaemonCommands extends DockworkerLocalCommands {

  use LocalDockerContainerTrait;
  use ApplicationShellTrait;
  use WorkstationShellTrait;

  const ERROR_CONTAINER_MISSING = 'The %s local deployment is not running. You can start it with \'dockworker deploy\'.';
  const ERROR_CONTAINER_STOPPED = 'The %s local deployment appears to be stopped.';
  const ERROR_UPDATING_HOSTFILE = 'Error updating hostfile!';
  const WAIT_DEPLOYMENT_CYCLE_LENGTH = 1;
  const WAIT_DEPLOYMENT_MAX_REPEATS = 300;


  /**
   * Halts this local deamon application without removing its persistent data.
   *
   * Following a halt, the application can be restarted with the 'start'
   * command, and all data will be preserved.
   *
   * @command local:halt
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function localHalt() {
    $this->_exec('docker-compose stop --timeout 10');
  }

  /**
   * Displays all of this local application's previous logs and outputs any new ones that occur.
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
   * Opens a shell within this application's local deployment.
   *
   * @command shell:local
   * @aliases shell
   * @throws \Exception
   *
   * @return \Robo\Result
   *   The result of the command.
   *
   * @shell
   */
  public function openLocalShell() {
    return $this->taskDockerExec($this->instanceName)
      ->interactive()
      ->option('-t')
      ->exec($this->applicationShell)
      ->run();
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
   */
  public function startOver(array $options = ['no-cache' => FALSE, 'no-kill' => FALSE, 'no-rm' => FALSE]) {
    if (!$options['no-kill']) {
      $this->io()->title("Killing application");
      $this->_exec('docker-compose kill');
    }

    if (!$options['no-rm']) {
      $this->setRunOtherCommand('local:rm');
    }

    $this->io()->title("Building application");
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
   * @workstationshell
   */
  public function setHostFileEntries() {
    $hostnames = $this->getHostFileHostnames();
    $this->io()->title("Configuring Local Hostfile");
    $this->say("If you are asked for a password, you should enable passwordless sudo for your user.");
    foreach ($hostnames as $hostname) {
      $delete_command = "sudo $this->workstationShell -c 'sed -i '' -e \"/$hostname/d\" /etc/hosts'";
      $add_command = "sudo $this->workstationShell -c 'echo \"127.0.0.1       $hostname\" >> /etc/hosts'";

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
   * @shell
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
    $this->setInstanceName();
    $hostnames = [escapeshellarg('local-' . $this->instanceName)];

    $additional_hostnames = Robo::Config()->get('dockworker.deployment.local.localhost_hostnames');
    if (!empty($additional_hostnames)) {
      $hostnames = array_merge($hostnames, $additional_hostnames);
    }
    return $hostnames;
  }

  /**
   * Determines the local primary service deployment port.
   *
   * @return string
   *   The local primary service deployment port.
   */
  protected function getLocalDeploymentPort() {
    $docker_compose = Yaml::parse(
      file_get_contents(
        $this->repoRoot . '/docker-compose.yml'
      )
    );
    foreach($docker_compose['services'][$this->instanceName]['ports'] as $portmap) {
      $values = explode(':', $portmap);
      return $values[0];
    }
  }

}
