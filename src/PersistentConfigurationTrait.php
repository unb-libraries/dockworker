<?php

namespace Dockworker;

use Consolidation\Config\ConfigInterface;
use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;
use Robo\Config\Config;
use Robo\Symfony\ConsoleIO;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides methods to interact with persistent configuration.
 */
trait PersistentConfigurationTrait
{
    use DockworkerIOTrait;
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
     * Gets a configuration item's value, set and write it from a query if unset.
     *
     * @param \Robo\Symfony\ConsoleIO $io
     *   The console IO.
     * @param string $path
     *   The path to load the configuration from.
     * @param string $namespace
     *   The configuration namespace to retrieve from.
     * @param string $item
     *   The item to set.
     * @param string $query
     *   The query to display if the configuration item is unset.
     * @param string $default_value
     *   Optional. The default query response, defaults to none.
     * @param string $description
     *   Optional. A description offering further information about the item.
     * @param string[] $reference_uris
     *   Optional. Labels and URIs to display to support describing the item.
     * @param string $env_var_override_name
     *   Optional. An OS environment variable name whose value overrides configuration.
     *
     * @return mixed
     *   The value of the configuration item.
     */
    protected function getSetPersistentConfigurationItem(
        ConsoleIO $io,
        string $path,
        string $namespace,
        string $item,
        string $query,
        string $default_value = '',
        string $description = '',
        array $reference_uris = [],
        string $env_var_override_name = ''
    ): mixed {
        if (!empty($env_var_override_name)) {
            $env_value = getenv($env_var_override_name);
            if (!empty($env_value)) {
                return $env_value;
            }
        }
        $value = $this->getPersistentConfigurationItem(
            $path,
            $namespace,
            $item
        );
        if (empty($value)) {
            if (!empty($description)) {
                $this->dockworkerOutputBlock(
                    $io,
                    [$description]
                );
            }
            if (!empty($reference_uris)) {
                foreach ($reference_uris as $reference_uri) {
                    $this->dockworkerNote(
                        $io,
                        ["{$reference_uri['label']}: {$reference_uri['uri']}"]
                    );
                }
            }
            $ans_value = $this->dockworkerAsk(
                $io,
                $query,
                $default_value
            );
            $this->setWritePersistentConfigurationItem(
                $path,
                $namespace,
                $item,
                $ans_value
            );
            return $ans_value;
        }
        return $value;
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
     * @return mixed
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
                'dockworker:'
            );
            chmod($path, 0600);
        }
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
     * @param mixed $value
     *   The value to set.
     */
    protected function setWritePersistentConfigurationItem(
        string $path,
        string $namespace,
        string $item,
        mixed $value
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
     * Sets a configuration item's value in the persistent configuration.
     *
     * @param string $path
     *   The path to load the configuration from.
     * @param string $namespace
     *   The configuration namespace to set in.
     * @param string $item
     *   The item to set.
     * @param mixed $value
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
}
