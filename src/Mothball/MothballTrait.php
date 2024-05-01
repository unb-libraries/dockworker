<?php

namespace Dockworker\Mothball;

use Dockworker\Cli\RSyncCliTrait;
use Dockworker\IO\DockworkerIO;
use Dockworker\System\FileSystemOperationsTrait;

/**
 * Provides methods to authenticate and interact with a GitHub repo.
 */
trait MothballTrait
{
    use FileSystemOperationsTrait;
    use RSyncCliTrait;

    protected $mothballHost;
    protected $mothballPath;
    protected $mothballEnvPath;

    /**
     * Initializes the command and executes all preflight checks.
     *
     * @param \Dockworker\IO\DockworkerIO $io
     *   The IO to use for input and output.
     */
    protected function initRsyncCommand(
        DockworkerIO $io,
    ): void {
        $this->registerRSyncCliTool($io);
    }

    /**
     * Initializes the required bootstrap for a mothball command.
     *
     * @param string $env
     * @return void
     */
    protected function initMothballCommand(string $env): void
    {
        $this->initRsyncCommand($this->dockworkerIO, $env);
        $this->initMothballConfig();
        $this->registerPreflightMothballConnectionTest();
        $this->mothballEnvPath = $this->mothballHost . ':' . $this->mothballPath . '/' . $env;
        // $this->setMothballFiles($env);
    }

    /**
     * Initializes the configuration required for mothball commands.
     *
     * @return void
     */
    protected function initMothballConfig(): void
    {
        $this->mothballHost = $this->getSetDockworkerPersistentDataConfigurationItem(
            'mothball',
            'host',
            'Mothball Hostname',
            'retribution.hil.unb.ca',
            'Enter the hostname to store the mothballed item on. Before adding this value, it is important that you can currently SSH into this server without a password.',
            [],
            'MOTHBALL_SERVER_HOSTNAME'
        );
        $this->mothballPath = $this->getSetDockworkerPersistentDataConfigurationItem(
            'mothball',
            'path',
            'Mothball Root Path on Host',
            "/mnt/storage0/mothball",
            'Enter the path on the mothball host where the mothballs for this application are stored.',
            [],
            'MOTHBALL_SERVER_PATH'
        );
    }

    /**
     * Sets the mothball files from the storage server.
     *
     * @param string $env
     *  The environment to retrieve the mothball files for.
     */
    protected function setMothballFiles(string $env): void
    {

    }

    /**
     * Registers a preflight check to ensure that the snapshot server is accessible.
     */
    protected function registerPreflightSnapshotConnectionTest(): void
    {
        $this->registerNewPreflightCheck(
            'Testing connection to snapshot server',
            $this->getCliToolPreflightCheckCommand(
                $this->cliTools['rsync'],
                [
                    $this->mothballHost . ':/',
                ],
                'rsync',
                5.0
            ),
            'mustRun',
            [],
            'getOutput',
            [],
            'etc',
            sprintf(
                'Could not establish a connection to the snapshot server. Are you on the VPN? Please ensure that you can SSH into the server without a user or password specified in the ssh command (i.e. \'ssh %s\').',
                $this->mothballHost
            )
        );
    }

}
