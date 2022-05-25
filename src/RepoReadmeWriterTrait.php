<?php

namespace Dockworker;

use Dockworker\DockworkerException;

/**
 * Provides methods to manipulate a repository README.md.
 */
trait RepoReadmeWriterTrait {

  protected $readMePath;
  protected $readMeContents;
  protected $readMeTemplatePaths = [];
  protected $readMeTwig = '';

  /**
   * Sets the readme file contents.
   *
   * @hook post-init @readmecommand
   * @throws \Dockworker\DockworkerException
   */
  public function initReadmeCommand() {
    $this->readMePath = $this->repoRoot . '/README.md';
  }

  /**
   * Write the readme file.
   *
   * @hook post-init @readmecommand
   * @throws \Dockworker\DockworkerException
   */
  protected function writeReadme($contents) {
    try {
      file_put_contents($this->readMePath, $contents);
    }
    catch (\Exception) {
      throw new DockworkerException('Error when writing README file.');
    };
  }

}
