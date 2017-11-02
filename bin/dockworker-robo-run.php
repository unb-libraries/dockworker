<?php

/**
 * @file
 * Execute commands via Robo.
 */

use Robo\Robo;

// Discover all commands in Robo Directory.
$discovery = new \Consolidation\AnnotatedCommand\CommandFileDiscovery();
$discovery->setSearchPattern('*Command.php');
$coreClasses = $discovery->discover("$repo_root/vendor/unblibraries/dockworker/src/Robo", 'UnbLibraries\DockWorker\Robo');

$statusCode = Robo::run(
  $_SERVER['argv'],
  $coreClasses,
  'DockWorker',
  '1.0.0'
);

exit($statusCode);
