<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerException;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\BuilderAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\BuilderAwareInterface;
use Robo\Robo;
use Robo\Tasks;

/**
 * Defines a base class for all Dockworker Robo commands.
 */
class DockworkerCommands extends Tasks implements ContainerAwareInterface, LoggerAwareInterface, BuilderAwareInterface {

  use BuilderAwareTrait;
  use ConfigAwareTrait;
  use ContainerAwareTrait;
  use LoggerAwareTrait;

  const ERROR_CONFIG_VERSION = 'Invalid configuration version in %s. Config version must be at least 3.0';
  const ERROR_INSTANCE_NAME_UNSET = 'The application name value has not been set in %s';
  const ERROR_PROJECT_PREFIX_UNSET = 'The project_prefix variable has not been set in %s';
  const ERROR_UPSTREAM_IMAGE_UNSET = 'The upstream_image variable has not been set in %s';

  /**
   * The application's configuration object.
   *
   * @var \Consolidation\Config\ConfigInterface
   */
  protected $config;

  /**
   * The path to the configuration file.
   *
   * @var string
   */
  protected $configFile;

  /**
   * The instance name of the application.
   *
   * @var string
   */
  protected $instanceName;

  /**
   * The console output.
   *
   * @var object
   */
  protected $output;

  /**
   * The path to the Dockworker repo.
   *
   * @var string
   */
  protected $repoRoot;

  /**
   * DockworkerCommands constructor.
   */
  public function __construct() {
    $this->repoRoot = realpath(__DIR__ . "/../../../../../../../");
    $this->configFile = 'dockworker.yml';
    $this->output = $this->io();
    $this->config = Robo::loadConfiguration(
      [$this->repoRoot . '/' . $this->configFile]
    );
  }

  /**
   * Self-updates the dockworker application.
   *
   * @command dockworker:update
   * @aliases update
   *
   * @usage dockworker:update
   */
  public function getDockworkerUpdates() {
    $this->say('Checking for updates to unb-libraries/dockworker...');
    $this->taskExec('composer')
      ->dir($this->repoRoot)
      ->arg('update')
      ->arg('unb-libraries/dockworker')
      ->silent(TRUE)
      ->run();
  }

  /**
   * Get the instance name from config.
   *
   * @hook init
   * @throws \Dockworker\DockworkerException
   */
  public function setInstanceName() {
    $this->instanceName = Robo::Config()->get('dockworker.application.name');

    if (empty($this->instanceName)) {
      throw new DockworkerException(sprintf(self::ERROR_INSTANCE_NAME_UNSET, $this->configFile));
    }
  }

  /**
   * Ensure config and binary versions match.
   *
   * @hook init
   * @throws \Dockworker\DockworkerException
   */
  public function checkConfigVersion() {
    $version = Robo::Config()->get('dockworker.version');
    if (version_compare($version, '3') <= 0) {
      throw new DockworkerException(sprintf(self::ERROR_CONFIG_VERSION, $this->configFile));
    }
  }

  /**
   * Gets the project prefix.
   *
   * @throws \Dockworker\DockworkerException
   *
   * @return string
   *   The project prefix.
   */
  public function getProjectPrefix() {
    $project_prefix = Robo::Config()->get('dockworker.application.project_prefix');
    if (empty($project_prefix)) {
      throw new DockworkerException(sprintf(self::ERROR_PROJECT_PREFIX_UNSET, $this->configFile));
    }
    return $project_prefix;
  }

  /**
   * Gets the upstream image.
   *
   * @throws \Dockworker\DockworkerException
   *
   * @return string[]
   *   An array of upstream images configured for the repository.
   */
  public function getUpstreamImages() {
    $upstream_images = Robo::Config()->get('dockworker.application.upstream_images');
    if (empty($upstream_images)) {
      throw new DockworkerException(sprintf(self::ERROR_UPSTREAM_IMAGE_UNSET, $this->configFile));
    }

    // Handle migration from scalar.
    if (!is_array($upstream_images)) {
      $upstream_images = [$upstream_images];
    }

    return $upstream_images;
  }

  /**
   * Gets if the project allows prefix-less commit messages.
   */
  public function getAllowsPrefixLessCommit() {
    $allow_prefixless_commits = Robo::Config()->get('dockworker.options.allow_prefixless_commits');
    if (empty($upstream_image)) {
      FALSE;
    }
    return (bool) $allow_prefixless_commits;
  }

  /**
   * Runs another Dockworker command.
   *
   * This is necessary until the annotated-command feature request:
   * https://github.com/consolidation/annotated-command/issues/64 is merged
   * or solved. Otherwise hooks do not fire as expected.
   *
   * @param string $command_string
   *   The Dockworker command to run.
   * @param string $exception_message
   *   The message to display if a non-zero code is returned.
   *
   * @throws \Dockworker\DockworkerException
   *
   * @return int
   *   The return code of the command.
   */
  public function setRunOtherCommand($command_string, $exception_message = NULL) {
    $this->output->note("Spawning new command thread: $command_string");
    $bin = $_SERVER['argv'][0];
    $command = "$bin --ansi $command_string";

    passthru($command, $return);

    if ($return > 0) {
      throw new DockworkerException($exception_message);
    };
    return $return;
  }

  /**
   * Set up the required git hooks for dockworker.
   *
   * @command dockworker:git:setup-hooks
   *
   * @usage dockworker:git:setup-hooks
   */
  public function setupHooks() {
    $source_dir = $this->repoRoot . "/vendor/unb-libraries/dockworker/scripts/git-hooks";
    $target_dir = $this->repoRoot . "/.git/hooks";
    $this->_copy("$source_dir/commit-msg", "$target_dir/commit-msg");
  }

}
