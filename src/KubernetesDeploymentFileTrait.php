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
    $deployment = Yaml::parseFile($file);
    if (!empty($deployment['metadata']['namespace'])) {
      return $deployment['metadata']['namespace'];
    }
    return NULL;
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
  public static function getKubernetesDeploymentNamespaceFromBranch($repo_root, $env) {
    return self::getKubernetesDeploymentFileNamespace(
      self::getKubernetesDeploymentFileNameFromBranch($repo_root, $env)
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
  public static function getKubernetesDeploymentNameFromBranch($repo_root, $env) {
    return self::getKubernetesDeploymentFileDeploymentName(
      self::getKubernetesDeploymentFileNameFromBranch($repo_root, $env)
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
  public static function getKubernetesDeploymentFileNameFromBranch($repo_root, $env) {
    return "$repo_root/.dockworker/deployment/k8s/$env/deployment.yaml";
  }

  /**
   * Gets a kubernetes deployment name from the YAML file.
   *
   * @param string $file
   *   The path to the deployment file.
   *
   * @return string
   *   The value of the namespace element. NULL if does not exist.
   *
   * @throws \Exception
   */
  public static function getKubernetesDeploymentFileDeploymentName($file) {
    $deployment = Yaml::parseFile($file);
    if (!empty($deployment['metadata']['name'])) {
      return $deployment['metadata']['name'];
    }
    return NULL;
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
  protected function getTokenizedKubeDeploymentFile($repo_root, $env, $image) {
    $deployment_file = getKubernetesDeploymentFileNameFromBranch($repo_root, $env);
    if (!file_exists($deployment_file)) {
      throw new DockworkerException("Cannot find deployment file [$deployment_file]");
    }
    $tmp_yaml = tempnam(sys_get_temp_dir(), 'prefix') . '.yaml';
    $tokenized_deployment_yaml = file_get_contents($deployment_file);
    $full_deployment_yaml = str_replace('||DEPLOYMENTIMAGE||', $image, $tokenized_deployment_yaml);
    file_put_contents($tmp_yaml, $full_deployment_yaml);
    return $tmp_yaml;
  }

}
