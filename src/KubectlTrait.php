<?php

namespace Dockworker;

use Dockworker\DockworkerException;

/**
 * Provides methods to interact with kubectl.
 */
trait KubectlTrait {

  /**
   * The path to the kubectl bin.
   *
   * @var string
   */
  protected $kubeCtlBin;

  /**
   * Tests if kubectl is installed/executable.
   *
   * @hook pre-init @kubectl
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
   * @hook post-init @kubectl
   * @throws \Dockworker\DockworkerException
   */
  public function checkKubeCtlConnection() {
    $this->kubectlExec('api-resources', [], FALSE, FALSE);
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
   * @throws \Dockworker\DockworkerException
   *
   * @return string
   *   The output of the execution.
   */
  private function kubectlExec($command, $args = [], $print_output = TRUE, $print_command_string = TRUE) {
    $o = '';
    $r = '';
    $args_string = implode(' ', $args);
    $max_retries = 5;
    $try_count = 0;

    while (TRUE) {
      $try_count++;
      $command_string = "{$this->kubeCtlBin} $command $args_string";
      if ($print_command_string == TRUE) {
        $this->io()->text("Executing: $command_string");
      }

      exec("$command_string 2>&1", $o, $r );
      if ($r == 0) {
        if ($print_output) {
         $this->say(implode("\n", $o));
        }
        break;
      }
      else {
        if (isset($o[1]) && strpos($o[1], 'i/o timeout') !== FALSE && $try_count < $max_retries) {
          $this->io()->text("Connection to kubectl server timed out. Retrying... [$try_count/$max_retries]");
        }
        else {
          $error_string = implode("\n", $o);
          throw new DockworkerException("kubectl connection to the server failed: $error_string");
        }
      }
    }
    return implode("\n", $o);
  }

}

