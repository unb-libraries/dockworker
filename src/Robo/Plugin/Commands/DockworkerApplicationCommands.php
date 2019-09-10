<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Robo\Plugin\Commands\DockworkerCommands;

/**
 * Defines commands for a Dockworker application.
 */
class DockworkerApplicationCommands extends DockworkerCommands {

  const ERROR_BUILDING_IMAGE = 'Error reported building image!';
  const ERROR_PULLING_UPSTREAM_IMAGE = 'Error pulling upstream image %s';
  const ERROR_UPDATING_HOSTFILE = 'Error updating hostfile!';

  use \Droath\RoboDockerCompose\Task\loadTasks;

  /**
   * Clean up any leftover docker assets not being used.
   *
   * @command docker:cleanup
   */
  public function applicationCleanup() {
    $this->say("Cleaning up dangling images and volumes:");
    $this->_exec('docker system prune -af');
  }

  /**
   * Halt the instance without removing any data.
   *
   * @command application:halt
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function applicationHalt() {
    return $this->taskDockerComposeDown()->run();
  }

  /**
   * Display the instance logs.
   *
   * @param array $opts
   *   An array of options to pass to the builder.
   *
   * @command application:logs
   * @aliases logs
   * @throws \Exception
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function applicationLogs(array $opts = ['all' => FALSE]) {
    $this->getapplicationRunning();
    if ($opts['all']) {
      return $this->_exec('docker-compose logs -f');
    }
    else {
      return $this->_exec("docker-compose logs -f {$this->instanceName}");
    }
  }

  /**
   * Build the application.
   *
   * @param array $opts
   *   An array of options to pass to the builder.
   *
   * @command application:build
   * @aliases build
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
   */
  public function buildDockerImages(array $opts = ['no-cache' => FALSE]) {
    if ($opts['no-cache']) {
      $command = 'docker-compose build --no-cache';
    }
    else {
      $command = 'docker-compose build';
    }
    if (!$this->_exec($command)->wasSuccessful()) {
      throw new \Exception(
        self::ERROR_BUILDING_IMAGE
      );
    }
  }

  /**
   * Build all themes for the application.
   *
   * @command theme:build-all
   * @aliases build-themes
   */
  public function buildThemes() {
  }

  /**
   * Open the application's shell.
   *
   * @command application:shell
   * @aliases shell
   * @throws \Exception
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function openApplicationShell() {
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
   * @throws \Exception
   */
  public function pullUpstreamImages() {
    $upstream_images = $this->getUpstreamImages();
    foreach ($upstream_images as $upstream_image) {
      $result= $this->taskDockerPull($upstream_image)->run();
      if ($result->getExitCode() > 0) {
        throw new \Exception(
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
   * @command application:rm
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
   * @command application:start
   * @aliases start
   * @throws \Exception
   */
  public function start(array $opts = ['no-cache' => FALSE]) {
    // $this->setRunOtherCommand('dockworker:update');
    $this->setRunOtherCommand('application:update-hostfile');
    $this->setRunOtherCommand('docker:pull-upstream');

    $build_command = 'application:build';
    if ($opts['no-cache']) {
      $build_command = $build_command . ' --no-cache';
    }

    $this->setRunOtherCommand(
        $build_command,
      self::ERROR_BUILDING_IMAGE
    );

    $this->setRunOtherCommand('application:up');
    $this->setRunOtherCommand('application:logs');
  }

  /**
   * Bring down the instance, remove all persistent data and start it again.
   *
   * @param array $opts
   *   An array of options to pass to the builder.
   *
   * @command application:start-over
   * @aliases start-over, deploy
   * @throws \Exception
   */
  public function startOver($opts = ['no-cache' => FALSE]) {
      $this->_exec('docker-compose kill');
      $this->setRunOtherCommand('application:rm');
      $start_command = 'application:start';
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
     * @command application:rebuild
     * @aliases rebuild
     *
     * @throws \Exception
     */
    public function rebuild($opts = ['no-cache' => FALSE]) {
        $this->_exec('docker-compose kill');
        $start_command = 'application:start';
        if ($opts['no-cache']) {
            $start_command = $start_command . ' --no-cache';
        }
        $this->setRunOtherCommand($start_command);
    }

  /**
   * Bring up the instance.
   *
   * @command application:up
   * @aliases up
   */
  public function up() {
    return $this->taskDockerComposeUp()
      ->detachedMode()
      ->removeOrphans()
      ->run();
  }

  /**
   * Update the system hostfile with a local URL for the application.
   *
   * @command application:update-hostfile
   */
  public function setHostFileEntry() {
    $hostname = escapeshellarg('local-' . $this->instanceName);

    $delete_command = "sudo sh -c 'sed -i '' -e \"/$hostname/d\" /etc/hosts'";
    $add_command = "sudo sh -c 'echo \"127.0.0.1       $hostname\" >> /etc/hosts'";

    $this->say("Updating hostfile with entry for $hostname. If you are asked for a password, you should enable passwordless sudo for your user.");
    exec($delete_command, $delete_output, $delete_return);
    exec($add_command, $add_output, $add_return);

    if ($delete_return > 0 || $add_command > 0) {
      throw new \Exception(sprintf(self::ERROR_UPDATING_HOSTFILE));
    }
  }

}
