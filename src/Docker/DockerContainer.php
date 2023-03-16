<?php

namespace Dockworker\Docker;

use DateTimeImmutable;
use Dockworker\Cli\CliCommand;
use Dockworker\IO\DockworkerIO;

/**
 * Provides methods to execute commands in deployed docker containers.
 */
class DockerContainer
{
    /**
     * The entities controlling the container.
     *
     * For local containers, this will only be docker compose. For kubernetes
     * pods, the values will be the replication controller entity ID and its
     * parents.
     *
     * @var string[]
     */
    protected array $containerControlledBy;

    /**
     * The CLI execution entry point for the container.
     *
     * @var string[]
     */
    protected array $containerExecEntryPoint;

    /**
     * The time the container was created.
     *
     * @var DateTimeImmutable
     */
    protected DateTimeImmutable $containerCreationTimestamp;

    /**
     * The image used to create the container.
     *
     * @var string
     */
    protected string $containerImage;

    /**
     * The name of the container.
     *
     * @var string
     */
    protected string $containerName;

    /**
     * The namespace of the container.
     *
     * @var string
     */
    protected string $containerNamespace;

    /**
     * The state/status of the container.
     *
     * @var string
     */
    protected string $containerStatus;

    /**
     * DockerContainer constructor.
     *
     * @param string $name
     *   The name of the container.
     * @param string $namespace
     *   The namespace of the container.
     * @param string $image
     *   The image used to create the container.
     * @param string $status
     *   The state/status of the container.
     * @param DateTimeImmutable $creation_timestamp
     *   The time the container was created.
     * @param array $controlled_by
     *   The entities controlling the container.
     * @param array $exec_entry_point
     *   The CLI execution entry point for the container.
     */
    private function __construct(
        string $name,
        string $namespace,
        string $image,
        string $status,
        DateTimeImmutable $creation_timestamp,
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

    /**
     * Creates a new DockerContainer object.
     *
     * @param string $name
     *   The name of the container.
     * @param string $namespace
     *   The namespace of the container.
     * @param string $image
     *   The image used to create the container.
     * @param string $status
     *   The state/status of the container.
     * @param DateTimeImmutable $creation_timestamp
     *   The time the container was created.
     * @param array $controlled_by
     *   The entities controlling the container.
     * @param array $exec_entry_point
     *   The CLI execution entry point for the container.
     *
     * @return DockerContainer
     *   The new DockerContainer object.
     */
    public static function create(
        string $name,
        string $namespace,
        string $image,
        string $status,
        DateTimeImmutable $creation_timestamp,
        array $controlled_by,
        array $exec_entry_point
    ): DockerContainer {
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

    /**
     * Runs a command in the container.
     *
     * @param array $command
     *   The command to run in the container.
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param bool $use_tty
     *   TRUE to attach to a TTY for the command.
     */
    public function run(
        array $command,
        DockworkerIO $io,
        bool $use_tty = true,
    ): void {
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
        } else {
            $cmd->mustRun();
        }
    }

    /**
     * Gets the container name.
     *
     * @return string
     *   The container name.
     */
    public function getContainerName(): string
    {
        return $this->containerName;
    }
    /**
     * Gets the container namespace.
     *
     * @return string
     *   The container namespace.
     */
    public function getContainerNamespace(): string
    {
        return $this->containerNamespace;
    }
}
