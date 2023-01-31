<?php

namespace Dockworker;

use Consolidation\Config\ConfigInterface;
use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;
use Robo\Config\Config;
use Robo\Robo;
use Robo\Symfony\ConsoleIO;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides methods to format Symfony console output.
 */
trait PersistentGlobalDockworkerConfigTrait {

  /**
   * The global persistent dockworker configuration filepath.
   *
   * @var string
   */
  protected string $globalDockworkerConfigurationFilePath;

  /**
   * The global persistent dockworker configuration.
   *
   * @var \Consolidation\Config\ConfigInterface
   */
  protected ConfigInterface $globalDockworkerConfiguration;

  /**
   * Sets the global persistent dockworker configuration.
   */
  protected function setGlobalDockworkerConfig() : void {
    $loader = new YamlConfigLoader();
    $processor = new ConfigProcessor();
    $processor->extend($loader->load($this->globalDockworkerConfigurationFilePath));
    $config = $processor->export();
    if (!empty($config)) {
      $this->globalDockworkerConfiguration = new Config($config);
    }
  }

  /**
   * Initializes and sets up the global dockworker configuration.
   *
   * @param bool $force_reload
   *   If TRUE, config is always loaded from disk regardless of the state.
   */
  protected function initGlobalDockworkerConfig(bool $force_reload = FALSE) : void {
    $this->globalDockworkerConfigurationFilePath = implode(
      '/',
      [
        $this->userHomeDir,
        self::DOCKWORKER_GLOBAL_DATA_BASE_DIR,
        'dockworker.yml',
      ]
    );
    if (!is_file($this->globalDockworkerConfigurationFilePath)) {
      file_put_contents(
        $this->globalDockworkerConfigurationFilePath,
        'dockworker:'
      );
      chmod($this->globalDockworkerConfigurationFilePath, 0600);
    }
    if ($force_reload || empty($this->globalDockworkerConfiguration)) {
      $this->setGlobalDockworkerConfig();
    }
  }

  /**
   * Retrieves a global dockworker configuration item.
   *
   * @param string $namespace
   *   The configuration namespace to retrieve.
   *
   * @return string
   *   The value of the item.
   */
  protected function getGlobalDockworkerConfigItem(string $namespace) : string {
    $this->initGlobalDockworkerConfig();
    return (string) $this->globalDockworkerConfiguration->get($namespace);
  }

  /**
   * Sets a global dockworker configuration item.
   *
   * @param string $namespace
   *   The configuration namespace to set.
   * @param string $value
   *   The value to set.
   */
  protected function setGlobalDockworkerConfigItem(
    string $namespace,
    string $value
  ) : void {
    $this->initGlobalDockworkerConfig();
    $this->globalDockworkerConfiguration->set($namespace, $value);
  }

  /**
   * Sets and writes a global dockworker configuration item to disk.
   *
   * @param string $namespace
   *   The configuration namespace to set.
   * @param string $value
   *   The value to set.
   */
  protected function setWriteGlobalDockworkerConfigItem($namespace, $value) : void {
    $this->setGlobalDockworkerConfigItem($namespace, $value);
    $yaml = Yaml::dump(
      $this->globalDockworkerConfiguration->export(),
      5
    );
    file_put_contents(
      $this->globalDockworkerConfigurationFilePath,
      $yaml
    );
  }

  /**
   * Gets a global configuration item, setting it based on a query if not exists.
   *
   * @param string $namespace
   *   The configuration namespace to retrieve from.
   * @param string $question
   *   The prompt to display if the configuration item is unset.
   * @param \Robo\Symfony\ConsoleIO $io
   *   The IO to use when displaying the prompts.
   * @param string $default
   *   Optional, the default value for the prompt, defaults to none.
   * @param string $env_var_override_name
   *   Optional, the environment variable name that overrides the config item.
   *
   * @return string
   *   The value of the item.
   */
  protected function getSetGlobalDockworkerConfigItem(
    string $namespace,
    string $question,
    ConsoleIO $io,
    string $default = '',
    string $env_var_override_name = ''
  ) : string {
    if (!empty($env_var_override_name)) {
      $env_value = getenv($env_var_override_name);
      if (!empty($env_value)) {
        return $env_value;
      }
    }
    $cur_value = $this->getGlobalDockworkerConfigItem($namespace);
    if (empty($cur_value)) {
      $cur_value = $io->ask($question, $default);
      $this->setWriteGlobalDockworkerConfigItem($namespace, $cur_value);
    }
    return $cur_value;
  }

}
