<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Cli\DockerCliTrait;
use Dockworker\DockworkerCommands;
use Dockworker\IO\DockworkerIOTrait;

/**
 * Provides commands for interacting with Docker images.
 */
class DockworkerDockerImageCommands extends DockworkerCommands
{
    use DockerCliTrait;
    use DockworkerIOTrait;

    /**
     * Builds one of this application's docker images.
     *
     * @param string $image_tag
     *   The docker image tag to build.
     * @param string $build_context
     *   The context to build. Defaults to the root of the application.
     *
     * @command docker:image:build
     * @usage unb-libraries/dockworker:latest .
     */
    public function buildDockerImage(string $image_tag, string $build_context = ''): void
    {
        $build_context = empty($build_context) ? $this->applicationRoot : $build_context;
        $this->dockworkerIO->section("Building [$image_tag] from [$build_context/Dockerfile]");
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
     * Builds one of this application's docker images.
     *
     * @command application:build
     * @aliases build
     * @hidden
     */
    public function buildDockerComposeImage(): void
    {
        $this->dockworkerIO->section("Building Application");
        $this->dockerComposeRun(
            [
                'build',
                '--pull',
            ],
            'Builds the docker image.'
        );
    }
}
