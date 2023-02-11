<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Cli\DockerCommandTrait;
use Dockworker\DockworkerCommands;
use Dockworker\IO\DockworkerIOTrait;

/**
 * Provides commands for interacting with Docker images.
 */
class DockworkerDockerImageCommands extends DockworkerCommands
{
    use DockerCommandTrait;
    use DockworkerIOTrait;

    /**
     * Builds this application's docker image(s).
     *
     * @command docker:image:build
     *
     * @docker
     */
    public function buildDockerImage(string $tag): void
    {
        $this->dockworkerIO->section('Building Docker Image');
        $this->dockerRun(
            [
                'build',
                '--tag',
                $tag,
                '--pull',
                '.',
            ],
            'Builds the docker image.'
        );
    }

}
