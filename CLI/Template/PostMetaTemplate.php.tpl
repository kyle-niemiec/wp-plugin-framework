<?php
/**
 * A {@see use WPPF\v1_2_2\WordPress\Post_Meta} for a custom post type, containing configuration and schema information for meta data.
 */

defined( 'ABSPATH' ) or exit;

use WPPF\v1_2_2\WordPress\Meta_Schema;
use WPPF\v1_2_2\WordPress\Post_Meta;

if ( ! class_exists( '{{class_name}}', false ) ) {

	/**
	 * A class for managing custom post type meta data.
	 */
	final class {{class_name}} extends Post_Meta
	{
		{{class_properties}}

		/** @var array The default values for the user-defined meta data. */
		private static $generated_values = [
			{{default_values}}
		];

		/**
		 * @inheritDoc
		 */
		final public static function key(): string { return '_{{class_slug}}'; }

		/**
		 * Construct the meta data class with a given custom post type.
		 * 
		 * @param \WP_Post $Post The custom post type the {@see use WPPF\v1_2_2\WordPress\Post_Meta} values belong to.
		 */
		public function __construct( \WP_Post $Post )
		{
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
		 * This is called when saving the custom post type meta data and returns what is saved.
		 * 
		 * @return array The array representation of the {@see use WPPF\v1_2_2\WordPress\Post_Meta}.
		 */
		final public function export(): array
		{
			$export = [];

            // Set all meta data values to the export object
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
