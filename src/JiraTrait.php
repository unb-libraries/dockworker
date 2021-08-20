<?php

namespace Dockworker;

use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Project\ProjectService;
use Robo\Robo;

/**
 * Trait for interacting with a Jira instance.
 */
trait JiraTrait {

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
    $this->setDisplayEnvVarInfo();
    $this->setJiraUri();
    $this->setJiraUser();
    $this->setJiraPass();
    $this->setJiraConfig();
    $this->setJiraService();
  }

  /**
   * Informs the user of environment variables if not set.
   *
   * @throws \Exception
   */
  public function setDisplayEnvVarInfo() {
    if (empty(getenv('DOCKWORKER_JIRA_URI'))) {
      $this->say('Jira configuration is user-specific can be stored as environment variables. To avoid these prompts, set the following environment variables in your shell:');
      $this->say('DOCKWORKER_JIRA_URI, DOCKWORKER_JIRA_USER_NAME, DOCKWORKER_JIRA_USER_PASSWORD');
    }
  }

  /**
   * Sets the JIRA hostname.
   *
   * @throws \Exception
   */
  public function setJiraUri() {
    $this->jiraEndpointUri = getenv('DOCKWORKER_JIRA_URI');
    if (empty($this->jiraEndpointUri)) {
      $this->jiraEndpointUri = $this->askDefault(
        "Enter the URI of the Jira endpoint :",
        'https://jira.lib.unb.ca'
      );
    }
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
    $this->jiraUserName = getenv('DOCKWORKER_JIRA_USER_NAME');
    if (empty($this->jiraUserName)) {
      $this->jiraUserName = $this->ask(
        "Enter a Username for $this->jiraEndpointUri:"
      );
    }
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
    $this->jiraUserPassword = getenv('DOCKWORKER_JIRA_USER_PASSWORD');
    if (empty($this->jiraUserPassword)) {
      $this->jiraUserPassword = $this->ask(
        "Enter $this->jiraUserName's JIRA password for $this->jiraEndpointUri"
      );
    }
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
