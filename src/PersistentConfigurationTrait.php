<?php

namespace Dockworker;

use Consolidation\Config\ConfigInterface;
use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;
use Dockworker\FileSystemOperationsTrait;
use Robo\Config\Config;
use Robo\Symfony\ConsoleIO;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides methods to interact with persistent configuration.
 */
trait PersistentConfigurationTrait
{
    use FileSystemOperationsTrait;

    /**
     * The persistent configuration filepath.
     *
     * @var string
     */
    protected string $persistentConfigurationFilePath;

    /**
     * The persistent configuration.
     *
     * @var \Consolidation\Config\ConfigInterface
     */
    protected ConfigInterface $persistentConfiguration;

    /**
     * Loads the persistent configuration.
     */
    protected function loadPersistentConfiguration(): void
    {
        $this->createPersistentConfigurationFileIfNotExists(
            $this->persistentConfigurationFilePath
        );
        $loader = new YamlConfigLoader();
        $processor = new ConfigProcessor();
        $processor->extend(
            $loader->load($this->persistentConfigurationFilePath)
        );
        $config = $processor->export();
        if (!empty($config)) {
            $this->persistentConfiguration = new Config($config);
        }
    }

    /**
     * Initializes and sets up the persistent configuration object.
     *
     * @param string $path
     *   The path to load the configuration from.
     * @param string $namespace
     *   The configuration namespace to initialize in.
     * @param bool $force_reload
     *   If TRUE, config is always loaded from disk regardless of the state.
     */
    protected function initPersistentConfiguration(
        string $path,
        string $namespace,
        bool $force_reload = false
    ): void {
        $this->persistentConfigurationFilePath = $this->getPathFromPathElements(
            [
                $path,
                "$namespace.yml",
            ]
        );
        if ($force_reload || empty($this->persistentConfiguration)) {
            $this->loadPersistentConfiguration();
        }
    }

    /**
     * Creates an empty persistent configuration file.
     *
     * @param string $path
     *   The path to the persistent configuration file.
     */
    protected function createPersistentConfigurationFileIfNotExists(
        string $path
    ): void {
        if (!is_file($path)) {
            file_put_contents(
                $path,
                ''
            );
            chmod($path, 0600);
        }
    }

    /**
     * Retrieves a configuration item's value from persistent configuration.
     *
     * @param string $path
     *   The path to load the configuration from.
     * @param string $namespace
     *   The configuration namespace to retrieve from.
     * @param string $item
     *   The item to retrieve.
     *
     * @return string
     *   The value of the item.
     */
    protected function getPersistentConfigurationItem(
        string $path,
        string $namespace,
        string $item
    ): mixed {
        $this->initPersistentConfiguration($path, $namespace);
        return $this->persistentConfiguration->get($item);
    }

    /**
     * Sets a configuration item's value in the persistent configuration.
     *
     * @param string $path
     *   The path to load the configuration from.
     * @param string $namespace
     *   The configuration namespace to set in.
     * @param string $item
     *   The item to set.
     * @param string $value
     *   The value to set.
     */
    protected function setPersistentConfigurationItem(
        string $path,
        string $namespace,
        string $item,
        mixed $value
    ): void {
        $this->initPersistentConfiguration($path, $namespace);
        $this->persistentConfiguration->set($item, $value);
    }

    /**
     * Sets a configuration item value and persists the configuration to disk.
     *
     * @param string $path
     *   The path to load the configuration from.
     * @param string $namespace
     *   The configuration namespace to set in.
     * @param string $item
     *   The item to set.
     * @param string $value
     *   The value to set.
     */
    protected function setWritePersistentConfigurationItem(
        string $path,
        string $namespace,
        string $item,
        string $value
    ): void {
        $this->setPersistentConfigurationItem(
            $path,
            $namespace,
            $item,
            $value
        );
        $this->writePersistentConfigurationToDisk();
    }

    /**
     * Writes the currently loaded persistent configuration to disk.
     */
    protected function writePersistentConfigurationToDisk(): void
    {
        $yaml = Yaml::dump(
            $this->persistentConfiguration->export(),
            5
        );
        file_put_contents(
            $this->persistentConfigurationFilePath,
            $yaml
        );
    }

    /**
     * Gets a configuration item's value, set and write it from a query if unset.
     *
     * @param string $path
     *   The path to load the configuration from.
     * @param string $namespace
     *   The configuration namespace to retrieve from.
     * @param string $item
     *   The item to set.
     * @param string $query
     *   The query to display if the configuration item is unset.
     * @param \Robo\Symfony\ConsoleIO $io
     *   The IO to use when displaying the query.
     * @param string $default
     *   Optional. The default query response, defaults to none.
     * @param string $env_var_override_name
     *   Optional. An OS environment variable name whose value overrides configuration.
     *
     * @return mixed
     *   The value of the configuration item.
     */
    protected function getSetPersistentConfigurationItem(
        string $path,
        string $namespace,
        string $item,
        string $query,
        ConsoleIO $io,
        string $default = '',
        string $env_var_override_name = ''
    ): mixed {
        if (!empty($env_var_override_name)) {
            $env_value = getenv($env_var_override_name);
            if (!empty($env_value)) {
                return $env_value;
            }
        }
        $cur_value = $this->getPersistentConfigurationItem(
            $path,
            $namespace,
            $item
        );
        if (empty($cur_value)) {
            $ans_value = $io->ask($query, $default);
            $this->setWritePersistentConfigurationItem(
                $path,
                $namespace,
                $item,
                $ans_value
            );
            return $ans_value;
        }
        return $cur_value;
    }
}
