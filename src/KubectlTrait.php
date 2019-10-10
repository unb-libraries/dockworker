<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Robo\Robo;

/**
 * Provides methods to interact with kubectl.
 */
trait KubectlTrait {

  /**
   * The path to the kubectl bin.
   *
   * @var string
   */
  protected $kubeCtlBin = NULL;

  /**
   * Tests if kubectl is installed/executable.
   *
   * @hook pre-init
   * @throws \Dockworker\DockworkerException
   */
  public function checkKubeCtlBinExists() {
    $this->kubeCtlBin = trim(shell_exec(sprintf("which %s", 'kubectl')));
    if (empty($this->kubeCtlBin)) {
      throw new DockworkerException("kubectl binary not found.");
    }
  }

  /**
   * Tests if kubectl can make a connection to the API server.
   *
   * @hook post-init
   * @throws \Dockworker\DockworkerException
   */
  public function checkKubeCtlConnection() {
    exec($this->kubeCtlBin . ' api-resources', $output, $return_code);
    if ($return_code != 0) {
      throw new DockworkerException("kubectl connection to the server failed.");
    }
  }

  /**
   * Executes a kubectl command.
   *
   * @param string $command
   *   The kubectl command to execute.
   * @param string[] $args
   *   A list of arguments to pass to the kubectl command.
   * @param bool $print_output
   *   TRUE if the kubectl command should output results. False otherwise.
   *
   * @return \Robo\ResultData
   *   The result of the execution.
   */
  private function kubectlExec($command, $args = [], $print_output = TRUE) {
    $kube = $this->taskExec($this->kubeCtlBin)
      ->printOutput($print_output)
      ->arg($command);

    if (!empty($args)) {
      foreach ($args as $arg) {
        $kube->arg($arg);
      }
    }

    $this->say(sprintf('Executing kubectl %s...', $command));
    return $kube->run();
  }

}
