<?php

namespace Dockworker\Docker;

use Dockworker\Cli\DockerCliTrait;
use Dockworker\IO\DockworkerIOTrait;

/**
 * Provides commands for interacting with Docker images.
 *
 * @TODO This should be moved to a trait.
 */
trait DockerComposeTrait
{
    use DockerCliTrait;
    use DockworkerIOTrait;

    /**
     * Builds this application's docker images.
     */
    protected function buildComposeApplication(): void
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
     */
    protected function startComposeApplication(): void
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
     * Deletes any persistent data from this application's stopped local deployment.
     */
    protected function stopRemoveComposeApplicationData(): void
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

    /**
     * Deletes any persistent data from this application's stopped local deployment.
     */
    protected function followComposeApplicationLogs(): void
    {
        $this->dockworkerIO->section("[local] Displaying application logs");
        $this->dockerComposeRun(
          [
            'logs',
            '-f',
            $this->applicationName,
          ],
          'Display logs for the docker compose application.'
        );
    }
}
