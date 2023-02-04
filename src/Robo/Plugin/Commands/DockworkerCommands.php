<?php

namespace Dockworker\Robo\Plugin\Commands;

use CzProject\GitPhp\GitRepository;
use Dockworker\CommandRuntimeTrackerTrait;
use Dockworker\DestructiveActionTrait;
use Dockworker\DockworkerException;
use Dockworker\FileSystemOperationsTrait;
use Dockworker\GitRepoTrait;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Common\ExecTrait;
use Robo\Common\IO;
use Robo\Contract\ConfigAwareInterface;
use Robo\Contract\IOAwareInterface;
use Robo\Robo;
use \Robo\Tasks;

/**
 * Defines a base class for all Dockworker Robo commands.
 */
abstract class DockworkerCommands extends Tasks implements ConfigAwareInterface, ContainerAwareInterface, IOAwareInterface, LoggerAwareInterface
{
    use CommandRuntimeTrackerTrait;
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use DestructiveActionTrait;
    use FileSystemOperationsTrait;
    use GitRepoTrait;
    use IO;
    use LoggerAwareTrait;

    protected const APPLICATION_DATA_STORAGE_BASE_DIR = '.dockworker/data';
    protected const DOCKWORKER_CONFIG_FILE = '.dockworker/dockworker.yml';
    protected const DOCKWORKER_DATA_STORAGE_BASE_DIR = '.config/dockworker';
    protected const ERROR_CONFIG_ELEMENT_UNSET = 'Error! A required configuration element [%s] does not exist in %s.';

    /**
     * The name of the application.
     *
     * @var string
     */
    protected string $applicationName;

    /**
     * The path to the application's data storage directory.
     *
     * @var string
     */
    protected string $applicationDataStorageDir;

    /**
     * The 'slug' of the application.
     *
     * @var string
     */
    protected string $applicationSlug;

    /**
    * The shortened slug of the application.
    *
    * @var string
    */
    protected string $applicationShortSlug;

    /**
     * The full path to the application's dockworker configuration file.
     *
     * @var string
     */
    protected string $configFile;

    /**
     * The path to the dockworker data storage directory.
     *
     * @var string
     */
    protected string $dockworkerDataStorageDir;

    /**
     * The path to the application's git repository.
     *
     * @var string
     */
    protected string $repoRoot;

    /**
     * The application's git repository.
     *
     * @var \CzProject\GitPhp\GitRepository;
     */
    protected GitRepository $repoGit;

    /**
     * The current user's operating system home directory.
     *
     * @var string
     */
    protected string $userHomeDir;

    /**
     * The current user's operating system username.
     *
     * @var string
     */
    protected string $userName;

    /**
     * The UNB Libraries application uuid for the application.
     *
     * @link https://systems.lib.unb.ca/wiki/systems:docker:unique-site-uuids
     * @var string
     */
    protected string $uuid;

    /**
     * DockworkerCommands constructor.
     */
    public function __construct()
    {
        $this->repoRoot = realpath(__DIR__ . "/../../../../../../../");
        $this->configFile = $this->getPathFromPathElements(
            [
                $this->repoRoot,
                self::DOCKWORKER_CONFIG_FILE,
            ]
        );
        Robo::loadConfiguration(
            [$this->configFile],
            $this->config
        );
        $this->userName = get_current_user();
        $this->userHomeDir = $_SERVER['HOME'];
    }

    /**
     * Updates the dockworker package to the latest release.
     *
     * @command dockworker:update
     * @aliases update
     * @hidden
     */
    public function updateDockworker(): void
    {
        $this->io()->title("Updating Dockworker");
        $this->say('Checking for any updates to unb-libraries/dockworker...');
        $this->taskExec('composer')
          ->dir($this->repoRoot)
          ->arg('update')
          ->arg('unb-libraries/dockworker')
          ->silent(true)
          ->run();
    }

