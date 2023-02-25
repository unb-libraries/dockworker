<?php

namespace Dockworker\Cli;

use Dockworker\IO\DockworkerIO;
use Dockworker\Cli\CliCommand;

/**
 * Provides methods to interact with Jira for this dockworker application.
 */
trait KubectlCliTrait
{
    use CliToolTrait;

    /**
     * Registers kubectl as a required CLI tool.
     */
    public function registerKubectlCliTool(DockworkerIO $io): void
    {
        $file_path = "$this->applicationRoot/vendor/unb-libraries/dockworker/data/cli-tools/kubectl.yml";
        $this->registerCliToolFromYaml($file_path, $io);
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
   *
   * @return \Dockworker\Cli\CliCommand
   */
  protected function kubeCtlCli(
    array $command,
    string $description = '',
    ?float $timeout = null
  ): CliCommand {
    array_unshift(
      $command,
      $this->cliTools['kubectl']
    );
    return new CliCommand(
      $command,
      $description,
      null,
      [],
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
   * @param bool $use_tty
   *   Whether to use a TTY for the command. Defaults to TRUE.
   *
   * @return \Dockworker\Cli\CliCommand
   */
  protected function kubeCtlRun(
    array $command,
    string $description = '',
    ?float $timeout = null,
    bool $use_tty = true
  ): CliCommand {
    $cmd = $this->kubeCtlCli($command, $description, $timeout)
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
