<?php
/**
 * A custom @see WPPF\v1_2_1\WordPress\Post_Type containing configuration information for related functionality.
 */

defined( 'ABSPATH' ) or exit;

use WPPF\v1_2_1\WordPress\Post_Type;

if ( ! class_exists( '{{class_name}}', false ) ) {

	/**
	 * A class to represent and help deal with common plugin functionality.
	 */
	final class {{class_name}} extends Post_Type {

		/**
		 * The lower_underscore_case identifier for the post type.
		 * 
		 * @return string The @see WPPF\v1_2_1\WordPress\Post_Type name.
		 */
		final public static function post_type() { return '{{slug}}'; }

		/**
		 * Add Meta Box and call parent.
		 */
		final public function __construct() {
			parent::__construct();
		}

		/**
		 * The required options from the abstract.
		 * 
		 * @return array The Post Type options.
		 */
		final protected function post_type_options() {
			return array(
				'labels' => array(
					'menu_name' => __( '{{menu_name}}' ),
				),
				'singular_name'	=> __( '{{sungular_name}}' ),
				'plural_name'	=> __( '{{plural_name}}' ),
				'public'		=> true,
				'show_in_menu'	=> {{show_in_menu}},
				'show_ui'		=> true,
				'has_archive'	=> true,
				'supports'		=> array( 'title', 'thumbnail' ),
			);
		}

	}

}
