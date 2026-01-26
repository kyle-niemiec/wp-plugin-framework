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
		 * @var array A map of class filenames to directories they may be found in.
		 */
		protected static $class_map = array();

		/**
		 * @var array A list of module directories already scanned.
		 */
		protected static $scanned_modules = array();

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
		 * An alias function for linking our class search function to the SPL autoload list.
		 */
		protected static function register_spl_autoload_function() {
			spl_autoload_register( array( __CLASS__, 'search_for_class_file' ) );
		}

		/**
		 * Read a module directory and cache class file locations.
		 * 
		 * @param string $directory The module directory to scan.
		 * @param string[] $includable_directories A list of includable directories under /includes/.
		 * 
		 * @return bool Whether or not the directory was scanned.
		 */
		public function add_module_directory( string $directory, array $includable_directories ) {
			$directory = rtrim( $directory, '/' );

			if ( ! is_dir( $directory ) ) {
				return false;
			}

			// Ensure this module has not been autoloaded already
			if ( isset( self::$scanned_modules[ $directory ] ) ) {
				return false;
			}

			self::$scanned_modules[ $directory ] = true;

			// Scan the module directory itself (non-recursive).
			self::scan_directory_files( $directory );

			$includes_dir = sprintf( '%s/%s', $directory, Module::$includes_dir );

			if ( is_dir( $includes_dir ) ) {
				// Scan includable directories recursively.
				foreach ( $includable_directories as $includable_dir ) {
					$include_path = sprintf( '%s/%s', $includes_dir, $includable_dir );

					if ( is_dir( $include_path ) ) {
						self::scan_directory_recursive( $include_path );
					}
				}

				// Scan modules directory and recurse into module folders.
				$modules_dir = sprintf( '%s/%s', $includes_dir, Module::$modules_dir );

				if ( is_dir( $modules_dir ) ) {
					self::add_module_directory( $modules_dir, $includable_directories );
					$module_folders = Utility::scandir( $modules_dir, 'folders' );

					foreach ( $module_folders as $module_folder ) {
						$module_path = sprintf( '%s/%s', $modules_dir, $module_folder );
						self::add_module_directory( $module_path, $includable_directories );
					}
				}
			}

			return true;
		}

		/**
		 * Search for a class file via the cached class map.
		 * 
		 * @param string $search_class The class name to search for.
		 */
		public static function search_for_class_file( string $search_class ) {
			// Form the name of the file we're searching for.
			$search_class_slug = Utility::slugify( Utility::class_basename( $search_class ) );
			$search_filename = sprintf( 'class-%s.php', $search_class_slug );

			// Check if any such file name possibility has been registered
			if ( ! isset( self::$class_map[ $search_filename ] ) ) {
				return;
			}

			$search_version = Utility::get_namespace_version( $search_class );

			// Search through all of the directories associated with the filename
			foreach ( self::$class_map[ $search_filename ] as $directory ) {
				$found_file = trailingslashit( $directory ) . $search_filename;

				$found_class = sprintf(
					'%s\%s',
					Utility::get_file_namespace( $found_file ),
					Utility::get_file_class_name( $found_file )
				);

				$found_class = ltrim( $found_class, '\\' );
				$search_class = ltrim( $search_class, '\\' );

				if ( $found_class === $search_class ) {
					// If the full paths match, load it
					require( $found_file );
					break;
				} else if ( is_int( array_search( $search_version, Framework::COMPATIBILITY_VERSIONS ) ) ) {
					// Check if replacing the namespace version with a supported one matches
					$found_version = Utility::get_namespace_version( $found_class );
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
						continue;
					}
				}
			}
		}

		/**
		 * Scan a directory (non-recursive) and map any class files found.
		 * 
		 * @param string $directory The directory to scan.
		 */
		private static function scan_directory_files( string $directory ) {
			if ( ! is_dir( $directory ) ) {
				return;
			}

			$files = Utility::scandir( $directory, 'files' );

			foreach ( $files as $file ) {
				self::map_class_file( $directory, $file );
			}
		}

		/**
		 * Recursively scan a directory and map any class files found.
		 * 
		 * @param string $directory The directory to scan.
		 */
		private static function scan_directory_recursive( string $directory ) {
			$directory = rtrim( $directory, '/' );

			if ( ! is_dir( $directory ) ) {
				return;
			}

			$items = Utility::scandir( $directory );

			foreach ( $items as $item ) {
				$item_path = sprintf( '%s/%s', $directory, $item );

				if ( is_dir( $item_path ) ) {
					// Scan a directory
					self::scan_directory_recursive( $item_path );
				} else if ( is_file( $item_path ) ) {
					// Scan a file
					self::map_class_file( $directory, $item );
				}
			}
		}

		/**
		 * Add a class file to the class map if it matches the expected naming convention.
		 * 
		 * @param string $directory The directory where the file is located.
		 * @param string $file The file name.
		 */
		private static function map_class_file( string $directory, string $file ) {
			if ( ! preg_match( '/^class-([a-z-\.0-9]+)\.php$/i', $file ) ) {
				return;
			}

			$directory = rtrim( $directory, '/' );

			if ( ! isset( self::$class_map[ $file ] ) ) {
				self::$class_map[ $file ] = array();
			}

			if ( ! in_array( $directory, self::$class_map[ $file ] ) ) {
				self::$class_map[ $file ][] = $directory;
			}
		}

	}

}
