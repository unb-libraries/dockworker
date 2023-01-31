<?php

namespace Dockworker\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\CommandData;
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
use Robo\Symfony\ConsoleIO;
use Robo\Tasks;
use Symfony\Component\Yaml\Yaml;

/**
 * Defines a base class for all Dockworker Robo commands.
 */
class DockworkerBaseCommands extends Tasks implements ContainerAwareInterface, LoggerAwareInterface, BuilderAwareInterface {

  use BuilderAwareTrait;
  use ConfigAwareTrait;
  use ContainerAwareTrait;
  use LoggerAwareTrait;

  const DOCKWORKER_GLOBAL_DATA_BASE_DIR = '.config/dockworker';
  const DOCKWORKER_LOCAL_DATA_BASE_DIR = '.dockworker/data';
  const ERROR_BUILDING_IMAGE = 'Error reported building image!';
  const ERROR_INSTANCE_NAME_UNSET = 'The application name value has not been set in %s';
  const ERROR_PROJECT_PREFIX_UNSET = 'The project_prefix variable has not been set in %s';
  const ERROR_REQUIRED_ENV_UNSET = 'A required environment variable %s (%s) is not set in the current shell';
  const ERROR_UPSTREAM_IMAGE_UNSET = 'The upstream_image variable has not been set in %s';
  const ERROR_UUID_UNSET = 'The application UUID value has not been set in %s';

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
   * Should the command display its total runtime when complete?
   *
   * @var bool
   */
  protected $displayCommandRunTime = FALSE;

  /**
   * The local data directory for this application.
   *
   * @var string
   */
  protected $dockworkerApplicationDataDir;

  /**
   * The global dockworker data directory.
   *
   * @var string
   */
  protected $dockworkerGlobalDataDir;

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
   * DockworkerCommands constructor.
   */
  public function __construct() {
    $this->repoRoot = realpath(__DIR__ . "/../../../../../../../");
    $this->configFile = '.dockworker/dockworker.yml';
    $this->config = Robo::loadConfiguration(
      [$this->repoRoot . '/' . $this->configFile]
    );
    $this->userName = get_current_user();
    $this->userHomeDir = $_SERVER['HOME'];
  }

  /**
   * Self-updates dockworker.
   *
   * @command dockworker:update
   * @aliases update
   */
  public function getDockworkerUpdates() {
    $this->io()->title("Updating Dockworker");
    $this->say('Checking for any updates to unb-libraries/dockworker...');
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
   * @hook pre-init
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
   * Verifies that the required local environment variables are set.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function checkRequiredEnvironmentVariables() {
    $env_vars = Robo::Config()->get('dockworker.application.local.env_passthrough');

    if (empty($env_vars)) {
      return;
    }

    foreach ($env_vars as $env_var => $env_var_desc) {
      if (empty(getenv($env_var))) {
        throw new DockworkerException(
          sprintf(
            self::ERROR_REQUIRED_ENV_UNSET,
            $env_var,
            $env_var_desc
          )
        );
      }
    }
  }

  /**
   * Sets the global data dir.
   *
   * @hook init
   * @throws \Dockworker\DockworkerException
   */
  public function setDockworkerGlobalDataDir() {
    $this->dockworkerGlobalDataDir = implode('/', [$this->userHomeDir, self::DOCKWORKER_GLOBAL_DATA_BASE_DIR, $this->instanceName]);
    if (!file_exists($this->dockworkerGlobalDataDir)) {
      mkdir($this->dockworkerGlobalDataDir, 0755, TRUE);
    }
  }

  /**
   * Sets the application data dirs.
   *
   * @hook init
   * @throws \Dockworker\DockworkerException
   */
  public function setApplicationDataDir() {
    $this->dockworkerApplicationDataDir = implode('/', [$this->repoRoot, self::DOCKWORKER_LOCAL_DATA_BASE_DIR]);
    if (!file_exists($this->dockworkerApplicationDataDir)) {
      mkdir($this->dockworkerApplicationDataDir, 0755, TRUE);
    }
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
   * Displays the command's total run time.
   *
   * @hook post-command
   */
  public function displayCommandRunTime($result, CommandData $commandData) {
    if ($this->displayCommandRunTime) {
      date_default_timezone_set('UTC');
      $start = new \DateTime("@$this->commandStartTime");
      $end = new \DateTime();
      $diff = $start->diff($end);
      $run_string = $diff->format('%H:%I:%S');
      $this->say("Command run time: $run_string");
    }
  }

  /**
   * Sets up the lean repository git repo.
   *
   * @hook pre-init
   */
  public function setGitRepo() {
    $git = new Git;
    $this->repoGit = $git->open($this->repoRoot);
  }

  /**
   * Determines the UUID from config.
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
   * Determines the main project prefix.
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
   * Retrieves all defined project prefixes from the application configuration.
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
   * Sets up the required git hooks for dockworker.
   *
   * @command dockworker:git:setup-hooks
   */
  public function setupHooks() {
    $source_dir = $this->repoRoot . "/vendor/unb-libraries/dockworker-base/data/scripts/git-hooks";
    $target_dir = $this->repoRoot . "/.git/hooks";
    $this->_copy("$source_dir/commit-msg", "$target_dir/commit-msg");
  }

  /**
   * Retrieves the upstream images defined in the application configuration.
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
  public function setRunOtherCommand($command_string, $exception_message = '') {
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

  /**
   * Enable this command's total run time display upon completion.
   */
  protected function enableCommandRunTimeDisplay() {
    $this->displayCommandRunTime = TRUE;
  }

  /**
   * Disables this command's total run time display upon completion.
   */
  protected function disableCommandRunTimeDisplay() {
    $this->displayCommandRunTime = FALSE;
  }

  /**
   * Warns the user that a destructive action is about to be performed.
   *
   * @param \Robo\Symfony\ConsoleIO $io
   *   The IO to use when printing the statement.
   */
  protected function warnDestructiveAction(ConsoleIO $io) : void {
    $io->warning('Destructive, Irreversible Actions Ahead!');
  }

  /**
   * Determines if the user wishes to proceed with a destructive action.
   *
   * @param \Robo\Symfony\ConsoleIO $io
   *   The IO to use when printing the statement.
   * @param string $prompt
   *   The prompt to display to the user.
   *
   * @return bool
   *   TRUE if the user wishes to continue. False otherwise.
   */
  protected function warnConfirmDestructiveAction(ConsoleIO $io, string $prompt) : bool {
    $this->warnDestructiveAction($io);
    return ($io->confirm($prompt, FALSE));
  }

  /**
   * Warns, prompts the user for and conditionally exits the script.
   *
   * @param \Robo\Symfony\ConsoleIO $io
   *   The IO to use when printing the statement.
   * @param string $prompt
   *   The prompt to display to the user.
   */
  protected function warnConfirmExitDestructiveAction(ConsoleIO $io, string $prompt) {
    if (
      $this->warnConfirmDestructiveAction(
        $io,
        $prompt
      ) !== TRUE
    ) {
      exit(0);
    }
  }

  protected function constructRepoPathString(array $components) {
    array_unshift($components, $this->repoRoot);
    return implode(
      '/',
      $components
    );
  }

}
