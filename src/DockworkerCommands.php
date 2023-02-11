<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Contract\IOAwareInterface;
use Robo\Robo;
use Robo\Tasks;

/**
 * Defines a base abstract class for all Dockworker commands.
 *
 * This class should not contain any hooks or commands.
 */
abstract class DockworkerCommands extends Tasks implements ConfigAwareInterface, ContainerAwareInterface, IOAwareInterface, LoggerAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use FileSystemOperationsTrait;
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
     *
     * @throws \Dockworker\DockworkerException
     */
    public function __construct()
    {
        $this->applicationRoot = realpath(__DIR__ . "/../../../../");
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
}
