<?php

namespace Dockworker\Docker;

use Dockworker\Cli\CliCommand;
use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to execute commands in deployed docker containers.
 */
class DockerContainer
{
    protected array $containerControlledBy;
    protected array $containerExecEntryPoint;
    protected \DateTimeImmutable $containerCreationTimestamp;
    protected string $containerImage;
    protected string $containerName;
    protected string $containerNamespace;
    protected string $containerStatus;

    private function __construct(
        string $name,
        string $namespace,
        string $image,
        string $status,
        \DateTimeImmutable $creation_timestamp,
        array $controlled_by,
        array $exec_entry_point
    ) {
        $this->containerName = $name;
        $this->containerNamespace = $namespace;
        $this->containerImage = $image;
        $this->containerStatus = $status;
        $this->containerCreationTimestamp = $creation_timestamp;
        $this->containerControlledBy = $controlled_by;
        $this->containerExecEntryPoint = $exec_entry_point;
    }

    public static function create(
      string $name,
      string $namespace,
      string $image,
      string $status,
      \DateTimeImmutable $creation_timestamp,
      array $controlled_by,
      array $exec_entry_point
    ) {
        return new static(
            $name,
            $namespace,
            $image,
            $status,
            $creation_timestamp,
            $controlled_by,
            $exec_entry_point
        );
    }

    public function run(
      array $command,
      DockworkerIO $io,
      bool $use_tty = true,
    ) {
        $command = array_merge(
            $this->containerExecEntryPoint,
            $command
        );
        $cmd = new CliCommand(
          $command,
          'Running command in container',
          null,
          [],
          null,
          null
        );
        if ($use_tty) {
            $cmd->runTty($io);
        }
        else {
            $cmd->mustRun();
        }
    }

    /**
     * @return string
     */
    public function getContainerName(): string
    {
        return $this->containerName;
    }
    /**
     * @return string
     */
    public function getContainerNamespace(): string
    {
        return $this->containerNamespace;
    }
}
