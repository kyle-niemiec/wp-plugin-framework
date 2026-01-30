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

namespace WPPF\CLI\Static;

/**
 * A static class to hold general utilities for console functions.
 */
final class CliUtil
{
	/**
	 * Fetch a desired component template and apply replacement variables to it.
	 * 
	 * @param string $component The capitalized name of the component to fetch a template for.
	 * @param array $replacements An associative array of keys and values which replace corresponding template variables with values.
	 * 
	 * @throws \RuntimeException Will throw an exception if the template is not found or a file error occurs.
	 * @return string The fully formed template with variables dropped in.
	 */
	public static function apply_template( string $component, array $replacements ): string
	{
		// Ensure the requested template exists
		$template_path = __DIR__ . "/../Template/{$component}Template.php.tpl";

		if ( ! file_exists( $template_path ) ) {
			throw new \RuntimeException( 'Plugin template file not found.' );
		}

		// Load template
		$template = file_get_contents( $template_path );

		// Apply replacements
		return str_replace(
			array_keys( $replacements ),
			array_values( $replacements ),
			$template
		);
	}

	/**
	 * Convert a string to an Upper/lower_underscore_case format.
	 * 
	 * @param string $str The string to underscorify.
	 * @param bool $upperCase True if the first letter of each "word" should be uppercase.
	 * 
	 * @return string The underscorified string.
	 */
	public static function underscorify( string $str, bool $upperCase = false ): string
	{
		// Replace everything that isn't a letter or number with an underscore
		$str = preg_replace( '/[^a-zA-Z\\d]/', '_', $str );

		if ( $upperCase ) {
			return ucwords( $str, '_' );
		} else {
			return strtolower( trim( $str, '_' ) );
		}

	}

	/**
	 * Create a validator that can acccept yes/no/y/n.
	 * 
	 * @return callable A callback for the validator to use against text input.
	 */
	public static function yesNoValidator(): callable
	{
		return function ( $value ): string {
			$value = strtolower( trim( strval( $value ) ) );

			if ( ! in_array( $value, array( 'yes', 'no', 'y', 'n' ) ) ) {
				throw new \RuntimeException( 'Please answer (y)es or (n)o.' );
			}

			return $value;
		};
	}

}
