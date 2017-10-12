<?php

namespace UnbLibraries\DockWorker\Robo;

use Robo\Robo;
use Robo\Tasks;
use UnbLibraries\DockWorker\Robo\DockWorkerCommand;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;


/**
 * Defines commands in the GitCommand namespace.
 */
class DrupalRemoteSyncCommand extends DockWorkerCommand {

  use \Boedah\Robo\Task\Drush\loadTasks;

  const CONFIG_CONTAINER_APP_FILE_PATH = '/app/html/sites/default/files';
  const ERROR_UPSTREAM_CONFIG_UNSET = 'The %s key does not exist defined in %s.';

  /**
   * The path to the configuration file.
   *
   * @var string
   */
  protected $serverHostname;

  /**
   * The path to the configuration file.
   *
   * @var string
   */
  protected $remoteTmpDir;

  /**
   * The path to the configuration file.
   *
   * @var string
   */
  protected $localTmpDir;

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
   * Files.
   *
   * @command drupal:remote-sync:files
   */
  public function getRemoteServer() {
    $server = $this->askDefault("What server to sync from? (dev/live)", 'dev');

    $server_config_key = "dockworker.upstream.$server";
    $server_hostname = Robo::Config()->get($server_config_key);
    if (empty($server_hostname)) {
      throw new \Exception(
        sprintf(
          self::ERROR_UPSTREAM_CONFIG_UNSET,
            $server_config_key,
            $this->configFile
        )
      );
    }
    $this->serverHostname = $server_hostname;
    return TRUE;
  }

  /**
   * Files.
   */
  public function setCreateRemoteTmpDir() {
    $process = new Process("ssh {$this->serverHostname} mktemp -d");
    $process->run();

    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    $this->remoteTmpDir = trim($process->getOutput());
  }

  /**
   * Files.
   */
  public function setCreateLocalTmpDir() {
    $process = new Process("bash -c 'mktemp -d'");
    $process->run();

    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    $this->localTmpDir = trim($process->getOutput());
  }

  /**
   * Files.
   */
  public function setRemoveRemoteTmpDir() {
    if (trim($this->remoteTmpDir) != '') {
      $tmpdir = $this->remoteTmpDir;
      $this->taskSshExec($this->serverHostname)
        ->exec("rm -rf $tmpdir")
        ->run();
    }
  }

  /**
   * Files.
   */
  public function setRemoveLocalTmpDir() {
    if (trim($this->localTmpDir) != '') {
      $tmpdir = $this->localTmpDir;
      $this->taskExec('rm')
        ->arg('-rf')
        ->arg($tmpdir)
        ->run();
    }
  }

  /**
   * Files.
   */
  public function setCopyOutRemoteContainerFiles($hostname, $instance_name, $container_path, $remote_path) {
    $this->taskSshExec($hostname)
      ->exec("docker cp '$instance_name:$container_path'  '$remote_path'")
      ->run();
  }

  /**
   * Files.
   */
  public function setCopyInLocalContainerFiles($instance_name, $container_path, $remote_path) {
    // Local container.
    $this->taskExec('docker')
      ->arg('cp')
      ->arg('-i')
      ->arg($this->getInstanceName())
      ->arg('drush')
      ->arg('--root=/app/html')
      ->arg($command)
      ->run();
  }

  /**
   * Files.
   */
  public function setContainerDrushCommand($command, $remote = FALSE) {
    if ($remote) {
      // Remote container.
      $this->taskSshExec($this->serverHostname)
        ->exec("docker exec -i '{$this->getInstanceName()}' drush --root=/app/html $command")
        ->run();
    }
    else {
      // Local container.
      $this->taskExec('docker')
        ->arg('exec')
        ->arg('-i')
        ->arg($this->getInstanceName())
        ->arg('drush')
        ->arg('--root=/app/html')
        ->arg($command)
        ->run();
    }

  }

  /**
   * Files.
   */
  public function setCopyRemoteFiles($hostname, $remote_path, $local_path) {
    $this->_exec("scp -r '$hostname:$remote_path' '$local_path/'");
  }

  /**
   * Files.
   *
   * @command drupal:remote-sync:files
   */
  public function remoteFileSync() {
    $this->getRemoteServer();

    // Create tmp directories.
    $this->setCreateRemoteTmpDir();
    $this->setCreateLocalTmpDir();

    // Rebuild Drupal caches.
    $this->setContainerDrushCommand('cr', TRUE);
    $this->setContainerDrushCommand('cr', FALSE);

    $this->setCopyOutRemoteContainerFiles(
      $this->serverHostname,
        $this->getInstanceName(),
        self::CONFIG_CONTAINER_APP_FILE_PATH,
        $this->remoteTmpDir
    );

    $this->setCopyRemoteFiles(
      $this->serverHostname,
      $this->remoteTmpDir,
      $this->localTmpDir
    );

    $this->setCopyInLocalContainerFiles(
      $this->getInstanceName(),
      $this->localTmpDir,
      self::CONFIG_CONTAINER_APP_FILE_PATH
    );

    // Remove tmp directories.
    $this->setRemoveLocalTmpDir();
    $this->setRemoveRemoteTmpDir();
  }

}
