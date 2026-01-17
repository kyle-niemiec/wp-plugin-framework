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

namespace DesignInk\WordPress\Framework\v1_1_2\Action_Scheduler;

defined( 'ABSPATH' ) or exit;

use DesignInk\WordPress\Framework\v1_1_2\Admin\Pages\Management_Page;
use DesignInk\WordPress\Framework\v1_1_2\DesignInk_Framework_Shadow_Plugin;

if ( ! class_exists( '\DesignInk\WordPress\Framework\v1_1_2\Action_Scheduler\Action_Scheduler_Viewer', false ) ) {

	/**
	 * The options page configuration for general settings regarding the modules included in this plugin.
	 */
	final class Action_Scheduler_Viewer extends Management_Page {

		/** @var string The page option group. */
		final public static function page_option_group() { return 'designink-action-scheduler-viewer'; }

		/** @var string The page title. */
		final public static function page_title() { return 'DesignInk Action Viewer'; }

		/** @var string The page menu title. */
		final public static function menu_title() { return 'DesignInk Actions'; }

		/** @var string The page capability. */
		final public static function page_capability() { return 'manage_options'; }

		/**
		 * Construct the parent model. Make sure to call after global $submenu is defined.
		 */
		final public function __construct() {
			if ( ! self::menu_item_exists() ) {
				parent::__construct();
			}
		}
		
		/**
		 * Required inherited function to render the Page.
		 */
		final public static function render() {
			DesignInk_Framework_Shadow_Plugin::instance()->get_admin_module()->get_template( 'action-scheduler-viewer' );
		}

	}

}
