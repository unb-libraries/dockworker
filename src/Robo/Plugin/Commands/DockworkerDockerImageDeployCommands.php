<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\KubernetesDeploymentTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerDockerImageBuildCommands;

/**
 * Defines the commands used to build and push a docker image.
 */
class DockworkerDockerImageDeployCommands extends DockworkerDockerImagePushCommands {

  use KubernetesDeploymentTrait;

  /**
   * Builds this application's docker image, pushes it to the container registry, and updates its k8s deployment with it.
   *
   * @param string $env
   *   The environment to target.
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $use-tag
   *   Skip building and deploy with the specified tag.
   *
   * @command docker:image:deploy
   * @throws \Exception
   *
   * @usage prod
   *
   * @dockerimage
   * @dockerpush
   */
  public function buildPushDeployEnv($env, array $options = ['use-tag' => '']) {
    $this->pushCommandInit($env);
    $this->updateAllResourcesInK8s($env, $options);
  }

  /**
   * Builds the image name to use when updating k8s resources.
   *
   * @param string $env
   *   The environment to target.
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $use-tag
   *   Leveragethe specified tag.
   *
   * @throws \Dockworker\DockworkerException
   *
   * @return string
   */
  protected function getDeploymentResourceImageName(
    string $env,
    array $options = ['use-tag' => '']
  ) : string {
    if (empty($options['use-tag'])) {
      $timestamp = date('YmdHis');
      $this->buildPushEnv($env, $timestamp);

      if ($this->dockerImageTagDateStamp) {
       return "{$this->dockerImageName}:$env-$timestamp";
      }
      else {
        return "{$this->dockerImageName}:$env";
      }
    }
    else {
      return "{$this->dockerImageName}:{$options['use-tag']}";
    }
  }

  /**
   * Updates all k8s resources that are defined in this repository.
   *
   * @param string $env
   *   The environment to target.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function updateAllResourcesInK8s(string $env, array $options) : void {
    $resource_deploy_path =  "$this->repoRoot/.dockworker/deployment/k8s/$env";
    $resource_files = glob("$resource_deploy_path/*.yaml");
    foreach($resource_files as $resource_file) {
      $resource_basename = basename($resource_file, '.yaml');
      $resource_type = explode("-", $resource_basename)[0];
      $this->notifyUserK8sResourceUpdate($resource_type);
      switch ($resource_type) {
        case 'deployment':
          $this->applyKubeDeploymentUpdate(
            $this->repoRoot,
            $env,
            $this->getDeploymentResourceImageName($env, $options)
          );
          $this->say('Checking for successful deployment...');
          $this->setRunOtherCommand("k8s:deployment:status $env");
          break;
        case 'cronjob':
          $cron_file = $this->getTokenizedKubeFile(
            $this->repoRoot,
            $env,
            $this->getDeploymentResourceImageName($env, $options),
            'cronjob'
          );
          $this->setRunOtherCommand("k8s:deployment:delete-apply $cron_file");
          break;
        case 'backup':
          $this->setRunOtherCommand("k8s:deployment:delete-apply $resource_file");
          break;
        case 'testing':
          $this->setRunOtherCommand("k8s:deployment:create-test-secrets");
          $this->setRunOtherCommand("k8s:deployment:delete-apply $resource_file");
          break;
        default:
          $this->say("Resource type $resource_type is not known to dockworker. Skipping...");
      }
    }
  }

  /**
   * Notifies the end user that a resource is about to be updated in k8s.
   *
   * @param string $resource_type
   *   The type of resource to notify the user about.
   *
   * @return void
   */
  protected function notifyUserK8sResourceUpdate($resource_type) {
    $this->say("Updating $resource_type in k8s...");
  }

}
