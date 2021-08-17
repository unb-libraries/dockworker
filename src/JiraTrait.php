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
  protected $jiraHostName;

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
   * Sets the JIRA host from config.
   *
   * @throws \Exception
   *
   * @hook pre-init
   */
  public function setJiraHost() {
    $this->jiraHostName = getenv('DOCKWORKER_JIRA_HOSTNAME');
    if (empty($this->jiraHostName)) {
      throw new \Exception(sprintf('The DOCKWORKER_JIRA_HOSTNAME environment variable was not found.'));
    }
  }

  /**
   * Sets the JIRA service.
   *
   * @throws \Exception
   *
   * @hook post-init
   */
  public function setJiraService() {
    $this->jiraService = new ProjectService($this->jiraConfig);
  }

  /**
   * Sets the JIRA user from config.
   *
   * @throws \Exception
   *
   * @hook pre-init
   */
  public function setJiraUser() {
    $this->jiraUserName = getenv('DOCKWORKER_JIRA_USER_NAME');
    if (empty($this->jiraUserName)) {
      throw new \Exception(sprintf('The DOCKWORKER_JIRA_USER_NAME environment variable was not found.'));
    }
  }

  /**
   * Sets the JIRA pass.
   *
   * JIRA on-premises doesn't allow API keys to generate, so we need to password at run-time.
   *
   * @throws \Exception
   *
   * @hook pre-init
   */
  public function setJiraPass() {
    $this->jiraUserPassword = getenv('DOCKWORKER_JIRA_USER_PASSWORD');
    if (empty($this->jiraUserPassword)) {
      $this->jiraUserPassword = $this->ask(
        "Enter $this->jiraUserName's JIRA password for $this->jiraHostName"
      );
    }
  }

  /**
   * Sets up the config array.
   *
   * @throws \Exception
   *
   * @hook init
   */
  public function setJiraConfig() {
    $this->jiraConfig = new ArrayConfiguration(
      [
        'jiraHost' => $this->jiraHostName,
        'jiraUser' => $this->jiraUserName,
        'jiraPassword' => $this->jiraUserPassword,
      ]
    );
  }

}
