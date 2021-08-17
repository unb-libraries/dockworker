<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\JiraTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;
use JiraRestApi\Issue\IssueField;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;

/**
 * Class for MultipleProjectCreateTicketCommand Robo commands.
 */
class DockworkerJiraCommands extends DockworkerCommands {

  use JiraTrait;

  /**
   * The current Jira project.
   *
   * @var object
   */
  protected $jiraProject;

  /**
   * Creates a JIRA issue for this instance.
   *
   * @param string $summary
   *   The issue summary.
   * @param string $description
   *   The issue description.
   * @param string $type
   *   The type of issue. Optional, defaults to 'Bug'.
   *
   * @option bool yes
   *   Assume a 'yes' answer for all prompts.
   *
   * @throws \Exception
   *
   * @command jira:issue:create
   * @aliases jiraissue
   * @usage jira:issue:create 'Spark widget fieldset does not indicate its required fields'
   */
  public function createMultipleJiraTicket($summary, $description = '', $type = 'Bug', $options = ['yes' => FALSE]) {
    $this->setJiraProject();
    $issueField = new IssueField();
    $issueField->setProjectId($this->jiraProject->id)
      ->setSummary($issue_summary)
      ->setIssueType($type)
      ->setDescription($description);
    $issueService = new IssueService($this->jiraConfig);
    $this->say("Creating issue for {$this->instanceName}...");
    $issueService->create($issueField);
  }

  /**
   * Sets the current JIRA Project.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function setJiraProject() {
    try {
      $project_prefix = $this->getProjectPrefix();
      $project = $this->jiraService->get($slug);
    }
    catch (JiraException $e) {
      print("Error Setting Project ID: " . $e->getMessage());
    }
  }

}
