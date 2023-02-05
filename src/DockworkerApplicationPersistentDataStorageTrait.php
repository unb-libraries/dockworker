<?php

namespace Dockworker;

use Dockworker\FileSystemOperationsTrait;
use Dockworker\PersistentConfigurationTrait;
use Robo\Symfony\ConsoleIO;

/**
 * Provides IO methods to an in-repository data storage for the application.
 */
trait DockworkerApplicationPersistentDataStorageTrait
{
    use FileSystemOperationsTrait;
    use PersistentConfigurationTrait;

    private const APPLICATION_PERSISTENT_DATA_STORAGE_BASE_DIR = '.dockworker/data';

    /**
     * The path to the application's persistent data storage directory.
     *
     * @var string
     */
    protected string $applicationPersistentDataStorageDir;

    /**
     * Initializes the application's persistent data storage directory.
     *
     * @param string $repo_root
     *   The root of the application's git repository.
     */
    protected function initApplicationPersistentDataStorageDir(
        string $repo_root
    ): void {
        $this->applicationPersistentDataStorageDir = $this->initGetPathFromPathElements(
            [
                $repo_root,
                self::APPLICATION_PERSISTENT_DATA_STORAGE_BASE_DIR
            ]
        );
    }
    /**
     * Gets the application's persistent configuration item value, set and write it from a query if unset.
     *
     * @param string $namespace
     *   The configuration namespace to retrieve from.
     * @param string $item
     *   The item to retrieve.
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
    protected function getSetApplicationPersistentDataConfigurationItem(
        string $namespace,
        string $item,
        string $query,
        ConsoleIO $io,
        string $default = '',
        string $env_var_override_name = ''
    ): mixed {
        return $this->getSetPersistentConfigurationItem(
            $this->applicationLocalDataStorageDir,
            $namespace,
            $item,
            $query,
            $io,
            $default,
            $env_var_override_name
        );
    }

    /**
     * Gets the application's persistent configuration item value.
     *
     * @param string $namespace
     *   The configuration namespace to retrieve from.
     * @param string $item
     *   The item to retrieve.
     *
     * @return mixed
     *   The value of the configuration item.
     */
    protected function getApplicationPersistentDataConfigurationItem(
        string $namespace,
        string $item,
    ): mixed {
        return $this->getPersistentConfigurationItem(
            $this->applicationLocalDataStorageDir,
            $namespace,
            $item
        );
    }

    /**
     * Sets the application's persistent configuration item value.
     *
     * @param string $namespace
     *   The configuration namespace to set in.
     * @param string $item
     *   The item to set.
     * @param mixed $value
     *   The value to set.
     */
    protected function setApplicationPersistentDataConfigurationItem(
        string $namespace,
        string $item,
        mixed $value
    ): void {
        $this->setPersistentConfigurationItem(
            $this->applicationLocalDataStorageDir,
            $namespace,
            $item,
            $value
        );
    }
}
