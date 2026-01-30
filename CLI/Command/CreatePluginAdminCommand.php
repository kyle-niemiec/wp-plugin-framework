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

		$module_class_name = sprintf(
			'%s_Admin',
			CliUtil::plugin_class_name( $slug )
		);

		// Ensure the base plugin exists first
		if ( ! CreatePluginCommand::check_plugin_exists( $slug ) ) {
			$output->writeln( StyleUtil::color(
				sprintf( "Main plugin file not found (%s.php)", $slug ),
				ConsoleColor::Yellow )
			);

			if ( self::ask_create_plugin( $bundle ) ) {
				$create_plugin_command = new CreatePluginCommand();
				$create_plugin_input = new ArrayInput( array() );
				$create_plugin_input->setInteractive( $input->isInteractive() );
				$status = $create_plugin_command->run( $create_plugin_input, $output );

				if ( Command::SUCCESS !== $status || ! CreatePluginCommand::check_plugin_exists( $slug ) ) {
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
		if ( self::check_admin_module_exists( $slug ) ) {
			$output->writeln( StyleUtil::error( "Error: The admin module file already exists." ) );
			return Command::FAILURE;
		}

		$output->writeln(
			StyleUtil::color(
				sprintf( 'Creating admin module class %s (admin/%s-admin.php)', $module_class_name, $slug ),
				ConsoleColor::BrightCyan
			)
		);

		// Apply the data to the template
		try {
			$template = CliUtil::apply_template(
				'AdminModule',
				array(
					'{{module_class_name}}' => $module_class_name,
				)
			);
		} catch ( \RuntimeException $e ) {
			throw $e;
		}

		// Write file
		if ( ! self::create_admin_module_file( $template, $slug ) ) {
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
	private static function ask_create_plugin( HelperBundle $bundle ): bool
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
	private static function check_admin_module_exists( string $slug ): bool
	{
		$output_file = self::admin_module_file_path( $slug );
		return file_exists( $output_file );
	}

	/**
	 * Create the admin module file from the completed template string.
	 *
	 * @param string $template The file contents to write out.
	 * @param string $slug The lower-dash-case slug to use for the file name.
	 *
	 * @return bool The status of the file write operation.
	 */
	private static function create_admin_module_file( string $template, string $slug ): bool
	{
		$output_file = self::admin_module_file_path( $slug );
		$output_dir = dirname( $output_file );

		if ( ! is_dir( $output_dir ) ) {
			if ( ! mkdir( $output_dir, 0755, true ) && ! is_dir( $output_dir ) ) {
				return false;
			}
		}

		return file_put_contents( $output_file, $template );
	}

	/**
	 * Build the admin module file path for the current plugin.
	 *
	 * @param string $slug The lower-dash-case slug pulled from the folder name.
	 *
	 * @return string The admin module file path.
	 */
	private static function admin_module_file_path( string $slug ): string
	{
		return sprintf( '%s/admin/%s-admin.php', getcwd(), $slug );
	}
}
