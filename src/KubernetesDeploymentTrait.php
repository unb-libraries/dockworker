<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Dockworker\KubectlTrait;
use Dockworker\KubernetesDeploymentFileTrait;
use Dockworker\KubernetesPodTrait;
use Robo\Robo;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides methods to execute commands inside Kubernetes Pods.
 */
trait KubernetesDeploymentTrait {

  use KubernetesDeploymentFileTrait;
  use KubernetesPodTrait;

  /**
   * The k8s target deployment name.
   *
   * @var string
   */
  protected $deploymentK8sName;

  /**
   * The k8s target deployment namespace.
   *
   * @var string
   */
  protected $deploymentK8sNameSpace;

  /**
   * Determines if a deployment command should continue.
   *
   * @throws \Exception
   */
  protected function deploymentCommandShouldContinue($env) {
    if ($this->environmentIsDeployable($env)) {
      return;
    }
    else {
      $this->say("Skipping deployment command for environment [$env]. Deployable environments: " . implode(',', $this->getDeployableEnvironments()));
      exit(0);
    }
  }

  /**
   * Determines if an environment is marked as deployable.
   *
   * @throws \Exception
   */
  protected function environmentIsDeployable($env) {
    $deployable_environments = $this->getDeployableEnvironments();
    if (empty($deployable_environments) || !in_array($env, $deployable_environments)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Retrieves the environments that are marked as deployable.
   *
   * @throws \Exception
   */
  protected function getDeployableEnvironments() {
    return Robo::Config()
      ->get('dockworker.deployment.environments', []);
  }

  /**
   * Initialize a deployment command.
   *
   * @param string $repo_root
   *   The path to the repository to target.
   * @param string $env
   *   The environment to target.
   *
   * @throws \Exception
   */
  protected function deploymentCommandInit($repo_root, $env) {
    $this->deploymentCommandShouldContinue($env);
    $this->deploymentK8sNameSpace = self::getKubernetesDeploymentNamespaceFromBranch($repo_root, $env);
    $this->deploymentK8sName = self::getKubernetesDeploymentNameFromBranch($repo_root, $env);
  }

  /**
   * Tokenize the k8s deployment YAML and update the k8s deployment.
   *
   * @param string $repo_root
   *   The path to the repository to target.
   * @param string $env
   *   The environment to target.
   * @param string $image
   *   The image to update the deployment with.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function applyKubeDeploymentUpdate($repo_root, $env, $image) {
    if ($this->environmentIsDeployable($env)) {
      $deployment_file = $this->getTokenizedKubeFile($repo_root, $env, $image, 'deployment');
      $this->setRunOtherCommand("deployment:apply $deployment_file");
      return $deployment_file;
    }
    else {
      $this->say("Skipping deployment for environment [$env]. Deployable environments: " . implode(',', $this->getDeployableEnvironments()));
    }
    return NULL;
  }

}
