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
	 * @param OutputInterface $output The terminal output interface.
	 *
	 * @return int The command success status.
	 */
	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$bundle = new HelperBundle( new QuestionHelper, $input, $output );
		self::printInformationalMessages( $output );

		$variables = [];
		$question = new Question( 'Variable name (blank to finish): ' );
		$question->setValidator( self::snakeCaseValidator() );

		// Loop prompting for user input until they are finished
		while ( true ) {
			$value = $bundle->helper->ask( $bundle->input, $bundle->output, $question );

			if ( '' === $value ) {
				break;
			}

			$variables[] = $value;
		}

		// Remind user to set data types.
		$output->writeln(
			StyleUtil::optional(
				'You must set the data types of the default values in {file}.'
			)
		);

		return Command::SUCCESS;
	}

	/**
	 * Output informational messages.
	 * 
	 * @param OutputInterface The terminal output interface.
	 */
	private function printInformationalMessages( OutputInterface $output ): void
	{
		$output->writeln(
			StyleUtil::color(
				sprintf( 'Creating a new post type meta.' ),
				ConsoleColor::BrightCyan
			)
		);

		$output->writeln(
			'Which variables do you want the custom post type meta to store?'
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
	private function snakeCaseValidator(): callable
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

}
