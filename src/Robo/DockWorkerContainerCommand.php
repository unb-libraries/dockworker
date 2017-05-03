<?php

namespace UnbLibraries\DockWorker\Robo;

use Robo\Result;
use Robo\Robo;
use Symfony\Component\Finder\Finder;
use UnbLibraries\DockWorker\Robo\DockWorkerCommand;

/**
 * Defines commands for a DockWorker container.
 */
class DockWorkerContainerCommand extends DockWorkerCommand {

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
   * Build the instance images from the Dockerfiles.
   *
   * @command container:build
   */
  public function build($opts = ['no-cache' => FALSE]) {
    $this->buildThemes();

    // Build docker image.
    if ($opts['no-cache']) {
      return $this->_exec('docker-compose build --no-cache');
    }
    else {
      return $this->_exec('docker-compose build');
    }
  }

  /**
   * SCSS compile a theme's assets.
   *
   * @command container:theme:build
   */
  public function buildTheme($path) {
    $this->say("Compiling SCSS in $path");

    $compiler = $this->repoRoot . '/vendor/bin/pscss';
    $input = "src/scss/style.scss";
    $output = "$path/dist/css/style.css";

    $this->taskExecStack()
      ->stopOnFail()
      ->dir($path)
      ->exec("$compiler lint -f crunched $input > $output")
      ->run();

    $this->say("Done!");
  }

  /**
   * SCSS compile all themes in the repository.
   *
   * @command container:theme:build-all
   */
  public function buildThemes() {
    $custom_theme_dir = $this->repoRoot . '/custom/themes';

    if (file_exists($custom_theme_dir)) {
      $finder = new Finder();
      $finder->in($custom_theme_dir)
        ->files()
        ->name('/^style\.scss$/');
      foreach ($finder as $file) {
        $this->buildTheme(
          realpath(
            $file->getPath() . '/../../'
          )
        );
      }
    }
  }

  /**
   * Halt the instance without removing any data.
   *
   * @command container:halt
   */
  public function containerHalt() {
    return $this->taskDockerComposeDown()
      ->run();
  }

  /**
   * Display the instance logs.
   *
   * @command container:logs
   */
  public function containerLogs() {
    $this->getContainerRunning();
    return $this->_exec('docker-compose logs -f');
  }

  /**
   * Open the container's shell.
   *
   * @command container:shell
   */
  public function openContainerShell() {
    return $this->taskDockerExec($this->getInstanceName())
      ->interactive()
      ->option('-t')
      ->exec('sh')
      ->run();
  }

  /**
   * Git-pull the upstream image for this instance.
   *
   * @command container:pull-upstream
   */
  public function pullUpstreamImage() {
    $upstream_image = $this->getUpstreamImage();
    return $this->taskDockerPull($upstream_image)
      ->run();
  }

  /**
   * Bring down the instance and remove all persistent data.
   *
   * @command container:rm
   */
  public function removeData() {
    return $this->taskDockerComposeDown()
      ->volumes()
      ->removeOrphans()
      ->run();
  }

  /**
   * Bring up the instance and display the logs.
   *
   * @command container:start
   */
  public function start($opts = ['no-cache' => FALSE]) {
    $this->pullUpstreamImage();
    $this->build($opts);

    $collection = $this->collectionBuilder();
    $collection->addCode(
      [$this, 'up']
    );
    $collection->addCode(
      [$this, 'containerLogs']
    );
    return $collection->run();
  }

  /**
   * Bring down the instance, remove all persistent data and start it again.
   *
   * @command container:start-over
   */
  public function startOver($opts = ['no-cache' => FALSE]) {
    $this->removeData();
    return $this->start($opts);
  }

  /**
   * Bring up the instance.
   *
   * @command container:up
   */
  public function up() {
    return $this->taskDockerComposeUp()
      ->detachedMode()
      ->removeOrphans()
      ->run();
  }

}
