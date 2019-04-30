<?php

namespace DockWorker;

use Robo\Common\ConfigAwareTrait;
use Robo\Config;
use Robo\Robo;
use Robo\Runner as RoboRunner;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Dockworker {

  const APPLICATION_NAME = 'My Application';
  const REPOSITORY = 'org/project';

  use ConfigAwareTrait;

  private $runner;

  public function __construct(
    Config $config,
    InputInterface $input = NULL,
    OutputInterface $output = NULL
  ) {

    // Create applicaton.
    $this->setConfig($config);
    $application = new Application(self::APPLICATION_NAME, $config->get('version'));

    // Create and configure container.
    $container = Robo::createDefaultContainer($input, $output, $application,
      $config);
    $this->setContainer($container);
    $container->add(MyCustomService::class);

    // Instantiate Robo Runner.
    $this->runner = new RoboRunner([
      My\Custom\Command::class
    ]);
    $this->runner->setContainer($container);
    $this->runner->setSelfUpdateRepository(self::REPOSITORY);
  }

  public function run(InputInterface $input, OutputInterface $output) {
    $status_code = $this->runner->run($input, $output);

    return $status_code;
  }

}