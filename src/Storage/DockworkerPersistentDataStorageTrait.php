<?php

namespace Dockworker\Storage;

use Dockworker\System\FileSystemOperationsTrait;

/**
 * Provides R/W methods to Dockworker's persistent data storage.
 *
 * @internal This trait is intended only to be used by Dockworker commands. It
 * references user properties which are not in its own scope.
 */
trait DockworkerPersistentDataStorageTrait
{
    use FileSystemOperationsTrait;
    use PersistentConfigurationTrait;

    /**
     * The path to the dockworker persistent data storage base directory.
     *
     * @var string
     */
    protected string $dockworkerPersistentDataStorageBaseDir = '.config/dockworker';

    /**
     * The path to the dockworker persistent data storage directory.
     *
     * @var string
     */
    protected string $dockworkerPersistentDataStorageDir;

    /**
     * Provides a pre-init hook that assigns local PC data storage paths.
     *
     * @hook pre-init
     */
    public function preInitDockworkerPersistentDataStorageDir(): void
    {
        $this->initDockworkerPersistentDataStorageDir(
            $this->userHomeDir
        );
    }

    /**
     * Initializes the dockworker persistent data storage directory.
     *
     * @param string $user_home_dir
     *   The home directory of the current user.
     */
    protected function initDockworkerPersistentDataStorageDir(
        string $user_home_dir
    ): void {
        $this->dockworkerPersistentDataStorageDir = $this->initGetPathFromPathElements(
            [
                $user_home_dir,
                $this->dockworkerPersistentDataStorageBaseDir,
            ]
        );
    }

    /**
     * Gets the dockworker persistent configuration item value, set and write it from a query if unset.
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
     * @param array $reference_uris
     *   Optional. Labels and URIs to display to support describing the item.
     * @param string $env_var_override_name
     *   Optional. An OS environment variable name whose value overrides configuration.
     *
     * @TODO Docblock is wrong.
     * @return mixed
     *   The value of the configuration item.
     */
    protected function getSetDockworkerPersistentDataConfigurationItem(
        string $namespace,
        string $item,
        string $query,
        string $default = '',
        string $description = '',
        array $reference_uris = [],
        string $env_var_override_name = ''
    ): mixed {
        return $this->getSetPersistentConfigurationItem(
            $this->dockworkerPersistentDataStorageDir,
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
     * Gets the dockworker persistent configuration item value.
     *
     * @param string $namespace
     *   The configuration namespace to retrieve from.
     * @param string $item
     *   The item to retrieve.
     *
     * @return mixed
     *   The value of the configuration item.
     */
    protected function getDockworkerPersistentDataConfigurationItem(
        string $namespace,
        string $item,
    ): mixed {
        return $this->getPersistentConfigurationItem(
            $this->dockworkerPersistentDataStorageDir,
            $namespace,
            $item
        );
    }

    /**
     * Sets a dockworker persistent configuration item value.
     *
     * @param string $namespace
     *   The configuration namespace to set in.
     * @param string $item
     *   The item to set.
     * @param mixed $value
     *   The value to set.
     */
    protected function setDockworkerPersistentDataConfigurationItem(
        string $namespace,
        string $item,
        mixed $value
    ): void {
        $this->setWritePersistentConfigurationItem(
            $this->dockworkerPersistentDataStorageDir,
            $namespace,
            $item,
            $value
        );
    }
}
