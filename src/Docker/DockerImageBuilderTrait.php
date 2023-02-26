<?php

namespace Dockworker\Docker;

use Dockworker\Cli\DockerCliTrait;

/**
 * Provides commands for interacting with Docker images.
 *
 * @TODO This should be moved to a trait.
 */
trait DockerImageBuilderTrait
{
    use DockerCliTrait;

    /**
     * Builds a docker image.
     *
     * @param string $build_context
     *   The context to build. Defaults to the root of the application.
     * @param string $image_tag
     *   The docker image tag to build.
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     */
    public function buildDockerImage(
      string $build_context,
      string $image_tag,
      DockworkerIO $io
    ): void
    {
        $io->section("Building [$image_tag] from [$build_context/Dockerfile]");
        $this->dockerRun(
          [
            'build',
            '--tag',
            $image_tag,
            '--pull',
            $build_context,
          ],
          'Builds the docker image.'
        );
    }

    /**
     * Builds this application's docker images.
     */
    public function buildComposeApplication(): void
    {
        $this->dockworkerIO->section("[local] Building Application");
        $this->dockerComposeRun(
          [
            'build',
            '--pull',
          ],
          'Building the docker image.'
        );
    }

    /**
     * Starts the local docker compose application.
     *
     * @command local:start
     * @hidden
     */
    public function startComposeApplication(): void
    {
        $this->dockworkerIO->section("[local] Starting Application");
        $this->dockerComposeRun(
          [
            'up',
            '-d',
          ],
          'Starting the local application.'
        );
    }
}
