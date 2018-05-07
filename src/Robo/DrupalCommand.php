<?php

namespace UnbLibraries\DockWorker\Robo;

use Robo\Robo;
use Robo\Tasks;
use Symfony\Component\Finder\Finder;
use UnbLibraries\DockWorker\Robo\DockWorkerCommand;

/**
 * Defines commands in the GitCommand namespace.
 */
class DrupalCommand extends DockWorkerCommand {

  use \Boedah\Robo\Task\Drush\loadTasks;

  /**
   * SCSS compile a theme's assets.
   *
   * @hook post-command application:theme:build
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
   * @hook post-command application:theme:build-all
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
   * This hook will fire for all commands in this command file.
   *
   * @hook init
   */
  public function initialize() {
    $this->getInstanceName();
    $this->getContainerRunning();
  }

  /**
   * Rebuild the cache in the Drupal container.
   *
   * @command drupal:cr
   */
  public function resetCache() {
    return $this->taskDockerExec($this->getInstanceName())
      ->interactive()
      ->exec(
        $this->taskDrushStack()
          ->drupalRootDirectory('/app/html')
          ->uri('default')
          ->drush('cr')
      )
      ->run();
  }

  /**
   * Run the behat tests located in tests/.
   *
   * @command drupal:tests:behat
   */
  public function runBehatTests() {
    return $this->taskDockerExec($this->getInstanceName())
      ->interactive()
      ->exec('/scripts/runTests.sh')
      ->run();
  }

  /**
   * Get a ULI from the Drupal container.
   *
   * @command drupal:uli
   */
  public function uli() {
    return $this->taskDockerExec($this->getInstanceName())
      ->interactive()
      ->exec(
        '/scripts/pre-init.d/99_z_notify_user_URI.sh'
      )
      ->run();
  }

  /**
   * Write out the configuration from the instance.
   *
   * @command drupal:write-config
   */
  public function writeConfig() {
    return $this->taskDockerExec($this->getInstanceName())
      ->interactive()
      ->exec('/scripts/configExport.sh')
      ->run();
  }

}
