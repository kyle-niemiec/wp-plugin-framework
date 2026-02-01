<?php
/**
 * A {@see use WPPF\v1_2_1\WordPress\Post_Meta} for a custom post type, containing configuration and schema information for meta data.
 */

defined( 'ABSPATH' ) or exit;

use WPPF\v1_2_1\WordPress\Meta_Schema;
use WPPF\v1_2_1\WordPress\Post_Meta;

if ( ! class_exists( '{{class_name}}', false ) ) {

	/**
	 * A class for managing custom post type {@see use WPPF\v1_2_1\WordPress\Post_Meta} data.
	 */
	final class {{class_name}} extends Post_Meta {
		{{class_properties}}

		/** @var array The user-defined {@see use WPPF\v1_2_1\WordPress\Post_Meta} data. */
		private static $generated_values = array(
			{{default_values}}
		);

		/**
		 * The required abstraction function key()
		 * 
		 * @return string The meta key.
		 */
		final public static function key() { return '_{{class_slug}}'; }

		/**
		 * Constructs the {@see use WPPF\v1_2_1\WordPress\Post_Meta}.
		 * 
		 * @param \WP_Post $Post The custom post type the {@see use WPPF\v1_2_1\WordPress\Post_Meta} values belong to.
		 */
		public function __construct( \WP_Post $Post ) {
			// Set Meta schema
			$this->set_schema( new Meta_Schema( 'array', [
				{{schemas}}
			] ) );

			if ( ! $Post ) {
				$message = sprintf( "No valid Post was passed to the %s constructor.", self::class );
				throw new \Exception( $message );
			}

            // Set the generated variables to the class.
			foreach ( self::$generated_values as $property => $value ) {
				if ( property_exists( $this, $property ) ) {
					$this->{ $property } = $value;
				}
			}

			parent::__construct( $Post );
		}

		/**
		 * This is called when saving the {@see use WPPF\v1_2_1\WordPress\Post_Meta}. This function returns what is saved.
		 * 
		 * @return array The array representation of the {@see use WPPF\v1_2_1\WordPress\Post_Meta}.
		 */
		final public function export() {
			$export = array();

            // Set all {@see use WPPF\v1_2_1\WordPress\Post_Meta} key values to the export object
			foreach ( self::$generated_values as $property => $default_value ) {
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