    /**
     * Provides a pre-init hook that assigns core properties and configuration.
     *
     * @hook pre-init
     * @throws \Dockworker\DockworkerException
     */
    public function preInitDockworkerCommands(): void
    {
        $this->setCommandStartTime();
        $this->setCoreProperties();
        $this->initDataStorageDirs();
        $this->setGitRepo();
    }

    /**
     * Initializes the application's core properties.
     *
     * @throws \Dockworker\DockworkerException
     */
    protected function setCoreProperties(): void
    {
        $this->setPropertyFromConfigKey(
            'applicationName',
            'dockworker.application.identifiers.id'
        );
        $this->setPropertyFromConfigKey(
            'applicationSlug',
            'dockworker.application.identifiers.slug'
        );
        $this->setPropertyFromConfigKey(
            'applicationShortSlug',
            'dockworker.application.identifiers.short_slug'
        );
        $this->setPropertyFromConfigKey(
            'uuid',
            'dockworker.application.identifiers.uuid'
        );
    }

    /**
     * Sets a command object property from a config element.
     *
     * @param string $property
     *  The property to set.
     * @param string $config_key
     *  The namespace to obtain the configuration value from.
     *
     * @throws \Dockworker\DockworkerException
     */
    protected function setPropertyFromConfigKey(
        string $property,
        string $config_key
    ): void {
        $config_value = Robo::Config()->get($config_key);
        if (empty($config_value)) {
            throw new DockworkerException(sprintf(
                self::ERROR_CONFIG_ELEMENT_UNSET,
                $config_key,
                $this->configFile
            ));
        }
        $this->$property = $config_value;
    }

    /**
     * Initializes the data storage for dockworker and the application.
     *
     */
    protected function initDataStorageDirs(): void
    {
        $this->setDockworkerDataStorageDir();
        $this->setApplicationDataStorageDir();
    }

    /**
     * Sets the dockworker data storage directory.
     */
    protected function setDockworkerDataStorageDir(): void
    {
        $this->dockworkerDataStorageDir = $this->initGetPathFromPathElements(
            [
                $this->userHomeDir,
                self::DOCKWORKER_DATA_STORAGE_BASE_DIR,
                $this->applicationName,
            ]
        );
    }

    /**
     * Sets the application's data storage directory.
     */
    protected function setApplicationDataStorageDir(): void
    {
        $this->applicationDataStorageDir = $this->initGetPathFromPathElements(
            [
                $this->repoRoot,
                self::APPLICATION_DATA_STORAGE_BASE_DIR
            ]
        );
    }

    /**
     * Sets up the lean repository git repo.
     */
    protected function setGitRepo(): void
    {
        $this->repoGit = $this->getGitRepoFromPath($this->repoRoot);
        if (empty($this->repoGit)) {
            throw new DockworkerException('Could not initialize the git repository.');
        }
    }

    /**
     * Displays the command's total run time.
     *
     * @hook post-command
     */
    public function displayCommandRunTime(): void
    {
        if ($this->displayCommandRunTime) {
            $this->say($this->getTimeSinceCommandStart());
        }
    }

    /**
     * Sets up the required git hooks for dockworker.
     *
     * @command dockworker:git:setup-hooks
     */
    public function setupGitHooks(): void
    {
        // $this->getPathFromPathElements();
        $source_dir = $this->repoRoot . "/vendor/unb-libraries/dockworker/data/scripts/git-hooks";
        $target_dir = $this->repoRoot . "/.git/hooks";
        $this->_copy("$source_dir/commit-msg", "$target_dir/commit-msg");
    }

    /**
     * Runs another Dockworker command.
     *
     * This is necessary until the annotated-command feature request:
     * https://github.com/consolidation/annotated-command/issues/64 is merged
     * or solved. Otherwise, hooks do not fire as expected.
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
    public function setRunOtherCommand(
        string $command_string,
        string $exception_message = ''
    ): int {
        $this->io()->note("Spawning new command thread: $command_string");
        $bin = $_SERVER['argv'][0];
        $command = "$bin --ansi $command_string";
        passthru($command, $return);

        if ($return > 0) {
            throw new DockworkerException($exception_message);
        }
        return $return;
    }
}
