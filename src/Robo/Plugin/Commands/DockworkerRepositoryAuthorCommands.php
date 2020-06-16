<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerException;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;
use Dockworker\GitContributorsListTrait;

/**
 * Defines a base class for all Dockworker Robo commands.
 */
class DockworkerRepositoryAuthorCommands extends DockworkerCommands {

  use GitContributorsListTrait;

  const CONTRIBUTOR_LIST_DELIMITER = '[//]: contributors';
  /**
   * Updates the application's contributors list in README.md.
   *
   * @command dockworker:contributors:update
   * @aliases update-contributors
   *
   * @usage dockworker:contributors:update
   *
   * @github
   */
  public function setApplicationContributors() {
    $readme_filename = $this->repoRoot. '/README.md';

    if (!file_exists($readme_filename)) {
      throw new DockworkerException('A README.md file was not found in the repository root!');
    }

    $readme_contents = file_get_contents ($readme_filename);
    $number_delimiters = substr_count($readme_contents, self::CONTRIBUTOR_LIST_DELIMITER);
    if ($number_delimiters != '2') {
      throw new DockworkerException(
        sprintf(
          'The README.md file does not have the "%s" delimiters properly defined.',
          self::CONTRIBUTOR_LIST_DELIMITER
        )
      );
    }

    $new_contributor_html = $this->getContributorHTMLList($this->gitHubOwner, $this->gitHubRepo);
    $new_contributor_delimiter = self::CONTRIBUTOR_LIST_DELIMITER;
    $new_contributor_block = <<<EOT
$new_contributor_delimiter

$new_contributor_html
$new_contributor_delimiter
EOT;

    $replace_pattern = '/' . preg_quote(self::CONTRIBUTOR_LIST_DELIMITER, '/') . '.*' . preg_quote(self::CONTRIBUTOR_LIST_DELIMITER, '/') . '/s';
    $new_readme_contents = preg_replace($replace_pattern, $new_contributor_block, $readme_contents);

    if ("$new_readme_contents" == "$readme_contents") {
      $this->say('The contributors list in README.md is already up-to-date. Exiting.');
    }
    else {
      if ($this->confirm('New contributors not credited in README.md detected. Write and updated list to README.md?')) {
        file_put_contents($readme_filename, $new_readme_contents);
        $this->say('The contributors list in README.md has been updated. You will still need to commit the changes!');
      }
    }
  }

}
