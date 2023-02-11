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
     * @param string $tag
     *   The tag to build.
     * @param string $context
     *   The context to build.
     *
     * @command docker:image:build
     *
     * @docker
     */
    public function buildDockerImage(string $tag, string $context = '.'): void
    {
        $this->dockworkerIO->section('Building Docker Image(s)');
        $this->dockerRun(
            [
                'build',
                '--tag',
                $tag,
                '--pull',
                $context,
            ],
            'Builds the docker image.'
        );
    }

}
