<?php

namespace Dockworker\Core;

use Consolidation\Config\ConfigInterface;
use Dockworker\DockworkerException;

/**
 * Provides methods to access configuration elements.
 */
trait RoboConfigTrait
{
  /**
   * Sets a property from a config element.
   *
   * @param \Consolidation\Config\ConfigInterface $config
   *   The configuration object.
   * @param string $property
   *  The property to set.
   * @param string $config_key
   *  The namespace to obtain the configuration value from.
   *
   * @throws \Dockworker\DockworkerException
   */
    protected function setPropertyFromConfigKey(
        ConfigInterface $config,
        string $property,
        string $config_key
    ): void {
        $config_value = $config->get($config_key);
        if ($config_value == null) {
            throw new DockworkerException(
                sprintf(
                    self::ERROR_CONFIG_ELEMENT_UNSET,
                    $config_key,
                    $this->configFile
                )
            );
        }
        $this->$property = $config_value;
    }

    /**
    * Gets a configuration item from the Dockworker configuration.
    *
    * @param \Consolidation\Config\ConfigInterface $config
    *   The configuration object.
    * @param string $key
    *   The configuration key to retrieve.
    * @param mixed $default_value
    *   The default value to return if the configuration key is not set.
    *
    * @return mixed
    *   The configuration value.
    */
    protected function getConfigItem(
        ConfigInterface $config,
        string $key,
        mixed $default_value = null
    ): mixed {
        return $config->get($key, $default_value);
    }
}
