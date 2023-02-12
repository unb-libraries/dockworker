<?php

namespace Dockworker\Storage;

use Dockworker\System\FileSystemOperationsTrait;

/**
 * Provides IO methods to an in-repository data storage for the application.
 */
trait ApplicationPersistentDataStorageTrait
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
     * Provides a pre-init hook that assigns persistent data storage paths.
     *
     * @hook pre-init
     */
    public function preInitApplicationPersistentDataStorageDir(): void
    {
        $this->initApplicationPersistentDataStorageDir(
            $this->applicationRoot
        );
    }

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
     * @param string $default
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
    protected function getSetApplicationPersistentDataConfigurationItem(
        string $namespace,
        string $item,
        string $query,
        string $default = '',
        string $description = '',
        array $reference_uris = [],
        string $env_var_override_name = ''
    ): mixed {
        return $this->getSetPersistentConfigurationItem(
            $this->applicationPersistentDataStorageDir,
            $namespace,
            $item,
            $query,
            $default,
            $description,
            $reference_uris,
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
            $this->applicationPersistentDataStorageDir,
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
            $this->applicationPersistentDataStorageDir,
            $namespace,
            $item,
            $value
        );
    }
}
