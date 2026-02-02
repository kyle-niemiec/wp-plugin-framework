<?php
/**
 * Configuration information relating to the @see WPPF\v1_2_1\WordPress\Admin\Screens pertaining to a specific custom post type.
 */

defined( 'ABSPATH' ) or exit;

use WPPF\v1_2_1\WordPress\Admin\Screens\Post_Screens;

if ( ! class_exists( '{{class_name}}', false ) ) {

	/**
	 * This class contains methods which allow code to run on certain custom post type screens.
	 */
	final class {{class_name}} extends Post_Screens
	{
		/**
		 * @inheritDoc
		 */
		final public static function post_type(): string { return {{post_type_class}}::post_type(); }

		/**
		 * @inheritDoc
		 */
		final public static function construct_screen(): void { }

		/**
		 * The primary entry function for running code locally in the screen. This code will run in the 'current_screen' WordPress hook.
		 * This function will be called when any page from the valid screens is matched.
		 * 
		 * @param \WP_Screen $current_screen The current Screen being viewed.
		 */
		final public static function current_screen( \WP_Screen $current_screen ): void
		{
			// Runs for any matching post screen
		}

		/**
		 * This code will run when you are viewing/editing the single post of the custom post type.
		 * 
		 * @param \WP_Screen The current screen object.
		 */
		final public static function view_post( \WP_Screen $current_screen ): void
		{
			// Runs when viewing/editing a post
		}

		/**
		 * This code will run when you are viewing all posts of the custom post type.
		 * 
		 * @param \WP_Screen The current screen object.
		 */
		final public static function view_posts( \WP_Screen $current_screen ): void
		{
			// Runs when viewing all posts
		}

		/**
		 * This code will run on the screen when you are adding a post.
		 * 
		 * @param \WP_Screen The current screen object.
		 */
		final public static function add_post( \WP_Screen $current_screen ): void
		{
			// Runs when adding a new post
		}

	}

}
