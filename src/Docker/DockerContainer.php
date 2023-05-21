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
     * The CLI logs command for the container.
     *
     * @var string[]
     */
    protected array $containerLogsCommand;

    /**
     * The file copy entry point for the container.
     *
     * @var string[]
     */
    protected array $containerCopyEntryPoint;

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
     * @param array $copy_entry_point
     *   The file copy entry point for the container.
     * @param array $logs_command
     *   The command to retrieve logs for the container.
     */
    private function __construct(
        string $name,
        string $namespace,
        string $image,
        string $status,
        DateTimeImmutable $creation_timestamp,
        array $controlled_by,
        array $exec_entry_point,
        array $copy_entry_point,
        array $logs_command
    ) {
        $this->containerName = $name;
        $this->containerNamespace = $namespace;
        $this->containerImage = $image;
        $this->containerStatus = $status;
        $this->containerCreationTimestamp = $creation_timestamp;
        $this->containerControlledBy = $controlled_by;
        $this->containerExecEntryPoint = $exec_entry_point;
        $this->containerCopyEntryPoint = $copy_entry_point;
        $this->containerLogsCommand = $logs_command;
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
     * @param array $copy_entry_point
     *   The file copy entry point for the container.
     * @param array $logs_command
     *   The command to retrieve logs for the container.
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
        array $exec_entry_point,
        array $copy_entry_point,
        array $logs_command
    ): DockerContainer {
        return new static(
            $name,
            $namespace,
            $image,
            $status,
            $creation_timestamp,
            $controlled_by,
            $exec_entry_point,
            $copy_entry_point,
            $logs_command
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
    ): CliCommand {
        $this->setTtyInContainerEntryPoint($use_tty);
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
        return $cmd;
    }

    /**
     * Retrieves logs from the container.
     */
    public function logs(): string {
        $cmd = new CliCommand(
            $this->containerLogsCommand,
            'Retrieving logs from container',
            null,
            [],
            null,
            null
        );
        $cmd->mustRun();
        return $cmd->getOutput();
    }

    /**
     * Sets the appropriate TTY flag in the container entry point.
     *
     * @param bool $use_tty
     *   TRUE to attach to a TTY for the command.
     */
    private function setTtyInContainerEntryPoint(bool $use_tty): void
    {
        if (!$use_tty) {
            foreach ($this->containerExecEntryPoint as $key => $value) {
                if ($value == '-it') {
                    $this->containerExecEntryPoint[$key] = '-i';
                    break;
                }
            }
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

    /**
     * Copies a local filesystem file or directory to the container.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param string $source
     *   The full path to the local filesystem file or directory.
     * @param string $target
     *   The full path to the target in the container.
     */
    public function copyTo(
        DockworkerIO $io,
        string $source,
        string $target
    ): void {
        $target_uri = "$this->containerName:$target";
        $this->copyCmd(
            $io,
            $source,
            $target_uri
        );
    }

    /**
     * Copies a container file or directory to the local filesystem.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param string $source
     *   The full path to the file or directory in the container.
     * @param string $target
     *   The full path to local target.
     */
    public function copyFrom(
        DockworkerIO $io,
        string $source,
        string $target
    ): void {
        $source_uri = "$this->containerName:$source";
        $this->copyCmd(
            $io,
            $source_uri,
            $target
        );
    }

    /**
     * Copies files to/from the container.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     * @param string $source_uri
     *   The full uri of the source file.
     * @param string $target_uri
     *   The full uri of the target file.
     */
    protected function copyCmd(
        DockworkerIO $io,
        string $source_uri,
        string $target_uri
    ): void {
        $command = array_merge(
            $this->containerCopyEntryPoint,
            [
                $source_uri,
                $target_uri,
            ]
        );
        $cmd = new CliCommand(
            $command,
            "Copying $source_uri to $target_uri",
            null,
            [],
            null,
            null
        );
        $cmd->runTty($io);
    }
}
