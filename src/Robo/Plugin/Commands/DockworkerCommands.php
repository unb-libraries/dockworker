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
use Dockworker\DockworkerIO;
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
     * The application's IO interface point.
     *
     * @var \Dockworker\DockworkerIO
     */
    protected DockworkerIO $dockworkerIO;

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
     * @link https://systems.lib.unb.ca/wiki/systems:docker:unique-site-uuids UNB Libraries UUIDs
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
        $this->setCoreProperties();
    }

    /**
     * Initializes the application's core properties.
     *
     * @throws \Dockworker\DockworkerException
     */
    public function setCoreProperties(): void
    {
        $this->setCommandPropertyFromConfigKey(
            'applicationName',
            'dockworker.application.identifiers.id'
        );
        $this->setCommandPropertyFromConfigKey(
            'applicationSlug',
            'dockworker.application.identifiers.slug'
        );
        $this->setCommandPropertyFromConfigKey(
            'applicationShortSlug',
            'dockworker.application.identifiers.short_slug'
        );
        $this->setCommandPropertyFromConfigKey(
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
    protected function setCommandPropertyFromConfigKey(
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
     * Gets a configuration item from the Dockworker configuration.
     *
     * @param string $config_key
     *   The configuration key to retrieve.
     * @param mixed $default_value
     *   The default value to return if the configuration key is not set.
     *
     * @return mixed
     *   The configuration value.
     */
    protected function getConfigItem(
        string $config_key,
        mixed $default_value = null
    ): mixed {
        return Robo::Config()->get($config_key, $default_value);
    }

    /**
     * Runs another Dockworker command.
     *
     * This is necessary until the annotated-command feature request:
     * https://github.com/consolidation/annotated-command/issues/64 is merged
     * or solved. Otherwise, hooks do not fire as expected.
     *
     * @param ConsoleIO $io
     *   The console IO.
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
        ConsoleIO $io,
        string $command_string,
        string $exception_message = ''
    ): int {
        $this->dockworkerNote(
            $io,
            ["Spawning new command thread: $command_string"]
        );
        $bin = $_SERVER['argv'][0];
        $command = "$bin --ansi $command_string";
        passthru($command, $return);

        if ($return > 0) {
            throw new DockworkerException($exception_message);
        }
        return $return;
    }
}
