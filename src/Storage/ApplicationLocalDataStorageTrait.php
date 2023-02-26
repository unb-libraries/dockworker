<?php

namespace Dockworker\Storage;

use Dockworker\System\FileSystemOperationsTrait;

/**
 * Provides R/W methods to local PC data storage for the application.
 *
 * @INTERNAL This trait is intended only to be used by Dockworker commands. It
 * references user properties which are not in its own scope.
 */
trait ApplicationLocalDataStorageTrait
{
    use FileSystemOperationsTrait;
    use PersistentConfigurationTrait;

    /**
     * The path to the local PC data storage base directory.
     *
     * @var string
     */
    protected string $applicationLocalDataStorageBaseDir = '.config/dockworker';

    /**
     * The path to the application's local PC data storage directory.
     *
     * @var string
     */
    protected string $applicationLocalDataStorageDir;

    /**
     * Provides a pre-init hook that assigns local PC data storage paths.
     *
     * @hook pre-init
     */
    public function preInitApplicationLocalDataStorageDir(): void
    {
        $this->initApplicationLocalDataStorageDir(
            $this->userHomeDir,
            $this->applicationName
        );
    }

    /**
     * Initializes the application's local PC data storage directory.
     *
     * @param string $user_home_dir
     *   The home directory of the current user.
     * @param string $application_id
     *   The application identifier.
     *
     * @return void
     */
    protected function initApplicationLocalDataStorageDir(
        string $user_home_dir,
        string $application_id
    ): void {
        $this->applicationLocalDataStorageDir = $this->initGetPathFromPathElements(
            [
                $user_home_dir,
                $this->applicationLocalDataStorageBaseDir,
                $application_id,
            ]
        );
    }

    /**
     * Gets the application's local PC data configuration item value, set and write it from a query if unset.
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
    protected function getSetApplicationLocalDataConfigurationItem(
        string $namespace,
        string $item,
        string $query,
        string $default = '',
        string $description = '',
        array $reference_uris = [],
        string $env_var_override_name = ''
    ): mixed {
        return $this->getSetPersistentConfigurationItem(
            $this->applicationLocalDataStorageDir,
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
     * Gets the application's local PC data configuration item value.
     *
     * @param string $namespace
     *   The configuration namespace to retrieve from.
     * @param string $item
     *   The item to retrieve.
     *
     * @return mixed
     *   The value of the configuration item.
     */
    protected function getApplicationLocalDataConfigurationItem(
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
     * Sets the application's local PC data configuration item value.
     *
     * @param string $namespace
     *   The configuration namespace to set in.
     * @param string $item
     *   The item to set.
     * @param mixed $value
     *   The value to set.
     */
    protected function setApplicationLocalDataConfigurationItem(
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
