<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockworkerException;
use Dockworker\Robo\Plugin\Commands\DockworkerCommands;

/**
 * Defines a class to update the README of a repository with current badges.
 */
class DockworkerReadmeBadgeCommands extends DockworkerCommands {

  const BADGE_LIST_DELIMITER = '[//]: badges';

  /**
   * Updates the application's badge list in README.md.
   *
   * @command dockworker:badges:update
   * @aliases update-badges
   *
   * @usage dockworker:badges:update
   */
  public function setApplicationBadges() {
    $readme_filename = $this->repoRoot. '/README.md';

    if (!file_exists($readme_filename)) {
      throw new DockworkerException('A README.md file was not found in the repository root!');
    }

    $readme_contents = file_get_contents ($readme_filename);
    $number_delimiters = substr_count($readme_contents, self::BADGE_LIST_DELIMITER);
    if ($number_delimiters != '2') {
      throw new DockworkerException(
        sprintf(
          'The README.md file does not have the "%s" delimiters properly defined.',
          self::BADGE_LIST_DELIMITER
        )
      );
    }

    $instance_name = $this->instanceName;
    $delimiter = self::BADGE_LIST_DELIMITER;

    $new_badge_block = <<<EOT
$delimiter
[![Build Status](https://travis-ci.com/unb-libraries/$instance_name.svg?branch=prod)](https://travis-ci.com/unb-libraries/$instance_name)
[![GitHub license](https://img.shields.io/github/license/unb-libraries/$instance_name)](https://github.com/unb-libraries/$instance_name/blob/prod/LICENSE)
![GitHub repo size](https://img.shields.io/github/repo-size/unb-libraries/$instance_name?label=lean%20repo%20size)
[![Docker image size](https://img.shields.io/docker/image-size/unblibraries/$instance_name/prod?label=docker%20image%20size)](https://hub.docker.com/repository/docker/unblibraries/$instance_name)

$delimiter
EOT;

    $replace_pattern = '/' . preg_quote(self::BADGE_LIST_DELIMITER, '/') . '.*' . preg_quote(self::BADGE_LIST_DELIMITER, '/') . '/s';
    $new_readme_contents = preg_replace($replace_pattern, $new_badge_block, $readme_contents);

    if ("$new_readme_contents" == "$readme_contents") {
      $this->say('The badge list in README.md is already up-to-date. Exiting.');
    }
    else {
      if ($this->confirm('Up-to-date badge list not found. Write and updated list to README.md?')) {
        file_put_contents($readme_filename, $new_readme_contents);
        $this->say('The badge list in README.md has been updated. You will still need to commit the changes!');
      }
    }
  }

}
