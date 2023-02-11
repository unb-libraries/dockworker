<?php

namespace Dockworker\Robo\Plugin\Commands;


use Dockworker\Cli\DockerCliTrait;
use Dockworker\CliCommand;
use Dockworker\DockworkerCommands;
use Dockworker\DockerTrait;
use Dockworker\DockworkerIOTrait;
use Dockworker\DockworkerPersistentDataStorageTrait;
use Robo\Symfony\ConsoleIO;

/**
 * Provides commands for interacting with Docker images.
 *
 * This is intended to be implemented by a DockworkerCommand object only.
 */
class DockworkerDockerImageCommands extends DockworkerCommands
{
    use DockworkerPersistentDataStorageTrait;
    use DockerCliTrait;
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
        $cmd = new CliCommand(
            [
                $this->cliTools['docker'],
                'build',
                '--tag',
                $tag,
                '.',
            ],
            'Builds the docker image.'
        );
        $cmd->runTty();
    }

}
