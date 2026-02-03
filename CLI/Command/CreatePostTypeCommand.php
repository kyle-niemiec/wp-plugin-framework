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
use WPPF\v1_2_2\Framework\Utility;

/**
 * A command to create a post type class for the current plugin.
 */
#[AsCommand(
	description: 'Create a custom post type and define basic options.',
	name: 'make:post-type'
)]
final class CreatePostTypeCommand extends PluginCliCommand
{
	/** @var string A reference to the location of the post types folder */
	public const POST_TYPES_DIR = 'includes/post-types';

	/**
	 * Set up the helper variables, control message flow.
	 *
	 * @param InputInterface $input The terminal input interface.
	 * @param OutputInterface $output The terminal output interface.
	 *
	 * @return int The command success status.
	 */
	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$bundle = new HelperBundle( new QuestionHelper, $input, $output );

		$output->writeln(
			StyleUtil::color(
				sprintf( "\nCreating a new custom post type..." ),
				ConsoleColor::BrightCyan
			)
		);

		// Collect post type information
		$singularName = self::askSingularName( $bundle );
		$pluralName = self::askPluralName( $bundle );
		$showInMenu = self::askShowInMenu( $bundle );
		$menuName = $singularName;

		if ( $showInMenu ) {
			$menuName = self::askMenuEntryTitle( $bundle, $singularName );
		}

		$className = CliUtil::underscorify( $singularName, true );
		$postTypeKey = CliUtil::underscorify( $singularName );
		$output->writeln( '' );

		// Make sure the post type doesn't already exist
		if ( self::checkPostTypeExists( $postTypeKey ) ) {
			$output->writeln( StyleUtil::error( 'Error: The post type file already exists.' ) );
			return Command::FAILURE;
		}

		try {
			$template = CliUtil::applyTemplate(
				'PostType',
				[
					'{{class_name}}' => $className,
					'{{slug}}' => $postTypeKey,
					'{{menu_name}}' => $menuName,
					'{{sungular_name}}' => $singularName,
					'{{plural_name}}' => $pluralName,
					'{{show_in_menu}}' => $showInMenu ? 'true' : 'false',
				]
			);
		} catch ( \RuntimeException $e ) {
			throw $e;
		}

		$slug = Utility::slugify( $postTypeKey );

		if ( ! self::createPostTypeFile( $template, $slug ) ) {
			$output->writeln( StyleUtil::error( 'There was an error writing out the post type file to disk.' ) );
			return Command::FAILURE;
		}

		$output->writeln(
			StyleUtil::color(
				sprintf(
					'Post type `%s` was created at `%s/%s.php`.',
					$className,
					self::POST_TYPES_DIR,
					$slug
				),
				ConsoleColor::BrightGreen
			)
		);

		$output->writeln(
			StyleUtil::color(
				'See \\WPPF\\v1_2_2\\WordPress\\Post_Type for the complete list of post type options.',
				ConsoleColor::Gray
			)
		);

		$output->writeln( '' );
		return Command::SUCCESS;
	}

	/**
	 * Ask the user for the singular name of the post type.
	 *
	 * @param HelperBundle $bundle The bundle containing the question and IO interfaces.
	 *
	 * @return string The singular name entered by the user.
	 */
	private static function askSingularName( HelperBundle $bundle ): string
	{
		$question = new Question( 'Singular name: ' );

		$question->setValidator( function ( $value ): string {
			if ( null === $value || '' === trim( $value ) ) {
				throw new \RuntimeException( 'The singular name cannot be empty.' );
			}

			return $value;
		} );

		return $bundle->helper->ask( $bundle->input, $bundle->output, $question );
	}

	/**
	 * Ask the user for the plural name of the post type.
	 *
	 * @param HelperBundle $bundle The bundle containing the question and IO interfaces.
	 *
	 * @return string The plural name entered by the user.
	 */
	private static function askPluralName( HelperBundle $bundle ): string
	{
		$question = new Question( 'Plural name: ' );

		$question->setValidator( function ( $value ): string {
			if ( null === $value || '' === trim( $value ) ) {
				throw new \RuntimeException( 'The plural name cannot be empty.' );
			}

			return $value;
		} );

		return $bundle->helper->ask( $bundle->input, $bundle->output, $question );
	}

	/**
	 * Ask the user if the post type should show in the admin menu.
	 *
	 * @param HelperBundle $bundle The bundle containing the question and IO interfaces.
	 *
	 * @return bool True if the post type should show in the admin menu.
	 */
	private static function askShowInMenu( HelperBundle $bundle ): bool
	{
		$yn = StyleUtil::color( '(yes/no) ', ConsoleColor::Yellow );
		$question = new Question( 'Do you want to show this post type in the admin menu? ' . $yn );
		$question->setValidator( CliUtil::yesNoValidator() );

		$answer = $bundle->helper->ask( $bundle->input, $bundle->output, $question );

		return in_array( $answer, [ 'yes', 'y' ] );
	}

	/**
	 * Ask the user for the admin menu entry title.
	 *
	 * @param HelperBundle $bundle The bundle containing the question and IO interfaces.
	 * @param string $singularName The singular name for the default.
	 *
	 * @return string The menu entry title entered by the user.
	 */
	private static function askMenuEntryTitle( HelperBundle $bundle, string $singularName ): string
	{
		$question = new Question(
			sprintf(
				"What do you want the menu entry title to say? %s ",
				StyleUtil::optional( sprintf( "Default: '%s'", $singularName ) )
			),
			$singularName
		);

		$question->setValidator( function ( $value ) use ( $singularName ): string {
			$clean = trim( strval( $value ) );

			if ( '' === $clean ) {
				return $singularName;
			}

			return $clean;
		} );

		return $bundle->helper->ask( $bundle->input, $bundle->output, $question );
	}

	/**
	 * Check if an expected post type file already exists.
	 *
	 * @param string $slug The lower-underscore-case post type slug.
	 *
	 * @return bool Returns true if the post type file exists, false if it does not.
	 */
	private static function checkPostTypeExists( string $slug ): bool
	{
		$outputFile = self::postTypeFilePath( $slug );
		return file_exists( $outputFile );
	}

	/**
	 * Create the post type file from the completed template string.
	 *
	 * @param string $template The file contents to write out.
	 * @param string $slug The lower-underscore-case slug to use for the file name.
	 *
	 * @return bool The status of the file write operation.
	 */
	private static function createPostTypeFile( string $template, string $slug ): bool
	{
		$outputFile = self::postTypeFilePath( $slug );
		$outputDir = dirname( $outputFile );

		if ( ! is_dir( $outputDir ) ) {
			mkdir( $outputDir, 0777, true );
		}

		return file_put_contents( $outputFile, $template );
	}

	/**
	 * Build the post type file path for the current plugin.
	 *
	 * @param string $slug The lower-underscore-case slug for the file name.
	 *
	 * @return string The post type file path.
	 */
	private static function postTypeFilePath( string $slug ): string
	{
		return sprintf( '%s/%s/class-%s.php', getcwd(), self::POST_TYPES_DIR, $slug );
	}

}
