<?php
/**
 * WordPress Plugin Framework
 *
 * Copyright (c) 2008-2026 DesignInk, LLC
 * Copyright (c) 2026 Kyle Niemiec
 *
 * This file is licensed under the GNU General Public License v3.0.
 * See the LICENSE file for details.
 *
 * @package WPPF
 */

namespace WPPF\CLI\Command;

use WPPF\CLI\Util\CliUtil;
use WPPF\CLI\Util\StyleUtil;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WPPF\CLI\Enum\ConsoleColor;
use WPPF\CLI\Support\PluginCliCommand;

/**
 * A command to create an admin module class for the current plugin.
 */
#[AsCommand(
	description: 'Create an admin module class from a template.',
	name: 'make:plugin-admin'
)]
final class CreatePluginAdminCommand extends PluginCliCommand
{
	/** @var bool {@inheritDoc} */
	protected static bool $requiresPlugin = true;

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
		$slug = basename( getcwd() );

		$moduleClassName = sprintf(
			'%s_Admin',
			CliUtil::underscorify( $slug, true )
		);

		// Ensure the admin module file doesn't already exist
		if ( $this->adminModuleExists( $slug ) ) {
			$output->writeln( StyleUtil::error( "Error: The admin module file already exists." ) );
			return Command::FAILURE;
		}

		$output->writeln(
			StyleUtil::color(
				sprintf( "\nCreating admin module class `%s`...", $moduleClassName, $slug ),
				ConsoleColor::BrightCyan
			)
		);

		// Apply the data to the template
		try {
			$template = CliUtil::applyTemplate(
				'AdminModule',
				[
					'{{module_class_name}}' => $moduleClassName,
				]
			);
		} catch ( \RuntimeException $e ) {
			throw $e;
		}

		$output->writeln( '' );

		// Write file
		if ( ! $this->createAdminModuleFile( $template, $slug ) ) {
			$output->writeln( StyleUtil::error( "There was an error writing out the admin module file to disk." ) );
			return Command::FAILURE;
		}

		$output->writeln( StyleUtil::color(
			sprintf( "Admin module file was created at `admin/%s-admin.php`.", $slug ),
			ConsoleColor::BrightGreen
		) );

		$output->writeln( '' );
		return Command::SUCCESS;
	}

	/**
	 * Create the admin module file from the completed template string.
	 *
	 * @param string $template The file contents to write out.
	 * @param string $slug The lower-dash-case slug to use for the file name.
	 *
	 * @return bool The status of the file write operation.
	 */
	private function createAdminModuleFile( string $template, string $slug ): bool
	{
		$outputFile = $this->adminModuleFilePath( $slug );
		$outputDir = dirname( $outputFile );

		if ( ! is_dir( $outputDir ) ) {
			if ( ! mkdir( $outputDir, 0755, true ) && ! is_dir( $outputDir ) ) {
				return false;
			}
		}

		return file_put_contents( $outputFile, $template );
	}

}
