<?php

namespace Dockworker\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Dockworker\GitContributorsListTrait;
use Dockworker\RepoReadmeWriterTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerBaseCommands;
use Twig\TemplateWrapper;

/**
 * Defines a class to write a standardized README to a repository.
 */
class DockworkerReadmeCommands extends DockworkerBaseCommands implements CustomEventAwareInterface {

  use CustomEventAwareTrait;
  use GitContributorsListTrait;
  use RepoReadmeWriterTrait;

  const DOCKWORKER_DOCUMENTATION_DIR = 'documentation';

  /**
   * The path to the project documentation.
   *
   * @var string
   */
  protected string $documentationPath;

  /**
   * The README template.
   *
   * @var \Twig\TemplateWrapper
   */
  protected TemplateWrapper $readMeTemplate;

  /**
   * Writes a standardized README.md file for this application to this repository.
   *
   * @command readme:file:write
   * @aliases ru
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
    $this->readMeTemplatePaths[] = $this->repoRoot . '/vendor/unb-libraries/dockworker-base/data/README';

    // Render the file.
    $loader = new \Twig\Loader\FilesystemLoader($this->readMeTemplatePaths);
    $readme_twig = new \Twig\Environment($loader);
    $this->readMeTemplate = $readme_twig->load('README.md.twig');
    $this->readMeContents = $this->readMeTemplate->render(
      [
        'contributors' => $this->getGitHubContributors($this->gitHubOwner, $this->gitHubRepo),
        'has_documentation' => $this->projectHasReadMeDocumentationFile(),
        'instance_name' => $this->instanceName,
        'screenshot_uri' => $this->getReadmeScreenshotUri(),
      ]
    );
    $this->writeReadme();
    $this->say('The updated README.md contents have been written.');
  }

  /**
   * Sets the project documentation path.
   */
  protected function setDocumentationPath() : void {
    $this->documentationPath = implode(
      '/',
      [
        $this->repoRoot,
        self::DOCKWORKER_DOCUMENTATION_DIR,
      ]
    );
  }

  /**
   * Determines if the project documentation README.md file exists.
   *
   * @return bool
   */
  protected function projectHasReadMeDocumentationFile() : bool {
    $this->setDocumentationPath();
    if (!file_exists(
      implode(
        '/',
        [
          $this->documentationPath,
          'README.md',
        ]
      )
    )) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Generate the README screenshot image URI.
   */
  protected function getReadmeScreenshotUri() {
    if (file_exists($this->repoRoot . '/.dockworker/screenshot.png')) {
      return "https://github.com/{$this->gitHubOwner}/{$this->gitHubRepo}/raw/prod/.dockworker/screenshot.png";
    }
    return '';
  }

}
