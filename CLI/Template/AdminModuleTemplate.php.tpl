<?php
/**
 * The @see WPPF\v1_2_1\Framework\Module responsible for coordinating functionality in the admin dashboard.
 */

defined( 'ABSPATH' ) or exit;

use WPPF\v1_2_1\Framework\Admin_Module;

if ( ! class_exists( '{{module_class_name}}', false ) ) {

	/**
	 * The admin module for {{module_class_name}}.
	 */
	final class {{module_class_name}} extends Admin_Module { }

}
