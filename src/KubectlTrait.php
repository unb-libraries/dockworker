<?php

namespace Dockworker;

use Robo\Robo;

/**
 * Defines trait for interacting with KubeCtl.
 */
trait KubectlTrait {

  /**
   * The path to the kubectl bin.
   *
   * @var string
   */
  protected $kubeCtlBin = NULL;

  /**
   * Test kubectl is installed/executable.
   *
   * @hook pre-init
   */
  public function checkKubeCtlBinExists() {
    $this->kubeCtlBin = trim(shell_exec(sprintf("which %s", 'kubectl')));
    if (empty($this->kubeCtlBin)) {
      throw new \Exception("kubectl binary not found.");
    }
  }

  /**
   * Test kubectl is installed/executable.
   *
   * @hook post-init
   */
  public function checkKubeCtlConnection() {
    exec($this->kubeCtlBin . ' api-resources', $output, $return_code);
    if ($return_code != 0) {
      throw new \Exception("kubectl connection to the server failed.");
    }
  }

  /**
   * Execute a kubectl Command.
   *
   * @param string $command
   *   The kubectl command to execute (i.e. ls)
   * @param string[] $args
   *   A list of arguments to pass to kubectl.
   * @param bool $print_output
   *   TRUE if the command should output results. False otherwise.
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
