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
    protected function buildComposeApplication(string $service = ''): void
    {
        $this->dockworkerIO->section("[local] Building Application");
        $compose_build_cmd = [
            'build',
            '--pull',
        ];
        if (!empty($service)) {
            $compose_build_cmd[] = $service;
        }
        $cmd = $this->dockerComposeRun(
            $compose_build_cmd,
            'Building the docker image.',
            $this->dockworkerIO
        );
        if ($cmd->getExitCode() !== 0) {
            $this->dockworkerIO->error('Failed to build the docker image.');
            exit(1);
        }
    }

    /**
     * Starts the local docker compose application.
     */
    protected function startComposeApplication(string $service = ''): void
    {
        $this->dockworkerIO->section("[local] Starting Application");
        $compose_up_cmd = [
            'up',
            '-d',
        ];
        if (!empty($service)) {
            $compose_up_cmd[] = $service;
        }
        $cmd = $this->dockerComposeRun(
            $compose_up_cmd,
            'Starting the local application.',
            $this->dockworkerIO
        );
        if ($cmd->getExitCode() !== 0) {
            $this->dockworkerIO->error('Failed to start the docker container.');
            exit(1);
        }
    }

    /**
     * Stops the local docker compose application.
     */
    protected function stopComposeApplication(string $service = ''): void
    {
        $this->dockworkerIO->section("[local] Stopping Application");
        $compose_stop_cmd = [
            'stop',
        ];
        if (!empty($service)) {
            $compose_stop_cmd[] = $service;
        }
        $cmd = $this->dockerComposeRun(
            $compose_stop_cmd,
            'Stopping the local application.',
            $this->dockworkerIO
        );
        if ($cmd->getExitCode() !== 0) {
            $this->dockworkerIO->error('Failed to stop the docker container.');
            exit(1);
        }
    }

    /**
     * Deletes any persistent data from this application's stopped local deployment.
     */
    protected function stopRemoveComposeApplicationData(bool $volumes = true, string $service = ''): void
    {
        $this->dockworkerIO->section("[local] Removing existing application data");
        $compose_down_cmd = [
            'down',
            '--rmi',
            'local',
        ];
        if (!empty($service)) {
            $compose_down_cmd[] = $service;
        }
        if ($volumes) {
            $compose_down_cmd[] = '-v';
        }
        $this->dockerComposeRun(
            $compose_down_cmd,
            'Stopping the compose application and removing its data.',
            $this->dockworkerIO,
        );
    }

    /**
     * Deletes any persistent data from this application's stopped local deployment.
     */
    protected function showComposeApplicationLogs(string $service = ''): void
    {
        $this->dockworkerIO->section("[local] Displaying application logs");
        $compose_logs_cmd = [
            'logs',
        ];
        if (!empty($service)) {
            $compose_logs_cmd[] = $service;
        }
        $this->dockerComposeRun(
            $compose_logs_cmd,
            'Display logs for the docker compose application.',
            $this->dockworkerIO
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
            'Copy file.',
            $this->dockworkerIO
        );
    }
}
