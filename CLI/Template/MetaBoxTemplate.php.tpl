<?php
/**
 * This file manages the configuration for a @see WPPF\v1_2_1\WordPress\Admin\Meta_Box appearing on a post screen.
 */

defined( 'ABSPATH' ) or exit;

use WPPF\v1_2_1\WordPress\Admin\Admin_Notices;
use WPPF\v1_2_1\WordPress\Admin\Meta_Box;
use WPPF\v1_2_1\WordPress\Meta_Schema;

if ( ! class_exists( '{{class_name}}', false ) ) {

	/**
	 * This class contiains the configuration information for a meta-box belonging to {{post_type_class}}
	 */
	final class {{class_name}} extends Meta_Box {

		/**
		 * @inheritDoc
		 */
		final public static function meta_key(): string { return '{{meta_box_key}}'; }

		/**
		 * @inheritDoc
		 */
		final public static function get_title(): string { return '{{meta_box_title}}'; }

		/**
		 * @inheritDoc
		 */
		final public static function get_id(): string { return '{{meta_box_id}}'; }

		/**
		 * @inheritDoc
		 */
		final protected static function render(): void
		{
			{{plugin_class}}::instance()->get_admin_module()->get_template( '{{template_slug}}' );
		}

		/**
		 * This code runs whenever you save a custom post type if this meta box is present. Ideal for saving meta data.
		 * 
		 * @param int $post_id The Post ID.
		 * @param \WP_Post $Post The Post object.
		 * 
		 * @return int The post ID.
		 */
		final protected static function save_post( int $post_id, ?\WP_Post $Post = null ): int
		{
			$is_custom_post = $Post->post_type === {{post_type_class}}::post_type();

			// Don't save anything if the post being saved isn't our post type
			if ( ! $is_custom_post ) {
				return $post_id;
			}
			{{maybe_init_meta}}
			// Read the data sent from the admin page
			$data = self::prepare_data({{maybe_pass_meta}});
			{{maybe_save_meta}}
			return $post_id;
		}

		/**
		 * A function you can use to read and modify the data being sent from the admin page.
		 * {{maybe_pass_args_doc}}
		 * @return array The data to save to the meta.
		 */
		private static function prepare_data({{maybe_pass_args}}): array
		{
			$data = self::get_post_data();
			{{maybe_import_data}}

			// Return the data sent from the admin page
			return $data;
		}

	}

}
