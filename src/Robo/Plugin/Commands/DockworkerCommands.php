<?php

namespace Dockworker\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandData;
use CzProject\GitPhp\GitRepository;
use Dockworker\CliToolTrait;
use Dockworker\CommandRuntimeTrackerTrait;
use Dockworker\DestructiveActionTrait;
use Dockworker\DockworkerApplicationLocalDataStorageTrait;
use Dockworker\DockworkerApplicationPersistentDataStorageTrait;
use Dockworker\DockworkerException;
use Dockworker\DockworkerIOTrait;
use Dockworker\DockworkerPersistentDataStorageTrait;
use Dockworker\FileSystemOperationsTrait;
use Dockworker\GitRepoTrait;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Contract\IOAwareInterface;
use Robo\Robo;
use Robo\Symfony\ConsoleIO;
use Robo\Tasks;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Defines a base class for all Dockworker commands.
 */
abstract class DockworkerCommands extends Tasks implements ConfigAwareInterface, ContainerAwareInterface, IOAwareInterface, LoggerAwareInterface
{
    use CliToolTrait;
    use CommandRuntimeTrackerTrait;
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use DestructiveActionTrait;
    use DockworkerApplicationLocalDataStorageTrait;
    use DockworkerApplicationPersistentDataStorageTrait;
    use DockworkerIOTrait;
    use DockworkerPersistentDataStorageTrait;
    use FileSystemOperationsTrait;
    use GitRepoTrait;
    use LoggerAwareTrait;

    protected const DOCKWORKER_CONFIG_FILE = '.dockworker/dockworker.yml';
    protected const ERROR_CONFIG_ELEMENT_UNSET = 'Error! A required configuration element [%s] does not exist in %s.';

    /**
     * The name of the application.
     *
     * @var string
     */
    protected string $applicationName;

    /**
     * The application's git repository.
     *
     * @var \CzProject\GitPhp\GitRepository;
     */
    protected GitRepository $applicationRepository;

    /**
     * The path to the application's git repository.
     *
     * @var string
     */
    protected string $applicationRoot;

    /**
    * The shortened slug of the application.
    *
    * @var string
    */
    protected string $applicationShortSlug;

    /**
     * The 'slug' of the application.
     *
     * @var string
     */
    protected string $applicationSlug;

    /**
     * The full path to the application's dockworker configuration file.
     *
     * @var string
     */
    protected string $configFile;

    /**
     * A list of Jira project keys that apply to all projects.
     *
     * @var string[]
     */
    protected array $jiraGlobalProjectKeys = ['IN', 'DOCKW'];

    /**
     * The Jira project keys relating to this application.
     *
     * @var string[]
     */
    protected array $jiraProjectKeys = [];

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
        $this->applicationRoot = realpath(__DIR__ . "/../../../../../../../");
        $this->configFile = $this->getPathFromPathElements(
            [
                $this->applicationRoot,
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
    public function updateDockworker(ConsoleIO $io): void
    {
        $this->dockworkerTitle(
            $io,
            'Updating Dockworker'
        );
        $this->dockworkerSay(
            $io,
            ['Checking for any updates to unb-libraries/dockworker...'])
        ;
        $this->taskExec('composer')
          ->dir($this->applicationRoot)
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
        if ($config_value == null) {
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
        $this->initApplicationLocalDataStorageDir(
            $this->userHomeDir,
            $this->applicationName
        );
        $this->initApplicationPersistentDataStorageDir($this->applicationRoot);
        $this->initDockworkerPersistentDataStorageDir($this->userHomeDir);
    }

    /**
     * Sets up the lean repository git repo.
     *
     * @throws \Dockworker\DockworkerException
     */
    protected function setGitRepo(): void
    {
        $this->applicationRepository = $this->getGitRepoFromPath($this->applicationRoot);
        if (empty($this->applicationRepository)) {
            throw new DockworkerException('Could not initialize the git repository.');
        }
    }

    /**
     * Initializes the Jira properties for the application.
     *
     * @hook pre-init @jira
     */
    public function setJiraProperties(): void
    {
        $jira_project_keys = $this->getConfigItem(
            'dockworker.application.jira.project_keys'
        );
        if ($jira_project_keys != null) {
            $this->jiraProjectKeys = array_merge(
                $this->jiraGlobalProjectKeys,
                $jira_project_keys
            );
        }
    }

    protected function getConfigItem(string $config_key, $default_value = null): mixed
    {
        return Robo::Config()->get($config_key, $default_value);
    }

    /**
     * Registers kubectl as a required CLI tool.
     *
     * @hook interact @kubectl
     */
    public function registerKubeCtlCliTool(
        InputInterface $input,
        OutputInterface $output,
        AnnotationData $annotationData
    ): void {
        $io = new ConsoleIO($input, $output);
        $file_path = "$this->applicationRoot/vendor/unb-libraries/dockworker/data/cli-tools/kubectl.yml";
        $this->registerCliToolFromYaml($io, $file_path);
    }

    /**
     * Check all registered CLI tools.
     *
     * @hook validate
     *
     * @throws \Dockworker\DockworkerException
     */
    public function checkRegisteredCliTools(CommandData $commandData): void {
        $io = new ConsoleIO($commandData->input(), $commandData->output());
        $this->checkRegisteredCliToolCommands($io);
    }

    /**
     * Trigger the display of the command's total run time.
     *
     * @hook post-process
     */
    public function triggerDisplayCommandRunTime(
        $result,
        CommandData $commandData
    ): void {
        $io = new ConsoleIO($commandData->input(), $commandData->output());
        $this->displayCommandRunTime($io);
    }

    /**
     * Sets up the required git hooks for dockworker.
     *
     * @command dockworker:git:setup-hooks
     */
    public function setupGitHooks(): void
    {
        $hooks = ['commit-msg'];
        foreach ($hooks as $hook) {
            $source_file = $this->getPathFromPathElements(
                [
                    $this->applicationRoot,
                    'vendor/unb-libraries/dockworker/data/scripts/git-hooks',
                    $hook,
                ]
            );
            $target_file = $this->getPathFromPathElements(
                [
                    $this->applicationRoot,
                    '.git/hooks',
                    $hook,
                ]
            );
            $this->_copy($source_file, $target_file);
        }
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
        $this->dockworkerNote(["Spawning new command thread: $command_string"]);
        $bin = $_SERVER['argv'][0];
        $command = "$bin --ansi $command_string";
        passthru($command, $return);

        if ($return > 0) {
            throw new DockworkerException($exception_message);
        }
        return $return;
    }
}
