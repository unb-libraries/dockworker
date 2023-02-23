<?php

namespace Dockworker\Cli;

use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to interact with docker.
 */
trait DockerCliTrait
{
    use CliToolTrait;

    /**
     * Registers docker as a required CLI tool.
     */
    public function registerDockerCliTool(DockworkerIO $io): void
    {
        $file_path = "$this->applicationRoot/vendor/unb-libraries/dockworker/data/cli-tools/docker.yml";
        $this->registerCliToolFromYaml($file_path, $io);
    }

  /**
   * Constructs a docker command.
   *
   * @param array $command
   *   The full CLI command to execute.
   * @param string $description
   *   A description of the command.
   * @param ?float $timeout
   *   The timeout in seconds or null to disable
   *
   * @return \Dockworker\Cli\DockerCli
   */
    protected function dockerCli(
        array $command,
        string $description,
        ?float $timeout = null
    ): DockerCli {
        array_unshift($command, $this->cliTools['docker']);
        return new DockerCli(
            $command,
            $description,
            null,
            null,
            null,
            $timeout
        );
    }

  /**
   * Runs a docker command.
   *
   * @param array $command
   *   The full CLI command to execute.
   * @param string $description
   *   A description of the command.
   * @param ?float $timeout
   *   The timeout in seconds or null to disable
   * @param bool $use_tty
   *   Whether to use a TTY for the command. Defaults to TRUE.
   *
   * @return \Dockworker\Cli\DockerCli
   */
    protected function dockerRun(
        array $command,
        string $description,
        ?float $timeout = null,
        bool $use_tty = true
    ): DockerCli {
      $cmd = $this->dockerCli($command, $description, $timeout)
            ->setWorkingDirectory($this->applicationRoot);
      if ($use_tty) {
        $cmd->runTty($this->dockworkerIO);
      }
      else {
        $cmd->mustRun();
      }
      return $cmd;
    }

  /**
   * Constructs a 'docker compose' command.
   *
   * @param array $command
   *   The full CLI command to execute.
   * @param string $description
   *   A description of the command.
   * @param ?float $timeout
   *   The timeout in seconds or null to disable
   * @param string[] $profiles
   *   The docker compose profiles to target with this command.
   *
   * @return \Dockworker\Cli\DockerCli
   */
    protected function dockerComposeCli(
        array $command,
        string $description = '',
        ?float $timeout = null,
        array $profiles = []
    ): DockerCli {
        array_unshift(
            $command,
            $this->cliTools['docker'],
            'compose'
        );
        if (!empty($profiles)) {
            $env = [
                'COMPOSE_PROFILES' => implode(',', $profiles),
            ];
        } else {
            $env = null;
        }
        return new DockerCli(
            $command,
            $description,
            null,
            $env,
            null,
            $timeout
        );
    }

  /**
   * Runs a 'docker compose' command.
   *
   * @param array $command
   *   The full CLI command to execute.
   * @param string $description
   *   A description of the command.
   * @param ?float $timeout
   *   The timeout in seconds or null to disable
   * @param string[] $profiles
   *   The docker compose profiles to target with this command.
   * @param bool $use_tty
   *   Whether to use a TTY for the command. Defaults to TRUE.
   *
   * @return \Dockworker\Cli\DockerCli
   */
    protected function dockerComposeRun(
        array $command,
        string $description = '',
        ?float $timeout = null,
        array $profiles = [],
        bool $use_tty = true
    ): DockerCli {
        $cmd = $this->dockerComposeCli($command, $description, $timeout, $profiles)
            ->setWorkingDirectory($this->applicationRoot);
      if ($use_tty) {
        $cmd->runTty($this->dockworkerIO);
      }
      else {
        $cmd->mustRun();
      }
        return $cmd;
    }
}
