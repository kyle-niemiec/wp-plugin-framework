<?php
/**
 * This file manages the configuration for a custom @see WPPF\v1_2_2\Module.
 */

defined( 'ABSPATH' ) or exit;

use WPPF\v1_2_2\Framework\Module;

if ( ! class_exists( '{{class_name}}', false ) ) {

	/**
	 * A module to contain feature-specific functionality.
	 */
	final class {{class_name}} extends Module {

		/**
		 * The module entry point.
		 */
		final public static function construct() {
			add_action( 'init', array( __CLASS__, '_init' ) );
		}

		/**
		 * The WordPress 'init' action hook.
		 */
		final public static function _init() {
			// Perform init hook actions...
		}

	}

}
