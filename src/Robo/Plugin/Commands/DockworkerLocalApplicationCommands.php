<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Cli\DockerCliTrait;
use Dockworker\DockworkerCommands;
use Dockworker\IO\DockworkerIOTrait;

/**
 * Provides commands for building and deploying the application locally.
 */
class DockworkerLocalApplicationCommands extends DockworkerCommands
{
    use DockerCliTrait;
    use DockworkerIOTrait;

    /**
     * Builds this application's docker images.
     *
     * @command application:build
     * @aliases build
     */
    public function buildApplication(): void
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
