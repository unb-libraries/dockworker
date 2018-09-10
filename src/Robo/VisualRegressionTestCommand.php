<?php

namespace UnbLibraries\DockWorker\Robo;

/**
 * Defines commands in the VisualRegressionTestCommand namespace.
 */
class VisualRegressionTestCommand extends DockWorkerCommand {

  const APPROVE_CHANGES_GIT_COMMIT_MESSAGE = 'Update visual regression reference images';
  const ERROR_BACKSTOP_FILES_NOT_FOUND = 'No visual regression tests found.';
  const INITIAL_GENERATE_GIT_COMMIT_MESSAGE = 'Initialize visual regression testing';
  const WARNING_GENERATE_BACKSTOP_FILES_EXIST = 'Visual regression has already been initialized for %s. Use visualreg:add to add new tests or visualreg:test to update existing ones.';

  /**
   * The base URL of the project.
   *
   * @var string
   */
  protected $backstopBaseUrl;

  /**
   * The path to the backstop test directory.
   *
   * @var string
   */
  protected $backstopDir;

  /**
   * The path to the backstop boilerplate file.
   *
   * @var string
   */
  protected $backstopBoilerPlateFile;

  /**
   * The path to the backstop test file.
   *
   * @var string
   */
  protected $backstopFile;

  /**
   * Add a new URI to already initialized visual regression tests.
   *
   * @command visualreg:add
   */
  public function addVisualRegressionTests() {
    if (!$this->getBackStopFilesExist()) {
      $this->say('Backstop has not been set up. Use visualreg:init instead.');
      return 1;
    }

    $this->say('Checking if other tests are clean.');

    $initial_test = $this->visualRegressionTest();
    if ($initial_test != 0) {
      $this->say('Existing tests are failing, visualreg:update them first to proceed.');
      return 1;
    }

    $this->say('Other tests clean, adding new.');
    $backstop = $this->getBackstop();
    $num_old_scenarios = count($backstop->scenarios);

    // Add scenarios
    $this->setAddScenarios($backstop);

    // Write out new json.
    $this->setBackstop($backstop);

    // Test and approve new.
    $this->visualRegressionTest();
    $this->getVisualRegressionApprove();

    // Get list of added tests.
    $added_scenarios = array_slice($backstop->scenarios, $num_old_scenarios);
    $new_scenario_names = [];
    foreach ($added_scenarios as $new_scenario) {
      $new_scenario_names[] = $new_scenario->label;
    }

    $added_scenarios = implode(', ', $new_scenario_names);
    $this->setCommitTestDir("Add $added_scenarios to visual regression tests");
  }

