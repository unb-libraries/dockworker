<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Cli\DockerCommandTrait;
use Dockworker\DockworkerCommands;
use Dockworker\DockworkerIOTrait;
use Robo\Symfony\ConsoleIO;

/**
 * Provides commands for interacting with Docker images.
 *
 * This is intended to be implemented by a DockworkerCommand object only.
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
     * @throws \Dockworker\DockworkerException
     *
     * @docker
     */
    public function buildDockerImage(ConsoleIO $io, string $tag): void
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
