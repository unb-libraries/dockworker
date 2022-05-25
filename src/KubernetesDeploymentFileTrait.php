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
   *   The value of the namespace element. Empty if does not exist.
   *
   * @throws \Exception
   */
  public static function getKubernetesDeploymentFileNamespace($file) {
    $deployment = Yaml::parseFile($file);
    if (!empty($deployment['metadata']['namespace'])) {
      return $deployment['metadata']['namespace'];
    }
    return '';
  }

  /**
   * Get the deployment namespace value from the repo branch (tag).
   *
   * @param string $repo_root
   *   The path to the repository root.
   * @param string $env
   *   The environment to check the logs in.
   *
   * @return string
   *   The value of the deployment namespace.
   *
   * @throws \Exception
   */
  public static function getDeployedK8sResourceNamespace($repo_root, $env, $type = 'deployment') {
    return self::getKubernetesDeploymentFileNamespace(
      self::getKubernetesFileNameFromBranch($repo_root, $env, $type)
    );
  }

  /**
   * Get the deployment names value from the repo branch (tag).
   *
   * @param string $repo_root
   *   The path to the repository root.
   * @param string $env
   *   The environment to check the logs in.
   *
   * @return string
   *   The value of the deployment name.
   *
   * @throws \Exception
   */
  public static function getDeployedK8sResourceName($repo_root, $env, $type = 'deployment') {
    return self::getKubernetesDeploymentFileDeploymentName(
      self::getKubernetesFileNameFromBranch($repo_root, $env, $type)
    );
  }

  /**
   * Get a path to the branch's k8s deployment file.
   *
   * @param string $repo_root
   *   The path to the repository root.
   * @param string $env
   *   The environment (branch) to check the logs in.
   *
   * @return string
   *   The full path to the k8s deployment file.
   *
   * @throws \Exception
   */
  public static function getKubernetesFileNameFromBranch($repo_root, $env, $type) {
    return "$repo_root/.dockworker/deployment/k8s/$env/$type.yaml";
  }

  /**
   * Gets a kubernetes deployment name from the YAML file.
   *
   * @param string $file
   *   The path to the deployment file.
   *
   * @return string
   *   The value of the namespace element. Empty if does not exist.
   *
   * @throws \Exception
   */
  public static function getKubernetesDeploymentFileDeploymentName($file) {
    $deployment = Yaml::parseFile($file);
    if (!empty($deployment['metadata']['name'])) {
      return $deployment['metadata']['name'];
    }
    return '';
  }

  /**
   * Tokenize the k8s deployment YAML.
   *
   * @param string $repo_root
   *   The path to the repository to target.
   * @param string $env
   *   The environment to target.
   * @param string $image
   *   The image to update the deployment with.
   *
   * @return string
   *   The filename to the tokenized k8s deployment file.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function getTokenizedKubeFile($repo_root, $env, $image, $type) {
    $deployment_file = static::getKubernetesFileNameFromBranch($repo_root, $env, $type);
    if (!file_exists($deployment_file)) {
      throw new DockworkerException("Cannot find k8s resource file [$deployment_file]. This typically indicates this application does not have a $type resource.");
    }
    $tmp_yaml = tempnam(sys_get_temp_dir(), 'prefix') . '.yaml';
    $tokenized_deployment_yaml = file_get_contents($deployment_file);
    $full_deployment_yaml = str_replace('||DEPLOYMENTIMAGE||', $image, $tokenized_deployment_yaml);
    file_put_contents($tmp_yaml, $full_deployment_yaml);
    return $tmp_yaml;
  }

}
