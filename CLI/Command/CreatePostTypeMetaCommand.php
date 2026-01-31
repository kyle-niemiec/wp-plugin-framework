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
use WPPF\CLI\Static\HelperBundle;
use WPPF\CLI\Static\StyleUtil;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use WPPF\CLI\Static\ConsoleColor;
use WPPF\v1_2_1\Framework\Utility;

/**
 * A command to create a post type {@see WPPF\v1_2_1\WordPress\Post_Meta} class for the current plugin.
 */
#[AsCommand(
	description: 'Create a custom post type Meta class from a template.',
	name: 'make:post-type-meta'
)]
final class CreatePostTypeMetaCommand extends Command
{
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
		$output->writeln( StyleUtil::color(
			sprintf( 'Creating a new post type meta.' ),
			ConsoleColor::BrightCyan
		) );

		if ( ! $output instanceof ConsoleOutputInterface ) {
			$output->writeln( StyleUtil::error( 'Interactive console output is required for post type selection.' ) );
			return Command::FAILURE;
		}

		// Select post type for meta
		$bundle = new HelperBundle( new QuestionHelper, $input, $output );
		$selectedPostType = self::selectPostTypeFile( $bundle );

		// Loop through variable selection options.
		$displaySection = $output->section();
		$promptSection = $output->section();
		$variables = self::askVariableInformationLoop( $bundle, $promptSection, $displaySection );

		// Create the post meta file
		$postTypeSlug = pathinfo( $selectedPostType, PATHINFO_FILENAME );
		$className = sprintf( '%s_Post_Meta', CliUtil::underscorify( $postTypeSlug, true ) );
		$filePath = self::postMetaFilePath( $className );

		if ( file_exists( $filePath ) ) {
			$output->writeln( StyleUtil::error( 'Error: The post meta file already exists.' ) );
			return Command::FAILURE;
		}

		try {
			$template = CliUtil::applyTemplate(
				'PostMeta',
				array(
					'{{class_name}}' => $className,
					'{{class_properties}}' => self::buildClassProperties( $variables ),
					'{{default_values}}' => self::buildDefaultValues( $variables ),
				)
			);
		} catch ( \RuntimeException $e ) {
			throw $e;
		}

		if ( ! self::createPostMetaFile( $template, $filePath ) ) {
			$output->writeln( StyleUtil::error( 'There was an error writing out the post meta file to disk.' ) );
			return Command::FAILURE;
		}

		$output->writeln(
			StyleUtil::color(
				sprintf( 'Post type meta class `%s` created.', $className ),
				ConsoleColor::BrightGreen
			)
		);

		$output->writeln(
			StyleUtil::color(
				sprintf( 'You can set the default values of your variables under `%s`.', $filePath ),
				ConsoleColor::Gray
			)
		);

