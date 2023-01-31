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
 * Provides methods to store repo-specific data.
 */
trait PersistentLocalDockworkerDataTrait {

  /**
   * The local data path.
   *
   * @var string
   */
  protected string $curLocalDockworkerConfigPath;

  /**
   * The local persistent dockworker configuration.
   *
   * @var \Consolidation\Config\ConfigInterface
   */
  protected $curLocalDockworkerConfiguration;

  /**
   * Sets the local dockworker configuration.
   */
  protected function setLocalDockworkerConfig() : void {
    $loader = new YamlConfigLoader();
    $processor = new ConfigProcessor();
    $processor->extend($loader->load($this->curLocalDockworkerConfigPath));
    $config = $processor->export();
    if (!empty($config)) {
      $this->curLocalDockworkerConfiguration = new Config($config);
    }
  }

  /**
   * Initializes and sets up the local dockworker configuration.
   *
   * @param string $type
   *   The type of configuration to set up.
   * @param bool $force_reload
   *   If TRUE, config is always loaded from disk regardless of the state.
   */
  protected function initLocalDockworkerConfig(
    string $type,
    bool $force_reload = FALSE
  ) : void {
    $this->curLocalDockworkerConfigPath = implode(
      '/',
      [
        $this->dockworkerApplicationDataDir,
        "$type.yml",
      ]
    );
    if (!is_file($this->curLocalDockworkerConfigPath)) {
      file_put_contents(
        $this->curLocalDockworkerConfigPath,
        'dockworker:'
      );
      chmod($this->curLocalDockworkerConfigPath, 0600);
    }
    if ($force_reload || empty($this->curLocalDockworkerConfiguration)) {
      $this->setLocalDockworkerConfig();
    }
  }

  /**
   * Retrieves a local dockworker configuration item.
   *
   * @param string $type
   *   The type of configuration to retrieve.
   * @param string $namespace
   *   The configuration namespace to retrieve.
   *
   * @return string
   *   The value of the item.
   */
  protected function getLocalDockworkerConfigItem(string $type, string $namespace) : string {
    $this->initLocalDockworkerConfig($type);
    return (string) $this->curLocalDockworkerConfiguration->get($namespace);
  }

  /**
   * Sets a local dockworker configuration item.
   *
   * @param string $type
   *   The type of configuration to set.
   * @param string $namespace
   *   The configuration namespace to set.
   * @param string $value
   *   The value to set.
   */
  protected function setLocalDockworkerConfigItem(
    string $type,
    string $namespace,
    string $value
  ) : void {
    $this->initLocalDockworkerConfig($type);
    $this->curLocalDockworkerConfiguration->set($namespace, $value);
  }

  /**
   * Sets and writes a local dockworker configuration item to disk.
   *
   * @param string $type
   *   The type of configuration to set.
   * @param string $namespace
   *   The configuration namespace to set.
   * @param string $value
   *   The value to set.
   */
  protected function setWriteLocalDockworkerConfigItem(
    string $type,
    string $namespace,
    string $value
  ) : void {
    $this->setLocalDockworkerConfigItem($type, $namespace, $value);
    $this->witeLocalDockworkerConfig();
  }

  /**
   * Writes a local dockworker configuration item to disk.
   */
  protected function witeLocalDockworkerConfig() : void {
    $yaml = Yaml::dump(
      $this->curLocalDockworkerConfiguration->export(),
      5
    );
    file_put_contents(
      $this->curLocalDockworkerConfigPath,
      $yaml
    );
  }

  /**
   * Gets a local configuration item, setting it based on a query if not exists.
   *
   * @param string $type
   *   The type of configuration to query and set.
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
  protected function getsetLocalDockworkerConfigItem(
    string $type,
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
    $cur_value = $this->getLocalDockworkerConfigItem($type, $namespace);
    if (empty($cur_value)) {
      $cur_value = $io->ask($question, $default);
      $this->setWriteLocalDockworkerConfigItem($namespace, $cur_value);
    }
    return $cur_value;
  }

}
