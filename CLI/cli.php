<?php
/**
 * WordPress Plugin Framework
 *
 * Copyright (c) 2008–2026 DesignInk, LLC
 * Copyright (c) 2026 Kyle Niemiec
 *
 * This file is licensed under the GNU General Public License v3.0.
 * See the LICENSE file for details.
 *
 * @package WPPF
 */

namespace WPPF\CLI\Command;

use Symfony\Component\Console\Application;

$application = new Application( 'WP Plugin Framework CLI', '1.0.0' );

// Register commands
$application->add( new CreatePluginAdminCommand );
$application->add( new CreatePluginCommand );
$application->add( new CreatePostTypeCommand );
$application->add( new CreateMetaBoxCommand );
$application->add( new CreatePostTypeMetaCommand );
$application->add( new CreatePostScreenCommand );
$application->add( new FrameworkVersionUpgradeCommand );

// Run the CLI
$application->run();
