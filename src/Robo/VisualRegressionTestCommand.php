<?php

namespace UnbLibraries\DockWorker\Robo;

use Robo\Robo;
use Robo\Tasks;
use UnbLibraries\DockWorker\Robo\DockWorkerCommand;

/**
 * Defines commands in the VisualRegressionTestCommand namespace.
 */
class VisualRegressionTestCommand extends DockWorkerCommand {

  const WARNING_GENERATE_BACKSTOP_FILES_EXIST = 'Visual regression files already exist for %s. Please use visualreg:test or visualreg:approve to add changes.';
  const ERROR_BACKSTOP_FILES_NOT_FOUND = 'No visual regression tests found for branch %s.';

  const ADD_BRANCH_GIT_COMMIT_MESSAGE = 'Add visual regression reference images for %s';
  const APPROVE_CHANGES_GIT_COMMIT_MESSAGE = 'Update visual regression reference images for %s';

  const BACKSTOP_JSON_BOILERPLATE_FILE_URI = 'https://raw.githubusercontent.com/unb-libraries/unbherbarium.lib.unb.ca/dev/tests/backstop/dev/backstop.json';
  const BACKSTOP_JSON_BOILERPLATE_URL_STRING = 'https://dev-unbherbarium.lib.unb.ca/';
  const BACKSTOP_JSON_BOILERPLATE_PROJECT_SLUG = 'unbherbarium_lib_unb_ca';
  const BACKSTOP_JSON_BOILERPLATE_PROJECT_NAME = 'unbherbarium.lib.unb.ca/';

  /**
   * The path to the backstop test directory.
   *
   * @var string
   */
  protected $backstopDir;

  /**
   * The path to the backstop test file.
   *
   * @var string
   */
  protected $backstopFile;

  /**
   * Run the visual regression tests for a branch.
   *
   * @command visualreg:test
   */
  public function visualRegressionTest($branch = NULL) {
    $test_branch = $this->setBackstopFile($branch, FALSE, TRUE);
    $test_results = $this->getExecuteBackstop('test');

    if ($test_results != 0) {
      if ('y' === $this->ask('Tests have failed. Approve the changes? (y/n)')) {
        $approve_results = $this->visualRegressionApprove($test_branch);
        if ($approve_results == 0) {
          if ('y' === $this->ask('Approval successful, commit changes? (y/n)')) {
            // Unstage any files currently staged.
            $this->taskExec('git')
              ->arg('reset')
              ->arg('HEAD')
              ->run();

            // Commit the changes.
            $this->taskExec('git')
              ->arg('add')
              ->arg("{$this->backstopDir}")
              ->run();
            $this->taskExec('git')
              ->arg('commit')
              ->arg('--no-verify')
              ->arg('-m')
              ->arg(
                sprintf(
                  self::APPROVE_CHANGES_GIT_COMMIT_MESSAGE,
                  $test_branch
                )
              )
              ->run();
          }
        }
      }
    }
  }

  /**
   * Approve the previously failed visual regression tests for a branch.
   *
   * @command visualreg:approve
   */
  public function visualRegressionApprove($branch = NULL) {
    $this->setBackstopFile($branch);
    return $this->getExecuteBackstop('approve');
  }

