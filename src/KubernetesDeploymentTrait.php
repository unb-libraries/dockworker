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
   * The k8s target entity name.
   *
   * @var string
   */
  protected $deployedK8sResourceName;

  /**
   * The k8s target entity namespace.
   *
   * @var string
   */
  protected $deployedK8sResourceNameSpace;

  /**
   * The k8s target entity type.
   *
   * @var string
   */
  protected $deployedK8sResourceType;

  /**
   * Determines if a deployment command should continue.
   *
   * @throws \Exception
   */
  protected function checkDeployedK8sResourceEnv($env) {
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
      ->get('dockworker.deployment.environments', ['prod']);
  }

  /**
   * Initializes configuration to operate on a deployed k8s resource.
   *
   * @param string $repo_root
   *   The path to the repository to target.
   * @param string $env
   *   The environment to target.
   *
   * @throws \Exception
   */
  protected function deployedK8sResourceInit($repo_root, $env, $type = 'deployment') {
    $this->checkDeployedK8sResourceEnv($env);
    $this->deployedK8sResourceNameSpace = self::getDeployedK8sResourceNamespace($repo_root, $env, $type);
    $this->deployedK8sResourceName = self::getDeployedK8sResourceName($repo_root, $env, $type);
    $this->deployedK8sResourceType = $type;
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
   * @return string
   *   The path to the deployment file being applied.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function applyKubeDeploymentUpdate($repo_root, $env, $image) {
    if ($this->environmentIsDeployable($env)) {
      $deployment_file = $this->getTokenizedKubeFile($repo_root, $env, $image, 'deployment');
      $this->setRunOtherCommand("k8s:deployment:update $deployment_file");
      return $deployment_file;
    }
    $this->say("Skipping deployment for environment [$env]. Deployable environments: " . implode(',', $this->getDeployableEnvironments()));
    return '';
  }

}
