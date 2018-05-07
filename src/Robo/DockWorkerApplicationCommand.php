<?php

namespace UnbLibraries\DockWorker\Robo;

use Robo\Result;
use Robo\Robo;
use Symfony\Component\Finder\Finder;
use UnbLibraries\DockWorker\Robo\DockWorkerCommand;

/**
 * Defines commands for a DockWorker application.
 */
class DockWorkerApplicationCommand extends DockWorkerCommand {

  const ERROR_BUILDING_IMAGE = 'Error reported building image!';
  const ERROR_FAILED_THEME_BUILD = '%s failed theme building';
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
   * Build the instance images from the Dockerfiles.
   *
   * @command application:build
   * @aliases build
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
   * @command application:theme:build
   */
  public function buildTheme($path) {
    // CSS.
    $this->say("Compiling SCSS in $path");
    $compiler = $this->repoRoot . '/vendor/bin/pscss';
    $input = "src/scss/style.scss";
    $output = "$path/dist/css/style.css";
    $return_code = 0;

    // Ensure dist dir exists.
    $return = $this->taskExecStack()
      ->stopOnFail()
      ->dir($path)
      ->exec("mkdir -p dist/css")
      ->run();
    if ($return->getExitCode() != "0") {
      $return_code = 1;
    }

    // Compile sass.
    $return = $this->taskExecStack()
      ->stopOnFail()
      ->dir($path)
      ->exec("$compiler lint -f crunched $input > $output")
      ->run();
    if ($return->getExitCode() != "0") {
      $return_code = 1;
    }

    // Images.
    $this->say("Deploying Image Assets in $path");
    $return = $this->taskExecStack()
      ->stopOnFail()
      ->dir($path)
      ->exec("cp -r src/img dist/ || true")
      ->run();
    if ($return->getExitCode() != "0") {
      $return_code = 1;
    }

    // Javascript.
    $this->say("Deploying Javascript Assets in $path");
    $return = $this->taskExecStack()
      ->stopOnFail()
      ->dir($path)
      ->exec("cp -r src/js dist/ || true")
      ->run();
    if ($return->getExitCode() != "0") {
      $return_code = 1;
    }

    // Permissions.
    $this->say("Setting Permissions of dist in $path");
    $return = $this->taskExecStack()
      ->stopOnFail()
      ->dir($path)
      ->exec("chmod -R g+w dist")
      ->run();
    if ($return->getExitCode() != "0") {
      $return_code = 1;
    }

    $this->say("Done!");
    return $return_code;
  }

  /**
   * SCSS compile all themes in the repository.
   *
   * @command application:theme:build-all
   */
  public function buildThemes() {
    $custom_theme_dir = $this->repoRoot . '/custom/themes';

    if (file_exists($custom_theme_dir)) {
      $finder = new Finder();
      $finder->in($custom_theme_dir)
        ->files()
        ->name('/^style\.scss$/');
      foreach ($finder as $file) {
        $theme_path = realpath(
          $file->getPath() . '/../../'
        );
        $return_code = $this->buildTheme($theme_path);
        if ($return_code != "0") {
          throw new \Exception(
            sprintf(self::ERROR_FAILED_THEME_BUILD, $theme_path)
          );
        }
      }
    }
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
    return TRUE;
  }

  /**
   * Halt the instance without removing any data.
   *
   * @command application:halt
   */
  public function applicationHalt() {
    return $this->taskDockerComposeDown()
      ->run();
  }

  /**
   * Display the instance logs.
   *
   * @command application:logs
   * @aliases logs
   */
  public function applicationLogs() {
    $this->getapplicationRunning();
    return $this->_exec('docker-compose logs -f');
  }

  /**
   * Open the application's shell.
   *
   * @command application:shell
   * @aliases shell
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
   * @command application:start
   * @aliases start
   */
  public function start($opts = ['no-cache' => FALSE]) {
    $this->pullUpstreamImages();

    if ($this->build($opts)->getExitCode() > 0) {
      throw new \Exception(
        sprintf(self::ERROR_BUILDING_IMAGE)
      );
    }

    $collection = $this->collectionBuilder();
    $collection->addCode(
      [$this, 'up']
    );
    $collection->addCode(
      [$this, 'applicationLogs']
    );
    return $collection->run();
  }

  /**
   * Bring down the instance, remove all persistent data and start it again.
   *
   * @command application:start-over
   * @aliases start-over
   */
  public function startOver($opts = ['no-cache' => FALSE]) {
    $this->removeData();
    $this->applicationCleanup();
    return $this->start($opts);
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
