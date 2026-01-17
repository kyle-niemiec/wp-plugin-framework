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

namespace DesignInk\WordPress\Framework\v1_1_2\Plugin;

defined( 'ABSPATH' ) or exit;

use DesignInk\WordPress\Framework\v1_1_2\Module;

if ( ! class_exists( '\DesignInk\WordPress\Framework\v1_1_2\Plugin\Upgrader', false ) ) {

	/**
	 * A Module for providing automated access to running tasks on Plugin version updates.
	 */
	final class Upgrader extends Module { }

}
