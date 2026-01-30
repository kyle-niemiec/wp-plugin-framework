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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPPF\CLI\Static\ConsoleColor;

/**
 * A command to create a post type class for the current plugin.
 */
#[AsCommand(
	description: 'Create a custom post type and define basic options.',
	name: 'make:post-type'
)]
final class CreatePostTypeCommand extends Command
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

		$output->writeln(
			StyleUtil::color(
				sprintf( 'Creating a new custom post type.' ),
				ConsoleColor::BrightCyan
			)
		);

		$singular_name = self::ask_singular_name( $bundle );
		$plural_name = self::ask_plural_name( $bundle );
		$show_in_menu = self::ask_show_in_menu( $bundle );
		$menu_name = $singular_name;

		if ( $show_in_menu ) {
			$menu_name = self::ask_menu_entry_title( $bundle, $singular_name );
		}

		$class_name = self::post_type_class_name( $singular_name );
		$slug = self::post_type_slug( $singular_name );

		if ( self::check_post_type_exists( $slug ) ) {
			$output->writeln( StyleUtil::error( 'Error: The post type file already exists.' ) );
			return Command::FAILURE;
		}

		$output->writeln(
			StyleUtil::color(
				sprintf(
					'Creating post type class %s (includes/post-types/%s.php)',
					$class_name,
					$slug
				),
				ConsoleColor::BrightCyan
			)
		);

		try {
			$template = CliUtil::apply_template(
				'PostType',
				[
					'{{class_name}}' => $class_name,
					'{{slug}}' => $slug,
					'{{menu_name}}' => $menu_name,
					'{{sungular_name}}' => $singular_name,
					'{{plural_name}}' => $plural_name,
					'{{show_in_menu}}' => $show_in_menu ? 'true' : 'false',
				]
			);
		} catch ( \RuntimeException $e ) {
			throw $e;
		}

		if ( ! self::create_post_type_file( $template, $slug ) ) {
			$output->writeln( StyleUtil::error( 'There was an error writing out the post type file to disk.' ) );
			return Command::FAILURE;
		}

		$output->writeln(
			StyleUtil::color(
				'Post type file was created!',
				ConsoleColor::Green
			)
		);

		$output->writeln(
			StyleUtil::color(
				'See \\WPPF\\v1_2_1\\WordPress\\Post_Type for the complete list of post type options.',
				ConsoleColor::Gray
			)
		);

		return Command::SUCCESS;
	}

	/**
	 * Ask the user for the singular name of the post type.
	 *
	 * @param HelperBundle $bundle The bundle containing the question and IO interfaces.
	 *
	 * @return string The singular name entered by the user.
	 */
	private static function ask_singular_name( HelperBundle $bundle ): string
	{
		$question = new Question( 'What is the singular name of the custom post type? ' );

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
	private static function ask_plural_name( HelperBundle $bundle ): string
	{
		$question = new Question( 'What is the plural name of the custom post type? ' );

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
	private static function ask_show_in_menu( HelperBundle $bundle ): bool
	{
		$question = new Question( 'Do you want to show this post type in the admin menu? (yes/no) ' );

		$question->setValidator( function ( $value ): string {
			$value = strtolower( trim( strval( $value ) ) );

			if ( ! in_array( $value, array( 'yes', 'no', 'y', 'n' ) ) ) {
				throw new \RuntimeException( 'Please answer yes or no.' );
			}

			return $value;
		} );

		$answer = $bundle->helper->ask( $bundle->input, $bundle->output, $question );
		return in_array( $answer, array( 'yes', 'y' ) );
	}

	/**
	 * Ask the user for the admin menu entry title.
	 *
	 * @param HelperBundle $bundle The bundle containing the question and IO interfaces.
	 * @param string $singular_name The singular name for the default.
	 *
	 * @return string The menu entry title entered by the user.
	 */
	private static function ask_menu_entry_title( HelperBundle $bundle, string $singular_name ): string
	{
		$question = new Question(
			sprintf(
				"What do you want the menu entry title to say? %s ",
				StyleUtil::optional( sprintf( "Default: '%s'", $singular_name ) )
			),
			$singular_name
		);

		$question->setValidator( function ( $value ) use ( $singular_name ): string {
			$clean = trim( strval( $value ) );

			if ( '' === $clean ) {
				return $singular_name;
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
	private static function check_post_type_exists( string $slug ): bool
	{
		$output_file = self::post_type_file_path( $slug );
		return file_exists( $output_file );
	}

	/**
	 * Create the post type file from the completed template string.
	 *
	 * @param string $template The file contents to write out.
	 * @param string $slug The lower-underscore-case slug to use for the file name.
	 *
	 * @return bool The status of the file write operation.
	 */
	private static function create_post_type_file( string $template, string $slug ): bool
	{
		$output_file = self::post_type_file_path( $slug );
		$output_dir = dirname( $output_file );

		if ( ! is_dir( $output_dir ) ) {
			if ( ! mkdir( $output_dir, 0755, true ) && ! is_dir( $output_dir ) ) {
				return false;
			}
		}

		return file_put_contents( $output_file, $template );
	}

	/**
	 * Build the post type file path for the current plugin.
	 *
	 * @param string $slug The lower-underscore-case slug for the file name.
	 *
	 * @return string The post type file path.
	 */
	private static function post_type_file_path( string $slug ): string
	{
		return sprintf( '%s/includes/post-types/%s.php', getcwd(), $slug );
	}

	/**
	 * Convert a singular name into the post type class name.
	 *
	 * @param string $singular_name The singular post type name.
	 *
	 * @return string The class name for the post type.
	 */
	private static function post_type_class_name( string $singular_name ): string
	{
		$name = preg_replace( '/[^a-zA-Z\\d]/', '_', $singular_name );
		return ucwords( $name, '_' );
	}

	/**
	 * Convert a singular name into the post type slug.
	 *
	 * @param string $singular_name The singular post type name.
	 *
	 * @return string The slug for the post type.
	 */
	private static function post_type_slug( string $singular_name ): string
	{
		$slug = preg_replace( '/[^a-zA-Z\\d]/', '_', $singular_name );
		$slug = strtolower( trim( $slug, '_' ) );

		return $slug;
	}
}