  /**
   * Initially generate the assets for visual regression testing.
   *
   * @command visualreg:generate
   */
  public function visualRegressionGenerate($branch = NULL) {
    $new_branch = $this->setBackstopFile($branch, TRUE);
    if (!$new_branch) {
      $this->say(
        sprintf(
          self::WARNING_GENERATE_BACKSTOP_FILES_EXIST,
          $branch
        )
      );
      return 0;
    }

    $this->say("No visual regression tests found, generating them for $new_branch.");

    $project_name = $this->askDefault('Project name (i.e. unbherbarium.lib.unb.ca)?', Robo::Config()->get('dockworker.instance.name'));
    $project_slug = str_replace('.', '_', $project_name);
    $project_slug = $this->askDefault('Project slug (i.e. unbherbarium_lib_unb_ca)?', $project_slug);
    $test_uri = $this->askDefault('Initial URI to test for this branch?', "https://$project_name");

    // Make the directory.
    $this->taskExec('mkdir')
      ->arg('-p')
      ->arg($this->backstopDir)
      ->run();

    // Init the files.
    $this->getExecuteBackstop('init');

    // Change the permissions on the file.
    $this->say("Changing permissions for $new_branch as root, if prompted enter your local user password.");
    $this->taskExec('sudo')
      ->arg('chmod')
      ->arg('-R')
      ->arg('g+w')
      ->arg($this->backstopDir)
      ->run();

    // Pull in exemplar and boilerplate.
    $exemplar_json = file_get_contents(SELF::BACKSTOP_JSON_BOILERPLATE_FILE_URI);
    $exemplar_json = str_replace(SELF::BACKSTOP_JSON_BOILERPLATE_URL_STRING, $test_uri, $exemplar_json);
    $exemplar_json = str_replace(SELF::BACKSTOP_JSON_BOILERPLATE_PROJECT_SLUG, $project_slug, $exemplar_json);
    $exemplar_json = str_replace(SELF::BACKSTOP_JSON_BOILERPLATE_PROJECT_NAME, $project_name, $exemplar_json);

    // Write out new json.
    file_put_contents($this->backstopFile, $exemplar_json);

    // Run reference generation.
    $reference_results = $this->getExecuteBackstop('reference');

    if ($reference_results == 0) {
      if ('y' === $this->ask('Reference generation successful, commit changes? (y/n)')) {
        // Unstage any files currently staged.
        $this->taskExec('git')
          ->arg('reset')
          ->arg('HEAD')
          ->run();

        // Commit the changes.
        $this->taskExec('git')
          ->arg('add')
          ->arg("{$this->backstopDir}")
          ->run();
        $this->taskExec('git')
          ->arg('commit')
          ->arg('--no-verify')
          ->arg('-m')
          ->arg(
            sprintf(
              self::ADD_BRANCH_GIT_COMMIT_MESSAGE,
              $new_branch
            )
          )
          ->run();
      }
    }

  }

  /**
   * Check backstop status and set up Backstop file properties in object.
   */
  public function setBackstopFile($branch = NULL, $return_new_branch_on_missing = FALSE, $return_branch_on_exists = FALSE) {
    if (empty($branch)) {
      $cur_branch = $this->getGitBranch();
      $branch = $this->askDefault("Branch to target? (dev/live)", $cur_branch);
    }

    $backstop_file = $this->repoRoot . "/tests/backstop/$branch/backstop.json";
    $this->backstopFile = $backstop_file;
    $this->backstopDir = dirname($backstop_file);

    if (!file_exists($backstop_file)) {
      if (!$return_new_branch_on_missing) {
        throw new \Exception(
          sprintf(
            self::ERROR_BACKSTOP_FILES_NOT_FOUND,
            $branch
          )
        );
      }
      else {
        return $branch;
      }
    }
    if (!$return_branch_on_exists) {
      return FALSE;
    }
    else {
      return $branch;
    }
  }

  public function getExecuteBackstop($command = NULL) {
    $docker_bin = 'docker';
    print $this->backstopDir;
    return $this->taskExec($docker_bin)
      ->arg('run')
      ->arg('--rm')
      ->arg('-v')->arg("{$this->backstopDir}:/src")
      ->arg('docksal/backstopjs')
      ->arg($command)
      ->run()
      ->getExitCode();
  }

  protected function getGitBranch() {
    $shellOutput = [];
    exec("cd {$this->repoRoot}; git branch | grep '\*'", $shellOutput);
    foreach ($shellOutput as $line) {
      if (strpos($line, '* ') !== FALSE) {
        return trim(strtolower(str_replace('* ', '', $line)));
      }
    }
    return NULL;
  }

}
