<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Dockworker\KubectlTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides methods to execute commands inside Kubernetes Pods.
 */
trait KubernetesDeploymentFileTrait {

  /**
   * Gets a kubernetes deployment namespace from the YAML file.
   *
   * @param string $file
   *   The path to the deployment file.
   *
   * @return string
   *   The value of the namespace element. NULL if does not exist.
   *
   * @throws \Exception
   */
  public static function getKubernetesDeploymentFileNamespace($file) {
    $deployment = Yaml::parseFile('/path/to/file.yaml');
    if (!empty($deployment['metadata']['namespace'])) {
      return $deployment['metadata']['namespace'];
    }
    return NULL;
  }

}
