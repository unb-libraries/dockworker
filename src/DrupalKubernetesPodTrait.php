<?php

namespace Dockworker;

use Dockworker\KubernetesPodTrait;

/**
 * Defines trait for interacting with Drupal pods in k8s.
 */
trait DrupalKubernetesPodTrait {

  use KubernetesPodTrait;

  /**
   * Execute a drush command in a remote Kubernetes pod.
   *
   * @param string $pod
   *   The pod name to check.
   * @param $namespace
   *   The namespace to target the pod in.
   * @param $command
   *   The drush command to execute.
   *
   * @return mixed
   * @throws \Exception
   */
  protected function kubernetesPodDrushCommand($pod, $namespace, $command) {
    return $this->kubernetesPodExecCommand(
      $pod,
      $namespace,
      sprintf('drush --yes --root=/app/html %s',
        $command
      )
    );
  }

}
