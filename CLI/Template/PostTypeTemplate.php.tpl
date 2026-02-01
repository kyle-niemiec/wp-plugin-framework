<?php
/**
 * A custom @see WPPF\v1_2_1\WordPress\Post_Type containing configuration information for related functionality.
 */

defined( 'ABSPATH' ) or exit;

use WPPF\v1_2_1\WordPress\Post_Type;

if ( ! class_exists( '{{class_name}}', false ) ) {

	/**
	 * This class contains the configuration logic for a custom post type.
	 */
	final class {{class_name}} extends Post_Type
	{
		/**
		 * @inheritDoc
		 */
		final public static function post_type() { return '{{slug}}'; }

		/**
		 * Add meta boxes and run code when this class is registered to the framework.
		 */
		final public function __construct()
		{
			parent::__construct();
		}

		/**
		 * The WordPress custom post type options. @see WPPF\v1_2_1\WordPress\Post_Type for the complete list.
		 * 
		 * @return array The custom post type options.
		 */
		final protected function post_type_options()
		{
			return [
				'labels' => [
					'menu_name' => __( '{{menu_name}}' ),
				],
				'singular_name'	=> __( '{{sungular_name}}' ),
				'plural_name'	=> __( '{{plural_name}}' ),
				'public'		=> true,
				'show_in_menu'	=> {{show_in_menu}},
				'show_ui'		=> true,
				'has_archive'	=> true,
				'supports'		=> [ 'title', 'thumbnail' ],
			];
		}

	}

}