		return Command::SUCCESS;
	}

	/**
	 * Loop prompting the user to provide variable names and data types for a {@see use WPPF\v1_2_1\WordPress\Post_Meta}.
	 * 
	 * @param HelperBundle $bundle The terminal input/output interfaces.
	 * 
	 * @return array The user-entered variable names and types.
	 */
	private static function askVariableInformationLoop(
		HelperBundle $bundle,
		ConsoleSectionOutput $promptSection,
		ConsoleSectionOutput $displaySection
	): array
	{
		$promptSection->writeln(
			'Which variables will the meta data hold? Reminder: type variable names in "lower_snake_case".'
		);

		$promptSection->writeln( StyleUtil::optional(
			'A meta box might hold a "current_text_field_value", "times_saved_count", or "is_toggle_button_active" meta value.'
		) );

		// Set up section prompts
		$variables = array();
		self::updateVariableDisplay( $displaySection, $variables );

		$nameQuestion = new Question( StyleUtil::color(
			'Enter a name (blank if finished): ',
			ConsoleColor::BrightYellow
		) );

		$nameQuestion->setValidator( self::snakeCaseValidator() );

		$typeQuestion = new Question( StyleUtil::color(
			'Enter a type (array, boolean, float, integer, string): ',
			ConsoleColor::BrightYellow
		) );

		$typeQuestion->setValidator( self::typeValidator() );

		// Loop prompting for user input until they are finished
		while ( true ) {
			// Ask for the variable name
			$promptSection->clear();
			$name = $bundle->helper->ask( $bundle->input, $promptSection, $nameQuestion );

			if ( '' === $name ) {
				break;
			}

			$variables[ $name ] = null;
			self::updateVariableDisplay( $displaySection, $variables );

			// Ask for the variable type
			$promptSection->clear();
			$type = $bundle->helper->ask( $bundle->input, $promptSection, $typeQuestion );
			$variables[ $name ] = $type;
			self::updateVariableDisplay( $displaySection, $variables );
		}

		$promptSection->clear();
		return $variables;
	}

	/**
	 * Return a callable function which can validate snake case.
	 * 
	 * @return callable A callable which can evaluate snake case validity.
	 */
	private static function snakeCaseValidator(): callable
	{
		return function ( $value ): string {
			$value = trim( strval( $value ) );

			if ( '' === $value ) {
				return '';
			}

			if ( ! preg_match( '/^[a-z][a-z0-9_]*$/', $value ) ) {
				throw new \RuntimeException( 'Please use lower_snake_case (letters, numbers, underscores).' );
			}

			return $value;
		};
	}

	/**
	 * Update the variable display section with the current list.
	 *
	 * @param ConsoleSectionOutput $displaySection The section output for the variable list.
	 * @param array $variables The collected variable names.
	 */
	private static function updateVariableDisplay( ConsoleSectionOutput $displaySection, array $variables ): void
	{
		if ( null === $displaySection ) {
			return;
		}

		$lines = array();

		// Default message if no variables have been defined
		if ( empty( $variables ) ) {
			$lines = StyleUtil::color( "|\n| [enter a variable to begin...]\n|", ConsoleColor::Gray );
			$displaySection->overwrite( explode( "\n", $lines ) );
			return;
		}

		// Loop variables and types to print
		$lines[] = StyleUtil::color( "|", ConsoleColor::Green );

		foreach ( $variables as $name => $type ) {
			if ( null === $type ) {
				$lines[] = StyleUtil::color( '| ' . $name, ConsoleColor::BrightCyan );
			} else {
				$lines[] = sprintf( StyleUtil::color( '| %s => %s', ConsoleColor::Green ), $name, $type );
			}
		}

		$lines[] = StyleUtil::color( "|", ConsoleColor::Green );
		$displaySection->overwrite( $lines );
	}

	/**
	 * Return a callable function which can validate variable types.
	 *
	 * @return callable A callable which can evaluate type validity.
	 */
	private static function typeValidator(): callable
	{
		return function ( $value ): string {
			$value = strtolower( trim( strval( $value ) ) );

			if ( '' === $value ) {
				throw new \RuntimeException( 'Please provide a type.' );
			}

			$allowed = array( 'array', 'boolean', 'float', 'integer', 'string' );

			if ( ! in_array( $value, $allowed, true ) ) {
				throw new \RuntimeException( 'Allowed types: array, boolean, float, integer, string.' );
			}

			return $value;
		};
	}

	/**
	 * Prompt the user to select a post type file from includes/post-types.
	 *
	 * @param ConsoleOutputInterface $output The console output interface.
	 *
	 * @throws \RuntimeException Throws an error if post types are not available for selection.
	 * @return string The selected file name.
	 */
	private static function selectPostTypeFile( HelperBundle $bundle ): string
	{
		CliUtil::requireFrameworkUtility();
		$directory = sprintf( '%s/includes/post-types', getcwd() );

		if ( ! is_dir( $directory ) ) {
			throw new \RuntimeException( 'No post types currently exist in `includes/post-types`.' );
		}

		$files = Utility::scandir( $directory, 'files' );

		if ( empty( $files ) ) {
			throw new \RuntimeException( 'No post types currently exist in `includes/post-types`.' );
		}

		$question = new ChoiceQuestion(
			StyleUtil::color( 'Select which custom post type to use:', ConsoleColor::BrightYellow ),
			$files
		);

		$question->setErrorMessage( 'Post type %s is invalid.' );
		return $bundle->helper->ask( $bundle->input, $bundle->output, $question );
	}

	/**
	 * Build the class properties block for the template.
	 *
	 * @param array $variables The collected variable names and types.
	 *
	 * @return string The class properties block.
	 */
	private static function buildClassProperties( array $variables ): string
	{
		$lines = [];

		foreach ( $variables as $name => $type ) {
			$lines[] = '';
			$lines[] = sprintf( "/** @var %s */", self::getTypeDeclarationString( $type ) );
			$lines[] = sprintf( "public %s $%s;", self::getTypeDeclarationString( $type ), $name );
		}

		return implode( "\n\t\t", $lines );
	}

	/**
	 * Build the default values block for the template.
	 *
	 * @param array $variables The collected variable names and types.
	 *
	 * @return string The default values block.
	 */
	private static function buildDefaultValues( array $variables ): string
	{
		$lines = [];

		foreach ( $variables as $name => $type ) {
			$lines[] = sprintf( "'%s' => %s,", $name, self::getTypeDefaultValue( $type ) );
		}

		return implode( "\n\t\t\t", $lines );
	}

	/**
	 * Return a default value for a given variable type.
	 * 
	 * @param string The string representation of the variable type.
	 * 
	 * @return mixed A default value for a given type.
	 */
	private static function getTypeDefaultValue( string $type ): string
	{
		switch( $type ) {
			case 'string':
				return "''";
				break;
			case 'array':
				return '[]';
				break;
			case 'integer':
				return '0';
				break;
			case 'boolean':
				return 'true';
				break;
			case 'float':
				return '0.0';
				break;
			default:
				return "null";
		}
	}

	/**
	 * Return a type declaration string for a given variable type.
	 * 
	 * @param string The string representation of the variable type.
	 * 
	 * @return mixed The declaration string for a given type.
	 */
	private static function getTypeDeclarationString( string $type ): string
	{
		switch( $type ) {
			case 'integer':
				return "int";
				break;
			case 'boolean':
				return 'bool';
				break;
			default:
				return $type;
		}
	}

	/**
	 * Build the post meta file path for the current plugin.
	 *
	 * @param string $className The post meta class name.
	 *
	 * @return string The post meta file path.
	 */
	private static function postMetaFilePath( string $slug ): string
	{
		$slug = CliUtil::underscorify( $slug );
		return sprintf( 'includes/post-meta/class-%s.php', $slug );
	}

	/**
	 * Create the post meta file from the completed template string.
	 *
	 * @param string $template The file contents to write out.
	 * @param string $filePath The full output file path.
	 *
	 * @return bool The status of the file write operation.
	 */
	private static function createPostMetaFile( string $template, string $filePath ): bool
	{
		$outputDir = dirname( $filePath );

		if ( ! is_dir( $outputDir ) ) {
			mkdir( $outputDir );
		}

		return file_put_contents( $filePath, $template );
	}

}
