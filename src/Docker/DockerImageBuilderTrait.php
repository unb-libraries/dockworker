<?php

namespace Dockworker\Docker;

use Dockworker\Cli\DockerCliTrait;
use Dockworker\IO\DockworkerIO;

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
     * @param DockworkerIO $io
     *   The IO to use for input and output.
     */
    public function buildDockerImage(
        string $build_context,
        string $image_tag,
        DockworkerIO $io
    ): void {
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
}
