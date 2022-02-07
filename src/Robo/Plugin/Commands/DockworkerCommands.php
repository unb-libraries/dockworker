<?php

namespace Dockworker\Robo\Plugin\Commands;

use CzProject\GitPhp\Git;
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
use Symfony\Component\Yaml\Yaml;

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
  const ERROR_UUID_UNSET = 'The application UUID value has not been set in %s';

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
   * The instance slug of the application.
   *
   * @var string
   */
  protected $instanceSlug;

  /**
   * The options passed to the command.
   *
   * @var array
   */
  protected $options;

  /**
   * The path to the Dockworker repo.
   *
   * @var string
   */
  protected $repoRoot;

  /**
   * The git repository.
   *
   * @var CzProject\GitPhp\Git;
   */
  protected $repoGit;

  /**
   * The UNBLibraries uuid of the application.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The current user's name.
   *
   * @var string
   */
  protected $userName;

  /**
   * The current user's token.
   *
   * @var string
   */
  protected $userToken;


  /**
   * DockworkerCommands constructor.
   */
  public function __construct() {
    $this->repoRoot = realpath(__DIR__ . "/../../../../../../../");
    $this->configFile = '.dockworker/dockworker.yml';
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
    $this->instanceSlug = str_replace('.', '-', $this->instanceName);

    if (empty($this->instanceName)) {
      throw new DockworkerException(sprintf(self::ERROR_INSTANCE_NAME_UNSET, $this->configFile));
    }
  }

  /**
   * Sets the running user credentials.
   *
   * @hook init
   * @throws \Dockworker\DockworkerException
   */
  public function setUserDetails() {
   $kubectl_bin = shell_exec(sprintf("which %s", 'kubectl'));
   if (!empty($kubectl_bin) && is_executable($kubectl_bin)) {
     $user_name_cmd = 'kubectl config view --raw --output jsonpath=\'{$.users[0].name}\'';
     $this->userName = shell_exec($user_name_cmd);
     $user_token_cmd = 'kubectl config view --raw --output jsonpath=\'{$.users[0].user.token}\'';
     $this->userToken = shell_exec($user_token_cmd);
   }
  }

  /**
   * Determines if the current user has details defined.
   *
   * @return bool
   */
  protected function userDetailsDefined() {
    return (!empty($this->userName) && !empty($this->userToken));
  }

  /**
   * Sets the git repo.
   *
   * @hook pre-init
   */
  public function setGitRepo() {
    $git = new Git;
    $this->repoGit = $git->open($this->repoRoot);
  }

  /**
   * Get the UUID from config.
   *
   * @hook init
   * @throws \Dockworker\DockworkerException
   */
  public function setUuid() {
    $this->uuid = Robo::Config()->get('dockworker.application.uuid');

    if (empty($this->uuid)) {
      throw new DockworkerException(sprintf(self::ERROR_UUID_UNSET, $this->configFile));
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
   * Gets the main project prefix.
   *
   * @throws \Dockworker\DockworkerException
   *
   * @return string
   *   The main project prefix.
   */
  public function getProjectPrefix() {
    $project_prefix = Robo::Config()->get('dockworker.application.project_prefix');
    if (empty($project_prefix)) {
      throw new DockworkerException(sprintf(self::ERROR_PROJECT_PREFIX_UNSET, $this->configFile));
    }
    if (is_array($project_prefix)) {
      return $project_prefix[0];
    }
    return $project_prefix;
  }

  /**
   * Gets all project prefixes.
   *
   * @throws \Dockworker\DockworkerException
   *
   * @return array
   *   The project prefixes.
   */
  public function getProjectPrefixes() {
    $project_prefixes = Robo::Config()->get('dockworker.application.project_prefix');
    if (empty($project_prefixes)) {
      throw new DockworkerException(sprintf(self::ERROR_PROJECT_PREFIX_UNSET, $this->configFile));
    }
    if (is_array($project_prefixes)) {
      return $project_prefixes;
    }
    return [$project_prefixes];
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
    $this->io()->note("Spawning new command thread: $command_string");
    $bin = $_SERVER['argv'][0];
    $command = "$bin --ansi $command_string";
    passthru($command, $return);

    if ($return > 0) {
      throw new DockworkerException($exception_message);
    }
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

  /**
   * Retrieves a list of changed files in the repository.
   *
   * 'Inspired' by https://github.com/czproject/git-php/pull/42/files
   *
   * @param $file_mask
   *   The regex pattern to search for, as a string.
   *
   * @return string[]
   *   The changed files, keyed by file path and values indicating status.
   */
  protected function getGitRepoChanges($file_mask = '') {
    $this->repoGit->execute('update-index', '-q', '--refresh');
    $output = $this->repoGit->execute('status', '--porcelain');
    $files = [];
    foreach($output as $line) {
      $line = trim($line);
      $file = explode(" ", $line, 2);
      if(count($file) >= 2){
        if (empty($file_mask) || preg_match($file_mask, $file[1])) {
          $files[$file[1]] = $file[0];
        }
      }
    }
    return $files;
  }

}
