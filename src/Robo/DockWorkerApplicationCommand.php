<?php

namespace UnbLibraries\DockWorker\Robo;

use UnbLibraries\DockWorker\Robo\DockWorkerCommand;

/**
 * Defines commands for a DockWorker application.
 */
class DockWorkerApplicationCommand extends DockWorkerCommand {

  const ERROR_BUILDING_IMAGE = 'Error reported building image!';
  const ERROR_PULLING_UPSTREAM_IMAGE = 'Error pulling upstream image %s';

  use \Droath\RoboDockerCompose\Task\loadTasks;

  /**
   * This hook will fire for all commands in this command file.
   *
   * @hook init
   */
  public function initialize() {
    $this->getInstanceName();
  }

  /**
   * Clean up any leftover docker assets not being used.
   *
   * @command application:cleanup
   */
  public function applicationCleanup() {
    $this->say("Cleaning up dangling images and volumes:");
    $this->_exec('docker images -qf dangling=true | xargs docker rmi -f');
    $this->_exec('docker volume ls -qf dangling=true | xargs docker volume rm');
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
   * @command application:logs
   * @aliases logs
   * @throws \Exception
   *
   * @return \Robo\Result
   *   The result of the command.
   */
  public function applicationLogs() {
    $this->getapplicationRunning();
    return $this->_exec('docker-compose logs -f');
  }

  /**
   * Build the application docker images.
   *
   * @param array $opts
   *   An array of options to pass to the builder.
   *
   * @command application:build
   * @aliases build
   */
  public function build(array $opts = ['no-cache' => FALSE]) {
    if ($opts['no-cache']) {
      $this->_exec('docker-compose build --no-cache');
    }
    else {
      $this->_exec('docker-compose build');
    }
  }

  /**
   * Compile a theme's assets.
   *
   * @param string $path
   *   The relative path of the theme to build.
   *
   * @command application:theme:build
   */
  public function buildTheme($path) {
  }

  /**
   * Compile all themes in the application.
   *
   * @command application:theme:build-all
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
    return $this->taskDockerExec($this->getInstanceName())
      ->interactive()
      ->option('-t')
      ->exec('sh')
      ->run();
  }

  /**
   * Git-pull the upstream image(s) for this instance.
   *
   * @command application:pull-upstream-images
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
    // Make sure the instance is down first.
    $this->taskDockerComposeDown()
      ->volumes()
      ->removeOrphans()
      ->run();

    // Remove the docker-compose stored data.
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
    $this->setRunOtherCommand('application:pull-upstream-images');

    $this->setRunOtherCommand(
      'application:build',
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
   * @aliases start-over
   * @throws \Exception
   */
  public function startOver($opts = ['no-cache' => FALSE]) {
    $this->setRunOtherCommand('application:rm');
    $this->setRunOtherCommand('application:cleanup');
    $this->setRunOtherCommand('application:theme:build-all');
    $this->setRunOtherCommand('application:start');
  }

  /**
   * Bring up the instance.
   *
   * @command application:up
   */
  public function up() {
    return $this->taskDockerComposeUp()
      ->detachedMode()
      ->removeOrphans()
      ->run();
  }

}
