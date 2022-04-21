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

  const DOCKWORKER_DATA_BASE_DIR = '/.config/dockworker';
  const ERROR_CONFIG_VERSION = 'Invalid configuration version in %s. Config version must be at least 3.0';
  const ERROR_INSTANCE_NAME_UNSET = 'The application name value has not been set in %s';
  const ERROR_PROJECT_PREFIX_UNSET = 'The project_prefix variable has not been set in %s';
  const ERROR_UPSTREAM_IMAGE_UNSET = 'The upstream_image variable has not been set in %s';
  const ERROR_UUID_UNSET = 'The application UUID value has not been set in %s';

  /**
   * The shell of the current application.
   *
   * @var string
   */
  protected $applicationShell = '/bin/sh';

  /**
   * The timestamp the command was started.
   *
   * @var string
   */
  protected $commandStartTime;

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
   * The local data directory for this application.
   *
   * @var string
   */
  protected $dockworkerApplicationDataDir;

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
   * The current user's configured kubectl username.
   *
   * @var string
   */
  protected $kubeUserName;

  /**
   * The current user's configured kubectl access token.
   *
   * @var string
   */
  protected $kubeUserToken;

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
   * The current user's configured home directory.
   *
   * @var string
   */
  protected $userHomeDir;

  /**
   * The current user's configured username.
   *
   * @var string
   */
  protected $userName;

  /**
   * The UNBLibraries uuid of the application.
   *
   * @var string
   */
  protected $uuid;


  /**
   * Sets the application shell used.
   *
   * @hook init
   */
  public function setApplicationShell() {
    $deployment_shell = Robo::Config()->get('dockworker.application.shell');
    if (!empty($deployment_shell)) {
      $this->applicationShell = $deployment_shell;
    }
  }

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
   * Sets the running user's details and credentials.
   *
   * @hook post-init
   * @throws \Dockworker\DockworkerException
   */
  public function setUserDetails() {
    $this->userName = get_current_user();
    $this->userHomeDir = $_SERVER['HOME'];
    $this->dockworkerApplicationDataDir = implode('/', [$this->userHomeDir, self::DOCKWORKER_DATA_BASE_DIR, $this->instanceName]);
    if (!file_exists($this->dockworkerApplicationDataDir)) {
      mkdir($this->dockworkerApplicationDataDir, 0755, TRUE);
    }
    $this->setUserKubeDetails();
  }

  /**
   * Sets the running user's details and credentials.
   *
   * @hook pre-init
   * @throws \Dockworker\DockworkerException
   */
  public function setCommandStartTime() {
    $this->commandStartTime = time();
  }

  /**
   * Sets the running user's kubernetes credentials.
   *
   * @throws \Dockworker\DockworkerException
   */
  public function setUserKubeDetails() {
   $kubectl_bin = shell_exec(sprintf("which %s", 'kubectl'));
   if (!empty($kubectl_bin) && is_executable($kubectl_bin)) {
     $user_name_cmd = 'kubectl config view --raw --output jsonpath=\'{$.users[0].name}\'';
     $this->kubeUserName = shell_exec($user_name_cmd);
     $user_token_cmd = 'kubectl config view --raw --output jsonpath=\'{$.users[0].user.token}\'';
     $this->kubeUserToken = shell_exec($user_token_cmd);
   }
  }

  /**
   * Determines if the current user has k8s details defined.
   *
   * @return bool
   */
  protected function k8sUserDetailsDefined() {
    return (!empty($this->kubeUserName) && !empty($this->kubeUserToken));
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
