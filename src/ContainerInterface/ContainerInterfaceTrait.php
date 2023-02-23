<?php

namespace Dockworker\ContainerInterface;

/**
 * Provides methods to run commands inside containers regardless of environment.
 */
trait ContainerInterfaceTrait
{
    /**
     * The currently enabled container interface.
     *
     * @var string
     */
    protected string $currentContainerInterface = 'Local';

    /**
     * The currently enabled container namespace.
     *
     * @var string
     */
    protected string $currentContainerNamespace = 'prod';

    protected function containerInterfaceCli(
        array $command,
        string $description,
        ?float $timeout = null
    ): void {
        $this->{"containerInterface{$this->currentContainerInterface}Cli"}(
            $command,
            $description,
            $timeout
        );
    }

    protected function containerInterfaceLocal(
        array $command,
        string $description,
        ?float $timeout = null
    ): void {

    }

    protected function containerInterfaceKubernetes(
        array $command,
        string $description,
        ?float $timeout = null
    ): void {

    }

    /**
     * @return string
     */
    public function getCurrentContainerNamespace(): string {
      return $this->currentContainerNamespace;
    }

    /**
     * @param string $currentContainerNamespace
     */
    public function setCurrentContainerNamespace(string $currentContainerNamespace): void {
      $this->currentContainerNamespace = $currentContainerNamespace;
    }

    /**
     * @return string
     */
    protected function getCurrentContainerInterface(): string {
      return $this->currentContainerInterface;
    }

    /**
     * @param string $currentContainerInterface
     */
    protected function setCurrentContainerInterface(string $interface): void {
      $this->currentContainerInterface = $interface;
    }
}
