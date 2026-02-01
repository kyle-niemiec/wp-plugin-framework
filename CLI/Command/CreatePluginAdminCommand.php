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

use WPPF\CLI\Static\CliUtil;
use WPPF\CLI\Static\StyleUtil;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPPF\CLI\Static\ConsoleColor;
use WPPF\CLI\Static\HelperBundle;

/**
 * A command to create an admin module class for the current plugin.
 */
#[AsCommand(
	description: 'Create an admin module class from a template.',
	name: 'make:plugin-admin'
)]
final class CreatePluginAdminCommand extends Command
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
		$slug = basename( getcwd() );
		$bundle = new HelperBundle( new QuestionHelper, $input, $output );

		$moduleClassName = sprintf(
			'%s_Admin',
			CliUtil::underscorify( $slug, true )
		);

		// Ensure the base plugin exists first
		if ( ! CreatePluginCommand::checkPluginExists( $slug ) ) {
			$output->writeln( StyleUtil::color(
				sprintf( "Main plugin file not found (%s.php)", $slug ),
				ConsoleColor::Yellow )
			);

			if ( self::askCreatePlugin( $bundle ) ) {
				$createPluginCommand = new CreatePluginCommand();
				$createPluginInput = new ArrayInput( [] );
				$createPluginInput->setInteractive( $input->isInteractive() );
				$status = $createPluginCommand->run( $createPluginInput, $output );

				if ( Command::SUCCESS !== $status || ! CreatePluginCommand::checkPluginExists( $slug ) ) {
					$output->writeln( StyleUtil::error(
						"The plugin file was not created. Aborting admin module creation."
					) );

					return Command::FAILURE;
				}
			} else {
				$output->writeln( StyleUtil::color(
					"You must create a plugin before you can create a plugin admin module.",
					ConsoleColor::Yellow
				) );

				return Command::SUCCESS;
			}

		}

		// Ensure the admin module file doesn't already exist
		if ( self::checkAdminModuleExists( $slug ) ) {
			$output->writeln( StyleUtil::error( "Error: The admin module file already exists." ) );
			return Command::FAILURE;
		}

		$output->writeln(
			StyleUtil::color(
				sprintf( 'Creating admin module class %s (admin/%s-admin.php)', $moduleClassName, $slug ),
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

		// Write file
		if ( ! self::createAdminModuleFile( $template, $slug ) ) {
			$output->writeln( StyleUtil::error( "There was an error writing out the admin module file to disk." ) );
			return Command::FAILURE;
		}

		$output->writeln( StyleUtil::color(
			"Admin module file was created!",
			ConsoleColor::Green
		) );

		return Command::SUCCESS;
	}

	/**
	 * Ask the user if the primary plugin file should be created
	 * 
	 * @param HelperBundle $bundle The bundle containing the question and IO interfaces.
	 * 
	 * @return bool True if the user wants to create the plugin.
	 */
	private static function askCreatePlugin( HelperBundle $bundle ): bool
	{
		$yn = StyleUtil::color( '(yes/no) ', ConsoleColor::Yellow );
		$q = "Do you want to create the primary plugin file? " . $yn;
		$question = new Question( $q );

		$question->setValidator( CliUtil::yesNoValidator() );

		$answer = $bundle->helper->ask( $bundle->input, $bundle->output, $question );
		return 'y' === $answer ? true : false;
	}

	/**
	 * Check if an expected admin module file already exists.
	 *
	 * @param string $slug The lower-dash-case slug pulled from the folder name.
	 *
	 * @return bool Returns true if the admin module file exists, false if it does not.
	 */
	private static function checkAdminModuleExists( string $slug ): bool
	{
		$outputFile = self::adminModuleFilePath( $slug );
		return file_exists( $outputFile );
	}

	/**
	 * Create the admin module file from the completed template string.
	 *
	 * @param string $template The file contents to write out.
	 * @param string $slug The lower-dash-case slug to use for the file name.
	 *
	 * @return bool The status of the file write operation.
	 */
	private static function createAdminModuleFile( string $template, string $slug ): bool
	{
		$outputFile = self::adminModuleFilePath( $slug );
		$outputDir = dirname( $outputFile );

		if ( ! is_dir( $outputDir ) ) {
			if ( ! mkdir( $outputDir, 0755, true ) && ! is_dir( $outputDir ) ) {
				return false;
			}
		}

		return file_put_contents( $outputFile, $template );
	}

	/**
	 * Build the admin module file path for the current plugin.
	 *
	 * @param string $slug The lower-dash-case slug pulled from the folder name.
	 *
	 * @return string The admin module file path.
	 */
	private static function adminModuleFilePath( string $slug ): string
	{
		return sprintf( '%s/admin/%s-admin.php', getcwd(), $slug );
	}

}
