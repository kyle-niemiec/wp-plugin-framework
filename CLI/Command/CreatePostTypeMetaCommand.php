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

use WPPF\CLI\Static\HelperBundle;
use WPPF\CLI\Static\StyleUtil;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPPF\CLI\Static\ConsoleColor;

/**
 * A command to create a post type {@see WPPF\v1_2_1\WordPress\Meta} class for the current plugin.
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
	 * @param ConsoleOutputInterface $output The terminal output interface.
	 *
	 * @return int The command success status.
	 */
	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		self::printInformationalMessages( $output );

		$displaySection = $output->section();
		$promptSection = $output->section();
		$bundle = new HelperBundle( new QuestionHelper, $input, $output );

		self::askVariableInformationLoop( $bundle, $promptSection, $displaySection );

		return Command::SUCCESS;
	}

	/**
	 * Loop prompting the user to provide variable names and data types for a @see use WPPF\v1_2_1\WordPress\Post_Meta.
	 * 
	 * @param HelperBundle $bundle The terminal input/output interfaces.
	 * 
	 * @return array The user-entered variable names and types.
	 */
	private static function askVariableInformationLoop(
		HelperBundle $bundle,
		?ConsoleSectionOutput $promptSection,
		?ConsoleSectionOutput $displaySection
	): array
	{
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
			if ( null !== $promptSection ) {
				$promptSection->clear();
			}

			// Ask for the variable name
			$name = $bundle->helper->ask( $bundle->input, $promptSection, $nameQuestion );

			if ( '' === $name ) {
				break;
			}

			$variables[ $name ] = null;
			self::updateVariableDisplay( $displaySection, $variables );

			if ( null !== $promptSection ) {
				$promptSection->clear();
			}

			// Ask for the variable type
			$type = $bundle->helper->ask( $bundle->input, $promptSection, $typeQuestion );
			$variables[ $name ] = $type;
			self::updateVariableDisplay( $displaySection, $variables );
		}

		return $variables;
	}

	/**
	 * Output informational messages.
	 * 
	 * @param OutputInterface The terminal output interface.
	 */
	private static function printInformationalMessages( OutputInterface $output ): void
	{
		$output->writeln(
			StyleUtil::color(
				sprintf( 'Creating a new post type meta.' ),
				ConsoleColor::BrightCyan
			)
		);

		$output->writeln(
			'Which variables will the meta data hold? Reminder: type variable names in "lower_snake_case".'
		);

		$output->writeln(
			StyleUtil::optional(
				'A meta box might hold a "current_text_field_value", "times_saved_count", or "is_toggle_button_active" meta value.'
			)
		);
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

}
