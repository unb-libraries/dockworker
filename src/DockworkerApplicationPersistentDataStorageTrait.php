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

    /**
     * The path to the persistent data storage base directory for applications.
     *
     * @var string
     */
    protected string $applicationPersistentDataStorageBaseDir = '.dockworker/data';

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
                $this->applicationPersistentDataStorageBaseDir,
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
        string $description = '',
        string $info_link = '',
        string $env_var_override_name = ''
    ): mixed {
        return $this->getSetPersistentConfigurationItem(
            $this->applicationLocalDataStorageDir,
            $namespace,
            $item,
            $query,
            $io,
            $default,
            $description,
            $info_link,
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
        $this->setWritePersistentConfigurationItem(
            $this->applicationLocalDataStorageDir,
            $namespace,
            $item,
            $value
        );
    }
}
