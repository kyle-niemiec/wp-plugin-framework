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

namespace WPPF\v1_2_1\Framework;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\WPPF\v1_2_1\Framework\Autoloader', false ) ) {

	// Require Singleton abstract first thing since the Autoloader extends it and it won't be autoloaded!
	require_once ( __DIR__ . '/../abstracts/class-singleton.php' );

	/**
	 * Autoloader class for managing multiple SPL autoload directories
	 */
	final class Autoloader extends Singleton {

		/**
		 * @var array List of autoload searchable directories
		 */
		protected $autoload_directories = array();

		/**
		 * Protected constructor to prevent more than one instance of autoload directories from being created
		 */
		final protected function __construct() {

			// Check if Utility is loaded sincle this class requires it, but it also does the autoloading \o/.
			if ( ! class_exists( '\WPPF\v1_2_1\Utility', false ) ) {
				$utility_path = __DIR__ . '/../statics/class-utility.php';
				require_once ( $utility_path );
			}

			self::register_spl_autoload_function();
		}

		/**
		 * Checks if the passed directory exists, then adds the directory to the list of autoload locations if it does not exists there already.
		 * 
		 * @param string $directory The directory to be searched for potential new classes.
		 * @return bool Whether or not the directory was successfully added to the autoload array.
		 */
		final public function add_autoload_directory( string $directory ) {
			$is_directory = is_dir( $directory );

			if ( ! $is_directory ) {
				return false;
			}

			if ( ! in_array( $directory, $this->autoload_directories ) ) {
				$this->autoload_directories[] = trailingslashit( $directory );
				return true;
			}

			return false;
		}

		/**
		 * Tranverse a parent folder's structure and add the folder and all subfolders to the Autoloader
		 * 
		 * @param string $directory The absolute directory path from the plugin folder to search. Trailing slashes are not necessary and are removed.
		 * 
		 * @return array A list of all of the folders that were found.
		 */
		final public function autoload_directory_recursive( string $directory ) {
			$folders_found = array();
			$directory = rtrim( $directory, '/' );

			if ( $this->add_autoload_directory( $directory ) ) {
				$folders_found[] = $directory;
			}

			$sub_folders = Utility::scandir( $directory, 'folders' );

			foreach ( $sub_folders as $sub_folder ) {
				$sub_folder_path = sprintf( '%s/%s', $directory, $sub_folder );

				$folders_found = array_merge(
					$folders_found,
					$this->autoload_directory_recursive( $sub_folder_path )
				);
			}

			return $folders_found;
		}

		/**
		 * An alias function for linking our class search function to the SPL autoload list.
		 */
		final protected static function register_spl_autoload_function() {
			spl_autoload_register( array( __CLASS__, 'search_for_class_file' ) );
		}

		/**
		 * A function which, given a fully-qualified class, will look for a matching, slugified filename using the standard WordPress "class-{$class_name}.php" structure.
		 * 
		 * @param string $search_class The name-component of a fully-qualified class to search a manicured list of default class directories for.
		 */
		final protected static function search_for_class_file( string $search_class ) {
			// Form the name of the file we're searching for
			$search_class_slug = Utility::slugify( Utility::class_basename( $search_class ) );
			$search_filename = sprintf( 'class-%s.php', $search_class_slug );

			$search_version = Utility::get_namespace_version( $search_class );

			// Look through all autoload directories for a matching file
			foreach ( self::instance()->autoload_directories as $directory ) {
				$found_file = trailingslashit( $directory ) . $search_filename;

				// If the file name matches, check that the namespace matches
				if ( file_exists( $found_file ) ) {
					$found_class = sprintf(
						'%s\%s',
						Utility::get_file_namespace( $found_file ),
						Utility::get_file_class_name( $found_file )
					);

					$found_version = Utility::get_namespace_version( $found_class );

					if ( $found_class === $search_class ) {
						// If the full paths match, load it
						require( $found_file );
						break;
					} else if ( array_search( $search_version, Framework::COMPATIBILITY_VERSIONS ) ) {
						// Check if replacing the namespace version with a supported one matches
						$supportable_class = str_replace( $found_version, $search_version, $found_class );

						if ( $supportable_class === $search_class ) {
							// If replacing the version matches the desired namespace, create an alias for the found class
							require( $found_file );

							$search_not_exist = ! class_exists( $search_class, false )
											&& ! interface_exists( $search_class, false )
											&& ! trait_exists( $search_class, false );

							$found_exists = class_exists( $found_class, false )
											|| interface_exists( $found_class, false )
											|| trait_exists( $found_class );

							if ( $search_not_exist && $found_exists ) class_alias( $found_class, $search_class, false );
							break;
						} else {
							// If the namespaces don't match, keep looking
							continue;
						}

					}

				}
			}
		}

	}

}
