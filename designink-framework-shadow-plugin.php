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

namespace DesignInk\WordPress\Framework\v1_1_2;

defined( 'ABSPATH' ) or exit;

use DesignInk\WordPress\Framework\v1_1_2\Plugin;

if ( ! class_exists( '\DesignInk\WordPress\Framework\v1_1_2\DesignInk_Framework_Shadow_Plugin', false ) ) {

	/**
	 * The 'shadow' plugin for the framework that will control the loading of crucial modules.
	 */
	final class DesignInk_Framework_Shadow_Plugin extends Plugin { }

	// Start it up
	DesignInk_Framework_Shadow_Plugin::instance();

}
