<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\GitContributorsListTrait;
use Dockworker\RepoReadmeWriterTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;

/**
 * Defines a class to write a standardized README to a repository.
 */
class DockworkerReadmeCommands extends DockworkerCommands {

  use GitContributorsListTrait;
  use RepoReadmeWriterTrait;

  /**
   * Updates the application's README.md.
   *
   * @command dockworker:readme:update
   * @aliases update-readme
   *
   * @usage dockworker:readme:update
   *
   * @github
   * @readmecommand
   */
  public function setApplicationReadme() {
    $this->readMeTemplatePaths[] = $this->repoRoot . '/vendor/unb-libraries/dockworker/data/README';
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
