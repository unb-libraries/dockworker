<?php

namespace Dockworker;

use Dockworker\FileSystemOperationsTrait;
use Dockworker\PersistentConfigurationTrait;
use Robo\Symfony\ConsoleIO;

/**
 * Provides IO methods to a local PC data storage for the application.
 */
trait DockworkerApplicationLocalDataStorageTrait
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
    protected function getSetApplicationLocalDataConfigurationItem(
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
