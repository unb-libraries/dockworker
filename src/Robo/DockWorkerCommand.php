<?php

namespace UnbLibraries\DockWorker\Robo;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\BuilderAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Config\Config;
use Robo\Contract\BuilderAwareInterface;
use Robo\Robo;
use Robo\Tasks;

/**
 * Base class for DockWorker Robo commands.
 */
class DockWorkerCommand extends Tasks implements ContainerAwareInterface, LoggerAwareInterface, BuilderAwareInterface {

  use BuilderAwareTrait;
  use ConfigAwareTrait;
  use ContainerAwareTrait;
  use LoggerAwareTrait;

  const ERROR_CONTAINER_MISSING = 'The %s container does not appear to exist.';
  const ERROR_CONTAINER_STOPPED = 'The %s container appears to be stopped.';
  const ERROR_INSTANCE_NAME_UNSET = 'The instance.name value has not been set in %s';
  const ERROR_PROJECT_PREFIX_UNSET = 'The project_prefix variable has not been set in %s';
  const ERROR_UPSTREAM_IMAGE_UNSET = 'The upstream_image variable has not been set in %s';

  /**
   * The path to the configuration file.
   *
   * @var string
   */
  protected $configFile;

  /**
   * The path to the DockWorker repo.
   *
   * @var string
   */
  protected $repoRoot;

  /**
   * Get the container name from config.
   */
  public function __construct() {
    $this->repoRoot = realpath(__DIR__ . "/../../../../../");
    $this->configFile = 'dockworker.yml';
    $this->config = Robo::loadConfiguration(
      [$this->repoRoot . '/' . $this->configFile]
    );
  }

  /**
   * Get the instance name from config.
   */
  public function getInstanceName() {
    $container_name = Robo::Config()->get('dockworker.instance.name');
    if (empty($container_name)) {
      throw new \Exception(sprintf(self::ERROR_INSTANCE_NAME_UNSET, $this->configFile));
    }
    return $container_name;
  }

  /**
   * Check if the container is running.
   */
  public function getContainerRunning() {
    $container_name = $this->getInstanceName();

    exec(
      "docker inspect -f {{.State.Running}} $container_name 2>&1",
      $output,
      $return_code
    );

    // Check if container exists.
    if ($return_code > 0) {
      throw new \Exception(sprintf(self::ERROR_CONTAINER_MISSING, $container_name));
    }

    // Check if container stopped.
    if ($output[0] == "false") {
      throw new \Exception(sprintf(self::ERROR_CONTAINER_STOPPED, $container_name));
    }
  }

  /**
   * Get the project prefix.
   */
  public function getProjectPrefix() {
    $project_prefix = Robo::Config()->get('dockworker.instance.project_prefix');
    if (empty($project_prefix)) {
      throw new \Exception(sprintf(self::ERROR_PROJECT_PREFIX_UNSET, $this->configFile));
    }
    return $project_prefix;
  }

  /**
   * Get the upstream image.
   */
  public function getUpstreamImages() {
    $upstream_images = Robo::Config()->get('dockworker.instance.upstream_image');
    if (empty($upstream_images)) {
      throw new \Exception(sprintf(self::ERROR_UPSTREAM_IMAGE_UNSET, $this->configFile));
    }

    // Handle migration from scalar.
    if (!is_array($upstream_images)) {
      $upstream_images = [$upstream_images];
    }

    return $upstream_images;
  }

  /**
   * Get the upstream image.
   */
  public function getAllowsPrefixLessCommit() {
    $allow_prefixless_commits = Robo::Config()->get('dockworker.options.allow_prefixless_commits');
    if (empty($upstream_image)) {
      FALSE;
    }
    return (bool) $allow_prefixless_commits;
  }
}
