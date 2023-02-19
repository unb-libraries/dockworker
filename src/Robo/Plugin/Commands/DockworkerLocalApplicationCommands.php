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

    /**
     * Deploys the application locally.
     *
     * @command deploy
     * @aliases start-over redeploy
     */
    public function deployComposeApplication(): void
    {
        $this->dockworkerIO->title("Dockworker: Deploying $this->applicationName");
        $this->stopRemoveComposeApplicationData();
        $this->setLocalHostFileEntries();
        $this->buildComposeApplication();
        $this->startComposeApplication();
    }

    /**
     * Deletes any persistent data from this application's stopped local deployment.
     *
     * @command rm
     * @hidden
     */
    public function stopRemoveComposeApplicationData(): void
    {
      $this->dockworkerIO->section("[local] Removing existing application data");
      $this->dockerComposeRun(
        [
          'down',
          '--rmi',
          'local',
          '-v',
        ],
        'Stopping he compose application and removing its data.'
      );
    }
}
