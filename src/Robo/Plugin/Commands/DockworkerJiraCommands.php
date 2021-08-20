<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\JiraTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;
use JiraRestApi\Issue\IssueField;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;

/**
 * Class for DockworkerJiraCommands Robo commands.
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
   * The current command options.
   *
   * @var array
   */
  protected $options;

  /**
   * Creates a JIRA issue for this instance.
   *
   * @param array $options
   *   An array of CLI options to pass to the command.
   *
   * @option string $summary
   *   The issue summary (title).
   * @option string $description
   *   The issue description.
   * @option string $type
   *   The type of issue.
   * @option bool yes
   *   Assume a 'yes' answer for all prompts.
   *
   * @throws \Exception
   *
   * @command jira:issue:create
   * @aliases jiraticket
   * @usage jira:issue:create 'Spark widget fieldset does not indicate its required fields'
   *
   * @jira
   */
  public function createJiraIssue($options = ['summary' => '', 'description' => '', 'type' => '', 'yes' => FALSE]) {
    $this->options = $options;
    $this->io()->title("Creating Jira Issue For {$this->getProjectPrefix()}");
    $this->setIssueSummary();
    $this->setIssueDescription();
    $this->setIssueType();
    if (!empty($this->options['summary'])) {
      $this->setJiraProject();
      $issueField = new IssueField();
      $issueField->setProjectId($this->jiraProject->id)
        ->setSummary($this->options['summary'])
        ->setIssueType($this->options['type'])
        ->setDescription($this->options['description']);
      $issueService = new IssueService($this->jiraConfig);
      $this->say("Creating issue for {$this->instanceName}...");
      $issueService->create($issueField);
    }
    else {
      $this->say('Creating a JIRA issue requires an issue title.');
    }
  }

  protected function setIssueSummary() {
    if (empty($this->options['summary'])) {
      $this->options['summary'] = $this->ask('Issue Title: ');
    }
  }

  protected function setIssueDescription() {
    if (empty($this->options['description'])) {
      $this->options['description'] = $this->ask('Issue Description (Enter for None): ');
    }
  }

  protected function setIssueType() {
    if (empty($this->options['type'])) {
      $this->options['type'] = $this->askDefault('Issue Type (Bug, Task, Story): ', 'Bug');
    }
  }

  /**
   * Sets the current JIRA Project.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function setJiraProject() {
    try {
      $project_prefix = $this->getProjectPrefix();
      $this->jiraProject = $this->jiraService->get($project_prefix);
    }
    catch (JiraException $e) {
      print("Error Setting Project ID: " . $e->getMessage());
    }
  }

}
