<?php

namespace Dockworker;

use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Project\ProjectService;

/**
 * Trait for interacting with a Jira instance.
 */
trait JiraTrait {

  use PersistentGlobalDockworkerConfigTrait;

  /**
   * The config to use.
   *
   * @var \JiraRestApi\Configuration\ArrayConfiguration
   */
  protected $jiraConfig;

  /**
   * The jira server hostname.
   *
   * @var string
   */
  protected $jiraEndpointUri;

  /**
   * The jira server user name to authenticate with.
   *
   * @var string
   */
  protected $jiraUserName;

  /**
   * The jira server user password to authenticate with.
   *
   * @var string
   */
  protected $jiraUserPassword;

  /**
   * The jira server user password to authenticate with.
   *
   * @var object
   */
  protected $jiraService;

  /**
   * Sets up the JIRA configuration.
   *
   * @throws \Exception
   *
   * @hook pre-init @jira
   */
  public function setupJiraConfiguration() {
    $this->setJiraUri();
    $this->setJiraUser();
    $this->setJiraPass();
    $this->setJiraConfig();
    $this->setJiraService();
  }

  /**
   * Sets the JIRA hostname.
   *
   * @throws \Exception
   */
  public function setJiraUri() {
    $this->jiraEndpointUri = $this->getSetGlobalDockworkerConfigItem(
      'dockworker.jira.uri',
      "Enter the URI of the Jira endpoint",
      $this->io(),
      'https://jira.lib.unb.ca',
      'DOCKWORKER_JIRA_URI'
    );
  }

  /**
   * Sets the JIRA service object.
   *
   * @throws \Exception
   */
  public function setJiraService() {
    $this->jiraService = new ProjectService($this->jiraConfig);
  }

  /**
   * Sets the JIRA username.
   *
   * @throws \Exception
   */
  public function setJiraUser() {
    $this->jiraUserName = $this->getSetGlobalDockworkerConfigItem(
      'dockworker.jira.username',
      "Enter the Username for $this->jiraEndpointUri",
      $this->io(),
      '',
      'DOCKWORKER_JIRA_USER_NAME'
    );
  }

  /**
   * Sets the JIRA user password.
   *
   * JIRA on-premises doesn't allow API keys to generate, so we need to
   * enter a password at run-time.
   *
   * @throws \Exception
   */
  public function setJiraPass() {
    $this->jiraUserPassword = $this->getSetGlobalDockworkerConfigItem(
      'dockworker.jira.password',
      "Enter $this->jiraUserName's JIRA password for $this->jiraEndpointUri",
      $this->io(),
      '',
      'DOCKWORKER_JIRA_USER_PASSWORD'
    );
  }

  /**
   * Sets up the config array.
   *
   * @throws \Exception
   */
  public function setJiraConfig() {
    $this->jiraConfig = new ArrayConfiguration(
      [
        'jiraHost' => $this->jiraEndpointUri,
        'jiraUser' => $this->jiraUserName,
        'jiraPassword' => $this->jiraUserPassword,
      ]
    );
  }

}
