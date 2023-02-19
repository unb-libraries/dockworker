<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\Cli\DockerCliTrait;
use Dockworker\DockworkerCommands;
use Dockworker\IO\DockworkerIOTrait;
use Dockworker\System\LocalHostFileOperationsTrait;

/**
 * Provides commands for building and deploying the application locally.
 */
class DockworkerLocalApplicationCommands extends DockworkerCommands
{
    use DockerCliTrait;
    use DockworkerIOTrait;
    use LocalHostFileOperationsTrait;

    /**
     * Builds this application's docker images.
     *
     * @command build
     */
    public function buildComposeApplication(): void
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

    /**
     * Builds this application's docker images.
     *
     * @command local:start
     * @hidden
     */
    public function startComposeApplication(): void
    {
        $this->dockworkerIO->section("Starting Application");
        $this->dockerComposeRun(
            [
                'up',
                '-d',
            ],
            'Starts the docker container.'
        );
    }

    /**
     * Deploys the application locally.
     *
     * @command deploy
     * @aliases start-over redeploy
     */
    public function deployComposeApplication(): void
    {
        $this->setLocalHostFileEntries();
        $this->buildComposeApplication();
        $this->startComposeApplication();
    }
}
