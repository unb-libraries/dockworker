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
    $github_contributors = $this->getGitHubContributors($this->gitHubOwner, $this->gitHubRepo);
    $screenshot_uri = $this->getReadmeScreenshotUri();

    $loader = new \Twig\Loader\FilesystemLoader();
    $repo_readme_overrides = $this->repoRoot . '/.dockworker/documentation/README';
    if (file_exists($repo_readme_overrides)) {
      $loader->addPath($repo_readme_overrides);
    }

    $dockworker_readme_path = $this->repoRoot . '/vendor/unb-libraries/dockworker/data/README';
    $this->readMeTemplatePaths[] = $dockworker_readme_path;

    foreach ($this->readMeTemplatePaths as $template_path) {
      $loader->addPath($template_path, 'base');
      $loader->addPath($template_path);
    }

    $this->readMeTwig = new \Twig\Environment($loader);
    $template = $this->readMeTwig->load('README.md.twig');
    $this->writeReadme(
      $template->render(
        [
          'instance_name' => $this->instanceName,
          'contributors' => $github_contributors,
          'screenshot_uri' => $screenshot_uri
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
