<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Dockworker\PersistentGlobalDockworkerConfigTrait;

/**
 * Provides methods to interact with kubectl.
 */
trait KubectlTrait {

  use PersistentGlobalDockworkerConfigTrait;

  /**
   * The path to the kubectl bin.
   *
   * @var string
   */
  protected $kubeCtlBin;

  /**
   * The current user's configured kubectl username.
   *
   * @var string
   */
  protected $kubeUserName;

  /**
   * The current user's configured kubectl access token.
   *
   * @var string
   */
  protected $kubeUserToken;

  /**
   * Tests if kubectl is installed/executable.
   *
   * @hook init @kubectl
   * @throws \Dockworker\DockworkerException
   */
  public function checkKubeCtlBinExists() {
    $this->kubeCtlBin = $this->getSetGlobalDockworkerConfigItem(
      'dockworker.kubectl.bin',
      "Enter the full path to kubectl",
      $this->io(),
      '/snap/bin/kubectl',
      'DOCKWORKER_KUBECTL_BIN'
    );

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
        if (isset($o[1]) && str_contains($o[1], 'i/o timeout') && $try_count < $max_retries) {
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

  /**
   * Sets the running user's kubernetes credentials.
   *
   * @throws \Dockworker\DockworkerException
   */
  protected function setKubectlUserDetails() {
    if (!empty($this->kubeCtlBin) && is_executable($this->kubeCtlBin)) {
      $user_name_cmd = $this->kubeCtlBin . ' config view --raw --output jsonpath=\'{$.users[0].name}\'';
      $this->kubeUserName = shell_exec($user_name_cmd);
      $user_token_cmd = $this->kubeCtlBin . ' config view --raw --output jsonpath=\'{$.users[0].user.token}\'';
      $this->kubeUserToken = shell_exec($user_token_cmd);
    }
  }

  /**
   * Determines if the current user has k8s details defined.
   *
   * @return bool
   */
  protected function kubectlUserDetailsDefined() {
    return (!empty($this->kubeUserName) && !empty($this->kubeUserToken));
  }

}

