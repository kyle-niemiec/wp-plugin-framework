<?php

use WPPF\CLI\Command\CreatePluginCommand;
use Symfony\Component\Console\Application;

$application = new Application( 'WP Plugin Framework CLI', '0.0.1' );

// Register commands
$application->addCommand( new CreatePluginCommand );

// Run the CLI
$application->run();
