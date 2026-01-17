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

use WPPF\v1_1_2\Framework;
use WPPF\v1_1_2\Autoloader;

global $DESIGNINK_FRAMEWORKS;

/**
 * The Autoloader is really all we need to start calling things up, so fire it up if it hasn't been.
 */
if ( ! class_exists( '\WPPF\v1_1_2\Autoloader', false ) ) {
	require_once ( plugin_dir_path( __FILE__ ) . 'includes/classes/class-autoloader.php' );
	Autoloader::instance()->autoload_directory_recursive( __DIR__ . '/includes' );
}

/**
 * Set global function for accessing Framework instances.
 * 
 * @return \WPPF\v1_1_2\Framework[] The DesignInk WordPress frameworks by version.
 */
if ( ! function_exists( 'designink_frameworks' ) ) {

	$DESIGNINK_FRAMEWORKS = array();

	function designink_frameworks() {
		global $DESIGNINK_FRAMEWORKS;
		return $DESIGNINK_FRAMEWORKS;
	}

}

/**
 * Instantiate the current Framework version and add to the Frameworks list.
 */
if ( ! class_exists( '\WPPF\v1_1_2\Framework', false ) ) {
	$DESIGNINK_FRAMEWORKS[ Framework::get_version() ] = Framework::instance();
}

/**
 * Initialize the shadow plugin
 */
if ( ! class_exists( '\WPPF\v1_1_2\DesignInk_Framework_Shadow_Plugin', false ) ) {
	require_once( __DIR__ . '/designink-framework-shadow-plugin.php' );
}
