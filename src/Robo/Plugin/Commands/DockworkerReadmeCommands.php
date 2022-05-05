<?php

namespace Dockworker\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Dockworker\GitContributorsListTrait;
use Dockworker\RepoReadmeWriterTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;

/**
 * Defines a class to write a standardized README to a repository.
 */
class DockworkerReadmeCommands extends DockworkerCommands implements CustomEventAwareInterface {

  use CustomEventAwareTrait;
  use GitContributorsListTrait;
  use RepoReadmeWriterTrait;

  /**
   * Writes a standardized README.md file for this application to this repository.
   *
   * @command readme:update
   * @aliases ru
   *
   * @usage readme:update
   *
   * @github
   * @readmecommand
   */
  public function setApplicationReadme() {
    // First, prioritize local repo templates.
    $repo_readme_overrides = $this->repoRoot . '/.dockworker/documentation/README';
    if (file_exists($repo_readme_overrides)) {
      array_unshift($this->readMeTemplatePaths, $this->repoRoot . '/.dockworker/documentation/README');
    }

    // Then, get any templates from dockworker extensions.
    $handlers = $this->getCustomEventHandlers('populate-readme-templates');
    foreach($handlers as $handler) {
      $this->readMeTemplatePaths = array_merge($this->readMeTemplatePaths, $handler());
    }

    // At last, provide our defaults.
    $this->readMeTemplatePaths[] = $this->repoRoot . '/vendor/unb-libraries/dockworker/data/README';

    // Render the file.
    $loader = new \Twig\Loader\FilesystemLoader($this->readMeTemplatePaths);
    $this->readMeTwig = new \Twig\Environment($loader);
    $template = $this->readMeTwig->load('README.md.twig');
    $this->writeReadme(
      $template->render(
        [
          'instance_name' => $this->instanceName,
          'contributors' => $this->getGitHubContributors($this->gitHubOwner, $this->gitHubRepo),
          'screenshot_uri' => $this->getReadmeScreenshotUri()
        ]
      )
    );
    $this->say('The updated README.md contents have been written.');
  }

  /**
   * Generate the README screenshot image URI.
   */
  protected function getReadmeScreenshotUri() {
    if (file_exists($this->repoRoot . '/.dockworker/screenshot.png')) {
      return "https://github.com/{$this->gitHubOwner}/{$this->gitHubRepo}/raw/prod/.dockworker/screenshot.png";
    }
    return NULL;
  }

}
