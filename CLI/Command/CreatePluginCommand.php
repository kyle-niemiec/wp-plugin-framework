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

use WPPF\CLI\Util\CliUtil;
use WPPF\CLI\Support\HelperBundle;
use WPPF\CLI\Util\StyleUtil;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPPF\CLI\Enum\ConsoleColor;
use WPPF\CLI\Support\PluginCliCommand;

/**
 * A command to gather information from the user and construct a plugin main file for the user in the CWD.
 */
#[AsCommand(
	description: 'Create a base plugin file with a series of prompts.',
	name: 'make:plugin'
)]
final class CreatePluginCommand extends PluginCliCommand
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
		$pluginClassName = CliUtil::underscorify( $slug, true );
		$bundle = new HelperBundle( new QuestionHelper, $input, $output );

		// Ensure the plugin file doesn't exist
		if ( self::checkPluginExists( $slug ) ) {
			$output->writeln( StyleUtil::error( 'Error: A plugin file already exists in this directory.' ) );
			return Command::FAILURE;
		}

		// Informational output (say hello)
		self::informationalOutput( $output, $pluginClassName, $slug );

		// Gather user information
		$templateData = self::gatherPluginInformation( $bundle, $pluginClassName );

		// Apply the data to the template
		try {
			$template = CliUtil::applyTemplate( 'Plugin', $templateData );
		} catch ( \RuntimeException $e ) {
			throw $e;
		}

		// Write file
		if ( ! self::createPluginFile( $template, $slug ) ) {
			$output->writeln( StyleUtil::error( "There was an error writing out the plugin file to disk." ) );
			return Command::FAILURE;
		}

		$output->writeln( StyleUtil::color(
			sprintf( "\nPlugin file was created at `%s`.php.\n", $slug ),
			ConsoleColor::BrightGreen
		) );

		// Success!
		return Command::SUCCESS;
	}

	/**
	 * Ask the user what the plugin name should be, but it cannot be empty.
	 * 
	 * @param HelperBundle $bundle The bundle containing the question and IO interfaces.
	 * 
	 * @return string The plugin name entered by the user.
	 */
	private static function askPluginName( HelperBundle $bundle ): string
	{
		$pluginNameQuestion = new Question( "Plugin Name: " );

		$pluginNameQuestion->setValidator( function ( $value ) use ( $bundle ): string {
			if ( $value === null || trim( $value ) === '' ) {
				throw new \RuntimeException( "The plugin name cannot be empty." );
			}

			return $value;
		} );

		return $bundle->helper->ask( $bundle->input, $bundle->output, $pluginNameQuestion );
	}

	/**
	 * Ask the user what the plugin URI is.
	 * 
	 * @param HelperBundle $bundle The bundle containing the question and IO interfaces.
	 * 
	 * @return string|null The plugin URI entered by the user.
	 */
	private static function askPluginUri( HelperBundle $bundle ): ?string
	{
		$question = sprintf( "Plugin URI %s: ", StyleUtil::optional( "(optional)" ) );
		$pluginUriQuestion = new Question( $question, null );

		return $bundle->helper->ask( $bundle->input, $bundle->output, $pluginUriQuestion );
	}

	/**
	 * Ask the user what the plugin description is.
	 * 
	 * @param HelperBundle $bundle The bundle containing the question and IO interfaces.
	 * 
	 * @return string|null The plugin description entered by the user.
	 */
	private static function askPluginDescription( HelperBundle $bundle ): ?string
	{
		$question = sprintf( "Description %s: ", StyleUtil::optional( "(optional)" ) );
		$pluginDescriptionQuestion = new Question( $question, null );

		return $bundle->helper->ask( $bundle->input, $bundle->output, $pluginDescriptionQuestion );
	}

	/**
	 * Ask the user what the author name is, but it cannot be empty.
	 * 
	 * @param HelperBundle $bundle The bundle containing the question and IO interfaces.
	 * 
	 * @return string The author name entered by the user.
	 */
	private static function askAuthorName( HelperBundle $bundle ): string
	{
		$authorNameQuestion = new Question( 'Author: ' );

		$authorNameQuestion->setValidator( function ( $value ) use ( $bundle ): string {
			if ( $value === null || trim( $value ) === '' ) {
				throw new \RuntimeException( "The author name cannot be empty." );
			}

			return $value;
		} );

		return $bundle->helper->ask( $bundle->input, $bundle->output, $authorNameQuestion );
	}

	/**
	 * Ask the user what the author's URI is.
	 * 
	 * @param HelperBundle $bundle The bundle containing the question and IO interfaces.
	 * 
	 * @return string|null The author URI entered by the user.
	 */
	private static function askAuthorUri( HelperBundle $bundle ): ?string
	{
		$question = sprintf( "Author URI %s: ", StyleUtil::optional( "(optional)" ) );
		$authorUriQuestion = new Question( $question, null );

		return $bundle->helper->ask( $bundle->input, $bundle->output, $authorUriQuestion );
	}

	/**
	 * Check if an expected plugin file already exists.
	 * 
	 * @param string $slug The lower-dash-case slug pulled from the folder name.
	 * 
	 * @return bool Returns true if the plugin file exists, false if it does not.
	 */
	public static function checkPluginExists( string $slug ): bool
	{
		$outputFile = sprintf( '%s/%s.php', getcwd(), $slug );
		return file_exists( $outputFile );
	}

	/**
	 * Create the plugin file from the completed template string.
	 * 
	 * @param string $template The file contents to write out.
	 * @param string $slug The lower-dash-case slug to use for the file name.
	 * 
	 * @return bool The status of the file write operation.
	 */
	private static function createPluginFile( string $template, string $slug ): bool
	{
		$outputFile = sprintf( '%s/%s.php', getcwd(), $slug );
		return file_put_contents( $outputFile, $template );
	}

	/**
	 * Prompt for the plugin and author information from the user.
	 * 
	 * @param HelperBundle $bundle The question helper and IO interfaces for interactive user data collection.
	 * @param string $pluginClassName The Upper_Underscored_Case name of the plugin class.
	 * 
	 * @return array An associative array with plugin information for a template.
	 */
	private static function gatherPluginInformation( HelperBundle $bundle, string $pluginClassName ): array
	{
		$pluginName = self::askPluginName( $bundle );
		$pluginUri = self::askPluginUri( $bundle );
		$pluginDescription = self::askPluginDescription( $bundle );
		$authorName = self::askAuthorName( $bundle );
		$authorUri = self::askAuthorUri( $bundle );

		// Call template with user information
		return [
			'{{plugin_name}}' => $pluginName,
			'{{plugin_uri}}' => $pluginUri,
			'{{description}}' => $pluginDescription,
			'{{author}}' => $authorName,
			'{{author_uri}}' => $authorUri,
			'{{plugin_class_name}}' => $pluginClassName,
		];
	}

	/**
	 * A helper function to move the informational text out of the execution context.
	 * 
	 * @param OutputInterface $output The terminal output interface.
	 * @param string $pluginClassName The Upper_Underscore_Case class name for informational output.
	 * @param string $slug The reference slug for the plugin naming.
	 */
	private static function informationalOutput( OutputInterface $output, string $pluginClassName, string $slug ): void
	{
		$output->writeln( '' );
		$output->writeln( "🚀~~~✨" );
		$output->writeln( "Thanks for using my plugin framework! I hope you get as much use out of it as I have!" );
		$output->writeln( "Follow the prompts below to create your main plugin file." );
		$output->writeln( "✨~~~🚀" );
		$output->writeln( '' );

		$output->writeln(
			StyleUtil::color(
				sprintf( 'Creating plugin class `%s`...', $pluginClassName ),
				ConsoleColor::BrightCyan
			)
		);
	}

}
