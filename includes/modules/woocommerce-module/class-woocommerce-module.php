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

namespace WPPF\v1_1_2;

defined( 'ABSPATH' ) or exit;

use WPPF\v1_1_2\Framework\Module;

if ( ! class_exists( '\WPPF\v1_1_2\WooCommerce', false ) ) {

	/**
	 * This Module manages the WooCommerce features.
	 */
	final class WooCommerce extends Module { }

}
