<?php

namespace Dockworker\Docker;

use Dockworker\Cli\DockerCliTrait;
use Dockworker\IO\DockworkerIOTrait;

/**
 * Provides methods for interacting with Docker compose stacks.
 *
 * @INTERNAL This trait is intended only to be used by Dockworker commands. It
 * references user properties which are not in its own scope.
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
    protected function showComposeApplicationLogs(): void
    {
        $this->dockworkerIO->section("[local] Displaying application logs");
        $this->dockerComposeRun(
            [
                'logs',
                $this->applicationName,
            ],
            'Display logs for the docker compose application.'
        );
    }

    /**
     * Copies a file between the local filesystem and the application container.
     *
     * @param string $source_path
     *   The path of the source file.
     * @param string $target_path
     *   The path of the target file.
     */
    protected function composeApplicationCopyFile(
        string $source_path,
        string $target_path
    ): void {
        $this->dockworkerIO->section("[local] Copying application file");
        $this->dockerComposeRun(
            [
                'cp',
                $source_path,
                $target_path,
            ],
            'Copy file.'
        );
    }
}
