<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\DockerImagePushTrait;
use Dockworker\DockerImageTrait;
use Dockworker\KubernetesDeploymentTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerDockerImageBuildCommands;

/**
 * Defines the commands used to build and push a docker image.
 */
class DockworkerDockerImageBuildPushCommands extends DockworkerDockerImageBuildCommands {

  use DockerImagePushTrait;
  use DockerImageTrait;
  use KubernetesDeploymentTrait;

  /**
   * Builds the application's docker image and pushes it to the deployment repository.
   *
   * @param string $tag
   *   The tag to use when building and pushing the image.
   *
   * @option bool $no-cache
   *   Do not use any cached steps in the build.
   * @option string $cache-from
   *   The image to cache the build from.
   *
   * @command image:build-push
   * @throws \Exception
   *
   * @usage image:build-push prod
   *
   * @dockerimage
   * @dockerpush
   */
  public function buildAndPushImage($tag) {
    $this->setRunOtherCommand('image:build $tag');
    $this->pushToRepository($tag);
  }

  /**
   * Builds, tags, pushes and deploys the application's docker image.
   *
   * @param string $env
   *   The environment to target.
   *
   * @option bool $no-cache
   *   Do not use any cached steps in the build.
   * @option string $cache-from
   *   The image to cache the build from.
   *
   * @command image:deploy
   * @throws \Exception
   *
   * @usage image:deploy prod
   *
   * @dockerimage
   * @dockerpush
   */
  public function buildPushDeployEnv($env) {
    $this->pushCommandInit($env);
    $timestamp = date('YmdHis');
    $this->buildPushEnv($env, $timestamp);

    if ($this->dockerImageTagDateStamp) {
      $image_name = "{$this->dockerImageName}:$env-$timestamp";
    }
    else {
      $image_name = "{$this->dockerImageName}:$env";
    }

    if ($this->environmentIsDeployable($env)) {
      $deployment_file = $this->applyKubeDeploymentUpdate($this->repoRoot, $env, $image_name);
      $deploy_namespace = $this->getKubernetesDeploymentFileNamespace($deployment_file);
      $this->setRunOtherCommand("deployment:status $deploy_namespace");
    }
    else {
      // Deploy a feature branch to k8s.
      //
      // @TODO: Add ['metatadata']['labels']['uri] to Ingress
      //
      // new method exportAppToYAML(id, namespace, export_dir)
      //   - check if export_dir empty, otherwise die
      //   - foreach 'Service', 'Deployment', 'Ingress':
      //     - export to YAML
      //
      // new method copyAppToFeatureBranch(source_namespace, copy_services)
      //   - foreach copy_services:
      //     - create temp_dir
      //     - exportAppToYAML(id, source_namespace, temp_dir)
      //     - foreach files in temp_dir:
      //       - $this->prepareObjectForFeatureBranch(file)
      //     - kubectl create -f tmp_dir
      //     - delete tmp_dir
      //
      // new method prepareObjectForFeatureBranch(file)
      //   - read file into array
      //   - uri = file['metatadata']['labels']['uri]
      //   - feature_uri = {branch_slug}-{uri}
      //   - switch $array['kind']
      //     case 'Service':
      //       - REPLACE metadata.namespace {feature_namespace}
      //       - REPLACE metadata.labels.vcsRef {feature_uri}
      //       - REPLACE metadata.labels.uri {feature_uri}
      //       - REPLACE spec.selector.uri {feature_uri}
      //     case 'Deployment':
      //       - foreach containers in spec.template.spec.containers:
      //        - REPLACE container.image {feature_branch_img}
      //         - foreach container.env:
      //           - if is_set env['valueFrom']
      //             - $this->copyEnvSecretToFeatureNamespace(env_arr, source_env)
      //         - REMOVE container.volumeMounts
      //       - REMOVE spec.template.spec.volumes
      //       - REPLACE metadata.namespace {feature_namespace}
      //       - REPLACE metadata.labels.vcsRef {feature_uri}
      //       - REPLACE metadata.labels.uri {feature_uri}
      //     case 'Ingress':
      //       - REPLACE metadata.namespace {feature_namespace}
      //       - REPLACE spec.tls.hosts  {feature_uri}
      //       - REPLACE spec.rules.host {feature_uri}
      //
      // $this->setupFeatureBranchDeployMetadata()
      //   - feature_namespace= {branch_slug}-{uri}
      //   - source_namespace = config.feature_branches.data_source (dockworker.yml)
      //   - required_services = config.feature_branches.required_services (dockworker.yml)
      //   - copy_services = Append this app_id from deployments/k8s/{source_env}/deployment.yml to required_services
      //S
      // $this->cleanupCreateFeatureBranchDeployNamespace()
      //   - if !dev, !prod: // avoid wiping out deployments
      //     - kubectl delete namespace {feature_namespace} // Deletes Everything!
      //     - kubectl create namespace {feature_namespace}
      //
      // $this-> copyAppToFeatureBranch(source_namespace, copy_services)
      //
      // $this->copyDataToFeatureBranch(source_namespace, feature_namespace)
      //   Extensions define this. i.e. dockworker-drupal: database, files
      //
      // $this->runRequiredMaintenanceTasks(feature_namespace)
      //   Extensions define this. i.e. dockworker-drupal:
      //     - Somehow create solr index dynamically?
      //     - Then : drush sapi-r
    }
  }

  /**
   * Builds the docker image, tags it with a current timestamp, and pushes it.
   *
   * This method is intended to be used as part of a build-push-deploy command,
   * usually in travis. In the vein, this cannot be called from a dirty git
   * repository.
   *
   * @param string $env
   *   The environment to target.
   * @param string $timestamp
   *   The timestamp string to use when tagging the image.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function buildPushEnv($env, $timestamp) {
    $this->pushCommandInit($env);
    $this->setRunOtherCommand("image:build $env");
    $this->pushToRepository($env);

    if ($this->dockerImageTagDateStamp) {
      $this->setRunOtherCommand("image:build $env-$timestamp");
      $this->pushToRepository("$env-$timestamp");
    }
  }

}
