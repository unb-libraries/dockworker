<?php

use League\Container\Container;
use Robo\Robo;
use Dockworker\Dockworker;

$input = new \Symfony\Component\Console\Input\ArgvInput($argv);
$output = new \Symfony\Component\Console\Output\ConsoleOutput();
$config = Robo::createConfiguration(['dockworker.yml']);
$app = new Dockworker($config, $input, $output);
$status_code = $app->run($input, $output);
exit($status_code);
