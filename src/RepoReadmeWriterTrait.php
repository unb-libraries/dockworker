<?php

namespace Dockworker;

/**
 * Provides methods to manipulate a repository README.md.
 */
trait RepoReadmeWriterTrait {

  /**
   * The path to the README file to write.
   *
   * @var string
   */
  protected $readMePath;

  /**
   * The contents of the README file to write.
   *
   * @var string
   */
protected $readMeContents;

  /**
   * The path to the README templates to use when writing.
   *
   * @var string
   */
  protected $readMeTemplatePaths = [];

  /**
   * The twig object to write to.
   *
   * @var string
   */
  protected $readMeTwig = '';

  /**
   * Sets the README file contents.
   *
   * @hook post-init @readmecommand
   * @throws \Dockworker\DockworkerException
   */
  public function initReadmeCommand() {
    $this->readMePath = $this->repoRoot . '/README.md';
  }

  /**
   * Writes the README file to disk.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function writeReadme() {
    try {
      file_put_contents($this->readMePath, $this->readMeContents);
    }
    catch (\Exception) {
      throw new DockworkerException('Error when writing README file.');
    };
  }

}
