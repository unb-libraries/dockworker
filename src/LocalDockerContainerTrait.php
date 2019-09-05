<?php

namespace Dockworker;

use Dockworker\KubernetesPodTrait;

/**
 * Defines trait for interacting with local docker containers.
 */
trait LocalDockerContainerTrait {

  /**
   * Execute a command in a local docker container.
   *
   * @param string $name
   *   The name of the container to execute the command in.
   * @param $command
   *   The command to execute.
   * @param bool $except_on_error
   *   TRUE to throw an exception on error. FALSE otherwise.
   *
   * @return mixed
   * @throws \Exception
   */
  protected function localDockerContainerExecCommand($name, $command, $except_on_error = TRUE) {
    exec(
      sprintf("docker exec -t %s sh -c '%s'",
        $name,
        $command
      ),
      $cmd_output,
      $return_code
    );
    if ($return_code != 0 && $except_on_error) {
      throw new \Exception("Local docker command [$command] returned error code $return_code : $cmd_output.");
    }
    return $cmd_output;
  }

  /**
   * Copy a file between a docker container and the local filesystem.
   *
   * @param string $source_path
   *   The source path of the file to copy.
   * @param $target_path
   *   The target path of the file to copy.
   * @param bool $except_on_error
   *   TRUE to throw an exception on error. FALSE otherwise.
   *
   * @return mixed
   * @throws \Exception
   */
  protected function localDockerContainerCopyCommand($source_path, $target_path, $except_on_error = TRUE) {
    exec(
      sprintf("docker cp %s %s",
        $source_path,
        $target_path
      ),
      $cmd_output,
      $return_code
    );
    if ($return_code != 0 && $except_on_error) {
      throw new \Exception("Local copy [$source_path -> $target_path] returned error code $return_code : $cmd_output.");
    }
    return $cmd_output;
  }

}