  /**
   * Check if backstop configuration files exist.
   */
  private function getBackStopFilesExist() {
    if (file_exists($this->backstopFile)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Run the visual regression tests.
   *
   * @command visualreg:test
   */
  public function visualRegressionTest() {
    return $this->getExecuteBackstop('test');
  }

  /**
   * Execute a backstop command.
   */
  private function getExecuteBackstop($command = NULL) {
    $docker_bin = 'docker';
    print $this->backstopDir;
    return $this->taskExec($docker_bin)
      ->arg('run')
      ->arg('--rm')
      ->arg("--network={$this->instanceName}")
      ->arg('-v')->arg("{$this->backstopDir}:/src")
      ->arg('docksal/backstopjs')
      ->arg($command)
      ->run()
      ->getExitCode();
  }

  /**
   * Get the current backstop configuration.
   */
  private function getBackstop() {
    return json_decode(
      file_get_contents($this->backstopFile)
    );
  }

  /**
   * Add new scenarios to the backstop object.
   */
  private function setAddScenarios(&$backstop) {
    $continue = TRUE;
    while ($continue == TRUE) {
      $this->setAddScenario($backstop);
      $last_scenario = array_values(array_slice($backstop->scenarios, -1))[0];
      $continue = $this->confirm("{$last_scenario->label} added to tests. Add another (y/n)?");
    }
  }

  /**
   * Add a new scenario to the backstop object.
   */
  private function setAddScenario(&$backstop) {
    $this->say('Adding a new page to test...');
    $test_uri = $this->askDefault('Relative URI of page (i.e. /search - include leading slash)', '/');
    $test_name = $this->askDefault('Label for the test', 'Homepage');
    $boilerplate_scenario = $this->getBackstopScenarioExemplarJson();

    $boilerplate_scenario->label = $test_name;
    $boilerplate_scenario->url = "{$this->backstopBaseUrl}{$test_uri}";
    $backstop->scenarios[] = $boilerplate_scenario;
  }

  /**
   * Get the backstop boilerplate for a scenario.
   */
  private function getBackstopScenarioExemplarJson() {
    $exemplar = $this->getBackstopExemplarJson();
    return $exemplar->scenarios[0];
  }

  /**
   * Get the backstop boilerplate for the entire backstop.json.
   */
  private function getBackstopExemplarJson() {
    return json_decode(
      file_get_contents($this->backstopBoilerPlateFile)
    );
  }

  /**
   * Write the backstop configuration.
   */
  private function setBackstop($backstop) {
    file_put_contents($this->backstopFile, json_encode($backstop, JSON_PRETTY_PRINT));
  }

  /**
   * Approve the previously failed visual regression tests.
   */
  public function getVisualRegressionApprove() {
    return $this->getExecuteBackstop('approve');
  }

  /**
   * Commit any changes in the backstop dir to git.
   */
  private function setCommitTestDir($message = 'Update backstop files.') {
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
      ->arg($message)
      ->run();
  }

  /**
   * Update all visual regression tests to match the current content snapshot.
   *
   * @command visualreg:update
   */
  public function updateVisualRegressionTests() {
    if (!$this->getBackStopFilesExist()) {
      $this->say('Backstop has not been set up. Use visualreg:init instead.');
      return 1;
    }

    $this->say('Checking tests are clean.');
    $initial_test = $this->visualRegressionTest();
    if ($initial_test == 0) {
      $this->say('Existing tests are not failing, nothing to update.');
      return 1;
    }

    // Approve new.
    $this->getVisualRegressionApprove();
    $this->setCommitTestDir("Update visual regression tests to match current state.");
  }

  /**
   * Initially generate the assets for visual regression testing.
   *
   * @command visualreg:init
   */
  public function visualRegressionInit() {
    if ($this->getBackStopFilesExist()) {
      $this->say(
        sprintf(
          self::WARNING_GENERATE_BACKSTOP_FILES_EXIST,
          $this->instanceName
        )
      );
      return 0;
    }

    $this->say("Generating backstop files for instance...");
    $this->backstopBaseUrl = "http://{$this->instanceName}";

    // Make the directory.
    $this->taskExec('mkdir')
      ->arg('-p')
      ->arg($this->backstopDir)
      ->run();

    // Init the files.
    $this->getExecuteBackstop('init');
    $this->setBackStopFilePermissions();
    $backstop = $this->getInitialBackstopJson();

    // Set up ID
    $backstop->id = $this->instanceName;

    // Add scenarios
    $this->setAddScenarios($backstop);

    // Write out new json.
    $this->setBackstop($backstop);

    // Run reference generation.
    $reference_results = $this->setVisualRegressionReference();

    if ($reference_results == 0) {
      if ('y' === $this->ask('Reference generation successful, commit changes? (y/n)')) {
        $this->setCommitTestDir(self::INITIAL_GENERATE_GIT_COMMIT_MESSAGE);
      }
    }
  }

  /**
   * Set the permissions of the backstop file so the user can write it.
   */
  private function setBackStopFilePermissions() {
    // Change the permissions on the file.
    $gid = posix_getgid();
    $this->say("Changing permissions for test files as root, if prompted enter your local user password.");
    $this->taskExec('sudo chgrp')
      ->arg($gid)
      ->arg('-R')
      ->arg($this->backstopDir)
      ->run();
    $this->taskExec('sudo')
      ->arg('chmod')
      ->arg('-R')
      ->arg('g+w')
      ->arg($this->backstopDir)
      ->run();
  }

  /**
   * Get the inital backstop JSON from boilerplate.
   */
  private function getInitialBackstopJson() {
    $exemplar = $this->getBackstopExemplarJson();
    $exemplar->scenarios = [];
    return $exemplar;
  }

  /**
   * Generate reference files for initially deployed tests.
   */
  private function setVisualRegressionReference() {
    // Run reference generation.
    $reference_results = $this->getExecuteBackstop('reference');
  }

  /**
   * Setup the backstop file.
   *
   * @hook init
   */
  public function setBackstopFile() {
    $backstop_file = $this->repoRoot . "/tests/backstop/backstop.json";
    $this->backstopFile = $backstop_file;
    $this->backstopDir = dirname($backstop_file);
    $this->setUpBackstopDir();
  }

  /**
   * Setup the backstop file.
   */
  private function setUpBackstopDir() {
    $backstop_dir = $this->repoRoot . "/tests/backstop";
    if (!file_exists($backstop_dir)) {
      $this->say("Backstop directory [$backstop_dir] missing, creating it");
      mkdir($backstop_dir);
    }

    $gitignore_file = $this->repoRoot . "/vendor/unb-libraries/dockworker/data/backstop/.gitignore";
    $this->say("Updating gitignore file");
    copy($gitignore_file, $this->backstopDir . "/.gitignore");
  }

  /**
   * Setup the backstop boilerplate file.
   *
   * @hook init
   */
  public function setBackstopBoilerplateFile() {
    $this->backstopBoilerPlateFile = $this->repoRoot . "/vendor/unb-libraries/dockworker/data/backstop/backstop.json";
  }

}
