<?php

namespace UnbLibraries\DockWorker\Robo;

use UnbLibraries\DockWorker\Robo\DockWorkerApplicationCommand;

/**
 * Defines commands in the DrupalUpgrade9AuditCommand namespace.
 */
class DrupalUpgrade9AuditCommand extends DockWorkerApplicationCommand {

  /**
   * Audit all code in /custom in anticipation of a Drupal 9 upgrade.
   *
   * @command drupal:audit:9-upgrade
   */
  public function auditCode() {
    return $this->taskDockerRun('jacobsanford/drupal-check:latest')
      ->volume($this->repoRoot . '/custom', '/drupal/web/modules/custom')
      ->exec("/drupal/web/modules/custom")
      ->run();
  }

}
