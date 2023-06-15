<?php

namespace Dockworker\System;

use Dockworker\Cli\CliCommand;
use Dockworker\Core\RoboConfigTrait;
use Dockworker\IO\DockworkerIOTrait;
use Robo\Robo;

/**
 * Provides methods to interact with a local hostfile.
 *
 * @INTERNAL This trait is intended only to be used by Dockworker commands. It
 * references user properties which are not in its own scope..
 */
trait LocalHostFileOperationsTrait
{
    use DockworkerIOTrait;
    use OperatingSystemConfigurationTrait;
    use RoboConfigTrait;

    /**
     * The path to the local OS host file.
     *
     * @var string
     */
    protected string $localHostFilePath = '/etc/hosts';

    /**
     * Adds this application's information into the local development computer's hostfile. Requires sudo.
     *
     * @throws \Dockworker\DockworkerException
     */
    protected function setLocalHostFileEntries(): void
    {
        $this->dockworkerIO->section("[local] Configuring HostFile");
        foreach ($this->getLocalApplicationHostNames() as $hostname) {
            $this->removeLocalHostFileEntry($hostname, true);
            $this->addLocalHostFileEntry($hostname);
        }
    }

    /**
     * Removes this application's information from the local development system's hostfile. Requires sudo.
     *
     * @throws \Dockworker\DockworkerException
     */
    protected function unSetLocalHostFileEntries(): void
    {
        $this->dockworkerIO->section("Reverting Local PC Hostfile");
        foreach ($this->getLocalApplicationHostNames() as $hostname) {
            $this->removeLocalHostFileEntry($hostname);
        }
    }

    /**
     * @param string $hostname
     * @param bool $quiet
     *
     * @throws \Dockworker\DockworkerException
     */
    protected function addLocalHostFileEntry(
        string $hostname,
        bool $quiet = false
    ): void {
        CliCommand::sayRunTestExcept(
            [
                'sudo',
                'bash',
                '-c',
                sprintf(
                    "echo \"127.0.0.1       %s\" >> %s",
                    $hostname,
                    $this->localHostFilePath
                )
            ],
            $this->dockworkerIO,
            $quiet ? '' : "Adding $hostname to hostfile..."
        );
    }


    /**
     * @param string $hostname
     * @param bool $quiet
     *
     * @throws \Dockworker\DockworkerException
     */
    protected function removeLocalHostFileEntry(
        string $hostname,
        bool $quiet = false
    ): void {
        CliCommand::sayRunTestExcept(
            [
                'sudo',
                'bash',
                '-c',
                sprintf(
                    '%s -e "/127.0.0.1       %s/d" %s',
                    $this->getSedInlineInvocation(),
                    $hostname,
                    $this->localHostFilePath
                )
            ],
            $this->dockworkerIO,
            $quiet ? '' : "Removing $hostname from hostfile..."
        );
    }

    /**
     * Gets a list of hostnames that this local deployment will use.
     *
     * @return string[]
     *   An array of hostnames.
     */
    protected function getLocalApplicationHostNames(): array
    {
        $hostnames = [
            "local-$this->applicationName",
        ];
        $additional_hostnames = $this->getConfigItem(
            Robo::Config(),
            'dockworker.workflows.local.additional_hostnames'
        );
        if (!empty($additional_hostnames)) {
            $hostnames = array_merge($hostnames, $additional_hostnames);
        }
        return $hostnames;
    }
}
