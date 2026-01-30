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

namespace WPPF\CLI\Command;

use WPPF\CLI\Static\HelperBundle;
use WPPF\CLI\Static\StyleUtil;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * A command to bump the framework version.
 */
#[AsCommand(
	description: 'Bump the framework version across all files.',
	name: 'framework:version:upgrade'
)]
final class FrameworkVersionUpgradeCommand extends Command
{
	/**
	 * Set up the helper variables, control user message flow.
	 *
	 * @param InputInterface $input The terminal input interface.
	 * @param OutputInterface $output The terminal output interface.
	 *
	 * @return int The command success status.
	 */
	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		// Set up command variables
		$bundle = new HelperBundle( new QuestionHelper, $input, $output );
		$currentVersion = self::readPackageVersion();

		// Ensure the current framework version is available in composer.json
		if ( $currentVersion !== null ) {
			$output->writeln(
				StyleUtil::color(
					sprintf( 'Current package version: %s', $currentVersion ),
					'yellow'
				)
			);
		} else {
			$message = 'There was an error parsing the current version from .';
			$output->writeln( StyleUtil::error( $message ) );
			return Command::FAILURE;
		}

		// Get the new version from the user
		$newVersion = self::askVersion( $bundle );

		// Check the user-provided version is formatted correctly
		if ( ! self::isValidVersionFormat( $newVersion ) ) {
			$message = 'Provided version is not in the correct format.';
			$output->writeln( StyleUtil::error( $message ) );
			return Command::FAILURE;
		}

		// Check that the new version is greater than the old
		if ( ! self::isNewerVersion( $newVersion, $currentVersion ) ) {
			$message = 'Provided version is not newer than the old version.';
			$output->writeln( StyleUtil::error( $message ) );
			return Command::FAILURE;
		}

		$output->writeln( StyleUtil::color( $newVersion, 'green' ) );

		// Update the versions in each file
		try {
			self::updateComposerVersion( $newVersion );
			self::updateFrameworkVersionConstant( $newVersion );
			self::updateNamespaceVersions( $newVersion );
		} catch ( \RuntimeException $e ) {
			$output->writeln( StyleUtil::error( $e->getMessage() ) );
			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}

	/**
	 * Prompt for the new version string.
	 *
	 * @param HelperBundle $bundle The question helper and IO interfaces for interactive user data collection.
	 *
	 * @return string The version entered by the user.
	 */
	private static function askVersion( HelperBundle $bundle ): string
	{
		$question = new Question(
			StyleUtil::color( 'Enter the version to bump to (e.g. "1.0.0"):', 'cyan' ) . ' '
		);

		return strval( $bundle->helper->ask( $bundle->input, $bundle->output, $question ) );
	}

	/**
	 * Read the current version from composer.json.
	 *
	 * @return string|null The current version, or null if missing.
	 */
	private static function readPackageVersion(): ?string
	{
		$path = sprintf( '%s/composer.json', getcwd() );

		if ( ! file_exists( $path ) ) {
			return null;
		}

		$composerJson = json_decode( file_get_contents( $path ), true );

		if ( ! is_array( $composerJson ) || ! isset( $composerJson['version'] ) ) {
			return null;
		}

		return strval( $composerJson['version'] );
	}

	/**
	 * Check whether the version matches the expected format.
	 *
	 * @param string $version The version input to validate.
	 *
	 * @return bool True if valid, false otherwise.
	 */
	private static function isValidVersionFormat( string $version ): bool
	{
		return boolval( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}$/', $version ) );
	}

	/**
	 * Check whether the new version is greater than the current version.
	 *
	 * @param string $newVersion The proposed version string.
	 * @param string $currentVersion The current version string.
	 *
	 * @return bool True if new version is greater, false otherwise.
	 */
	private static function isNewerVersion( string $newVersion, string $currentVersion ): bool
	{
		return version_compare( $newVersion, $currentVersion, '>' );
	}

	/**
	 * Update the version field in the composer.json file.
	 *
	 * @param string $newVersion The new version string.
	 * 
	 * @throws \RuntimeException Throws an exception if the requested file or version don't exist.
	 * @return bool True if the write operation succeeded.
	 */
	private static function updateComposerVersion( string $newVersion ): bool
	{
		$path = sprintf( '%s/composer.json', getcwd() );

		if ( ! file_exists( $path ) ) {
			throw new \RuntimeException( "File composer.json was not found" );
		}

		$contents = file_get_contents( $path );

		// Find and replace 1 instance of "version" in the file contents
		$updated = preg_replace(
			'/"version"\s*:\s*"[^"]*"/',
			sprintf( '"version": "%s"', $newVersion ),
			$contents,
			1,
			$count
		);

		// Confirm something was replaced
		if ( 0 === $count ) {
			throw new \RuntimeException( "Version field not found in composer.json" );
		}

		return file_put_contents( $path, $updated );
	}

	/**
	 * Update the framework version constant in the framework class.
	 *
	 * @param string $newVersion The new version string.
	 * 
	 * @throws \RuntimeException Throws an exception if the framework file or version don't exist.
	 * @return bool True if the framework file is written out.
	 */
	private static function updateFrameworkVersionConstant( string $newVersion ): bool
	{
		$path = sprintf( '%s/includes/modules/framework-module/includes/classes/class-framework.php', getcwd() );

		if ( ! file_exists( $path ) ) {
			throw new \RuntimeException( "Framework class file not found." );
		}

		$contents = file_get_contents( $path );

		// Find and replace 1 instance of "VERSION" in the file contents
		$updated = preg_replace(
			"/const VERSION = '[^']+';/",
			sprintf( "const VERSION = '%s';", $newVersion ),
			$contents,
			1,
			$count
		);

		// Confirm something was replaced
		if ( 0 === $count ) {
			throw new \RuntimeException( "Framework version constant not found." );
		}

		return file_put_contents( $path, $updated );
	}

	/**
	 * Replace all WPPF namespace version references across PHP files.
	 *
	 * @param string $newVersion The new version string.
	 */
	private static function updateNamespaceVersions( string $newVersion ): void
	{
		// Replace the version dots with underscores.
		$newVersion = str_replace( '.', '_', $newVersion );
		$replacement = sprintf( 'WPPF\\v%s', $newVersion );

		// Iterate over all files in the CWD
		$directory = new \RecursiveDirectoryIterator( getcwd(), \FilesystemIterator::SKIP_DOTS );
		$iterator = new \RecursiveIteratorIterator( $directory );

		// Search for PHP files
		foreach ( $iterator as $file ) {
			if ( $file->isDir() || $file->getExtension() !== 'php' && $file->getExtension() !== 'tpl' ) {
				continue;
			}

			// Load PHP files, do a quick search, and save them if there are any changes.
			$path = $file->getPathname();
			$contents = file_get_contents( $path );

			$updated = preg_replace(
				'/WPPF\\\\v\d{1,3}_\d{1,3}_\d{1,3}/',
				$replacement,
				$contents,
				-1,
				$count
			);

			if ( 0 < $count ) {
				file_put_contents( $path, $updated );
			}
		}
	}

}
