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

/**
 * This file holds initialization code for loading the framework and making it globally accessible.
 */

defined( 'ABSPATH' ) or exit;

use WPPF\v1_2_2\Framework\Autoloader;
use WPPF\v1_2_2\Framework\Admin_Module;
use WPPF\v1_2_2\Framework\Framework;
use WPPF\v1_2_2\Framework\Module;

global $WPPF_FRAMEWORKS;

/**
 * Require the basic class files to spin up the autoloader.
 */
if ( ! class_exists( '\WPPF\v1_2_2\Autoloader', false ) ) {
	require_once ( plugin_dir_path( __FILE__ ) . 'includes/modules/framework-module/includes/classes/class-autoloader.php' );
	require_once ( plugin_dir_path( __FILE__ ) . 'includes/modules/framework-module/includes/abstracts/class-module.php' );
	require_once ( plugin_dir_path( __FILE__ ) . 'includes/modules/plugin-module/includes/modules/upgrader/includes/traits/class-plugin-upgrader-trait.php' );
	require_once ( plugin_dir_path( __FILE__ ) . 'includes/modules/wordpress-module/includes/abstracts/class-plugin.php' );
	require_once ( plugin_dir_path( __FILE__ ) . 'includes/modules/framework-module/includes/abstracts/class-admin-module.php' );

	Autoloader::instance()->add_module_directory( __DIR__, Module::$includes );
	Autoloader::instance()->add_module_directory( sprintf( '%s/admin', __DIR__ ), Admin_Module::$includes );
}

/**
 * Set global function for accessing Framework instances.
 * 
 * @return \WPPF\v1_2_2\Framework[] The WordPress plugin frameworks by version.
 */
if ( ! function_exists( 'wppf_frameworks' ) ) {

	$WPPF_FRAMEWORKS = array();

	function wppf_frameworks() {
		global $WPPF_FRAMEWORKS;
		return $WPPF_FRAMEWORKS;
	}

}

/**
 * Instantiate the current Framework version and add to the Frameworks list.
 */
if ( ! class_exists( '\WPPF\v1_2_2\Framework', false ) ) {
	$WPPF_FRAMEWORKS[ Framework::get_version() ] = Framework::instance();
}

/**
 * Initialize the shadow plugin
 */
if ( ! class_exists( '\WPPF\v1_2_2\WPPF_Shadow_Plugin', false ) ) {
	require_once( __DIR__ . '/wppf-shadow-plugin.php' );
}
