<?php
/**
 * A @see WPPF\v1_2_1\WordPress\Post_Meta containing configuration and schema information for meta data.
 */

defined( 'ABSPATH' ) or exit;

use WPPF\v1_2_1\WordPress\Meta_Schema;
use WPPF\v1_2_1\WordPress\Post_Meta;

if ( ! class_exists( '{{class_name}}', false ) ) {

	/**
	 * A class for managing custom post type Meta data.
	 */
	final class {{class_name}} extends Post_Meta {

		/** @var {{var_type}} */
		public {{var_type}} ${{var_name}};

		/** @var array Default Meta values. */
		private static $default_values = array(
			'{{var_name}}' => {{var_type}},
		);

		/**
		 * The required abstraction function key()
		 * 
		 * @return string The meta key.
		 */
		final public static function key() { return '_wppf_test_post_data'; }

		/**
		 * Constructs the Post Series Meta.
		 * 
		 * @param \WP_Post $Post The parent WPPF Test Post the Meta values belong to.
		 */
		public function __construct( \WP_Post $Post ) {

			// Set Meta schema
			$this->set_schema( new Meta_Schema( 'array', array(
					'current_string'	=> new Meta_Schema(
						'string', '/^[a-zA-Z0-9 ]+$/', array( 'pattern_hint' => __( "Only letters, numbers, and spaces are allowed." ) )
					),
					'is_toggle_active'	=> new Meta_Schema( 'boolean' ),
					'times_saved'		=> new Meta_Schema( 'array', new Meta_Schema( 'integer' ) ),
				) )
			);

			if ( ! $Post ) {
				$message = sprintf( "No valid Post was passed to the %s constructor.", self::class );
				throw new \Exception( $message );
			}

			foreach ( self::$default_values as $property => $value ) {
				if ( property_exists( $this, $property ) ) {
					$this->{ $property } = $value;
				}
			}

			parent::__construct( $Post );
		}

		/**
		 * The required abstract called when saving the Meta. This function returns what is saved.
		 * 
		 * @return array The array representation of the Meta.
		 */
		final public function export() {
			$export = array();

			foreach ( self::$default_values as $property => $default_value ) {
				if ( isset( $this->{ $property } ) ) {
					$export[ $property ] = $this->{ $property };
				}

				else {
					$export[ $property ] = $default_value;
				}
			}

			return $export;
		}

	}

}
