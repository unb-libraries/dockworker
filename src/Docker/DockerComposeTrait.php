<?php

namespace Dockworker\Docker;

use Dockworker\Cli\CliCommand;
use Dockworker\Cli\DockerCliTrait;
use Dockworker\IO\DockworkerIO;
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
     *
     * @param string $service
     *   Optional. The service to build. Defaults to all services.
     */
    protected function buildComposeApplication(string $service = ''): void
    {
        $compose_build_cmd = [
            'build',
            '--pull',
        ];
        if (!empty($service)) {
            $compose_build_cmd[] = $service;

            $section_title = "[local] Building $service";
            $run_text = "Building the $service docker image.";
            $fail_text = "Failed to build the $service docker image.";
        }
        else {
            $section_title = "[local] Building Application";
            $run_text = "Building the application docker image(s).";
            $fail_text = "Failed to build the application docker image(s).";
        }
        $this->runComposeApplicationCommand(
            $compose_build_cmd,
            $this->dockworkerIO,
            $section_title,
            $run_text,
            $fail_text,
            true
        );
    }

    /**
     * Starts the local docker compose application.
     *
     * @param string $service
     *   Optional. The service to start. Defaults to all services.
     */
    protected function startComposeApplication(string $service = ''): void
    {
        $compose_up_cmd = [
            'up',
            '-d',
        ];
        if (!empty($service)) {
            $compose_up_cmd[] = $service;

            $section_title = "[local] Starting $service";
            $run_text = "Starting the $service docker container.";
            $fail_text = "Failed to start the $service docker container.";
        }
        else {
            $section_title = "[local] Starting Application";
            $run_text = "Starting the application.";
            $fail_text = "Failed to start the application.";
        }
        $this->runComposeApplicationCommand(
            $compose_up_cmd,
            $this->dockworkerIO,
            $section_title,
            $run_text,
            $fail_text,
            true
        );
    }

    /**
     * Stops the local docker compose application.
     *
     * @param string $service
     *   Optional. The service to stop. Defaults to all services.
     */
    protected function stopComposeApplication(string $service = ''): void
    {
        $compose_stop_cmd = [
            'stop',
        ];
        if (!empty($service)) {
            $compose_stop_cmd[] = $service;

            $section_title = "[local] Stopping $service";
            $run_text = "Stopping the $service docker container.";
            $fail_text = "Failed to stop the $service docker container.";
        }
        else {
            $section_title = "[local] Stopping Application";
            $run_text = "Stopping the application.";
            $fail_text = "Failed to stop the application.";
        }
        $this->runComposeApplicationCommand(
            $compose_stop_cmd,
            $this->dockworkerIO,
            $section_title,
            $run_text,
            $fail_text,
            true
        );
    }

    /**
     * Checks the compose application status.
     *
     * @param string $service
     *   The service to build. Defaults to all services.
     *
     * @return bool
     *   TRUE if the service is running, FALSE otherwise.
     */
    protected function composeServiceIsRunning($service): bool
    {
        $compose_ps_cmd = [
            'ps',
            '-q',
            $service,
        ];
        $cmd = $this->runComposeApplicationCommand(
            $compose_ps_cmd,
            $this->dockworkerIO,
            "[local] Checking if $service is running",
            "Checking if $service is running.",
            "Failed to check if $service is running.",
            false
        );

        $output = $cmd->getOutput();
        if (strpos($output, 'no such service') !== false) {
            return false;
        }
        if (empty($output)) {
            return false;
        }
        return true;
    }

    /**
     * Deletes any persistent data from this application's stopped local deployment.
     *
     * @param bool $volumes
     *   Optional. Whether to remove volumes. Defaults to TRUE.
     * @param string $service
     *   Optional. The service to remove data for. Defaults to all services.
     * @param string $profile
     *   Optional. The profile to remove data for. Defaults to all profiled and unprofiled services.
     */
    protected function stopRemoveComposeApplicationData(bool $volumes = true, string $service = '', string $profile = '*'): void
    {
        $compose_down_cmd = [
            "--profile='$profile'",
            'down',
            '--rmi',
            'local',
        ];

        if (!empty($service)) {
            $compose_down_cmd[] = $service;

            $section_title = "[local] Removing $service data";
            $run_text = "Stopping the $service docker container and removing its data.";
            $fail_text = "Failed to stop the $service docker container and remove its data.";
        }
        else {
            $section_title = "[local] Removing existing application data";
            $run_text = "Stopping the application and removing its data.";
            $fail_text = "Failed to stop the application and remove its data.";
        }
        if ($volumes) {
            $compose_down_cmd[] = '-v';
        }
        $this->runComposeApplicationCommand(
            $compose_down_cmd,
            $this->dockworkerIO,
            $section_title,
            $run_text,
            $fail_text,
            true
        );
    }

    /**
     * Displays the logs from this application's local deployment.
     *
     * @param string $service
     *  Optional. The service to display th e logs for. Defaults to all services.
     */
    protected function showComposeApplicationLogs(string $service = ''): void
    {
        $compose_logs_cmd = [
            'logs',
        ];
        if (!empty($service)) {
            $compose_logs_cmd[] = $service;

            $section_title = "[local] Displaying $service logs";
            $run_text = "Displaying logs for the $service docker container.";
            $fail_text = "Failed to display logs for the $service docker container.";
        }
        else {
            $section_title = "[local] Displaying application logs";
            $run_text = "Displaying logs for the application.";
            $fail_text = "Failed to display logs for the application.";
        }
        $this->runComposeApplicationCommand(
            $compose_logs_cmd,
            $this->dockworkerIO,
            $section_title,
            $run_text,
            $fail_text,
            true
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

    /**
     * Runs a local docker compose command.
     *
     * @param array $run_cmd
     *   The compose command to execute.
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO object to use for the command.
     * @param string $section_title
     *   The title to display.
     * @param string $run_text
     *   The text to display when running the command.
     * @param string $fail_text
     *   The text to display when the command fails.
     */
    protected function runComposeApplicationCommand(
        array $run_cmd,
        DockworkerIO $io,
        string $section_title,
        string $run_text,
        string $fail_text,
        bool $use_tty = true
    ): CliCommand {
        $this->dockworkerIO->section($section_title);
        $cmd = $this->dockerComposeRun(
            $run_cmd,
            $run_text,
            $io,
            null,
            [],
            $use_tty
        );
        if ($cmd->getExitCode() !== 0) {
            $this->dockworkerIO->error($fail_text);
            exit(1);
        }
        return $cmd;
    }
}
