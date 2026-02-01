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
use Symfony\Component\Console\Question\ChoiceQuestion;
use WPPF\CLI\Enum\ConsoleColor;
use WPPF\v1_2_1\Framework\Utility;

/**
 * A command to create a post screen class for the current plugin.
 */
#[AsCommand(
	description: 'Create a post screen class from a template.',
	name: 'make:post-screen'
)]
final class CreatePostScreenCommand extends Command
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
			sprintf( 'Creating a new admin post screen.' ),
			ConsoleColor::BrightCyan
		) );

		$bundle = new HelperBundle( new QuestionHelper, $input, $output );
		CliUtil::requireFrameworkUtility();

		// Ask the user which post type to use
		$selectedPostType = self::selectPostTypeFile( $bundle );

		$postTypeClass = Utility::get_file_class_name( sprintf(
			'%s/%s',
			CreatePostTypeCommand::POST_TYPES_DIR,
			$selectedPostType
		) );

		$className = sprintf( '%s_Post_Screens', $postTypeClass );

		// Check that the post screen doesn't already exist
		$filePath = self::postScreenFilePath( Utility::slugify( $className ) );

		if ( file_exists( $filePath ) ) {
			$output->writeln( StyleUtil::error( sprintf( 'The post screen `%s` already exists.', $className ) ) );
			return Command::FAILURE;
		}

		// Create the post screen template file
		try {
			$template = CliUtil::applyTemplate(
				'PostScreen',
				[
					'{{class_name}}' => $className,
					'{{post_type_class}}' => $postTypeClass,
				]
			);
		} catch ( \RuntimeException $e ) {
			throw $e;
		}

		if ( ! self::createPostScreenFile( $template, $filePath ) ) {
			$output->writeln( StyleUtil::error( 'There was an error writing out the post screen file to disk.' ) );
			return Command::FAILURE;
		}

		$output->writeln(
			StyleUtil::color(
				sprintf( 'Post screen class `%s` created at `%s`.', $className, $filePath ),
				ConsoleColor::BrightGreen
			)
		);

		return Command::SUCCESS;
	}

	/**
	 * Prompt the user to select a post type file from includes/post-types.
	 *
	 * @param HelperBundle $bundle The IO interfaces for the terminal.
	 *
	 * @throws \RuntimeException Throws an error if post types are not available for selection.
	 * @return string The selected file name.
	 */
	private static function selectPostTypeFile( HelperBundle $bundle ): string
	{
		$directory = sprintf( '%s/%s', getcwd(), CreatePostTypeCommand::POST_TYPES_DIR );

		// Check post types exist
		if ( ! is_dir( $directory ) ) {
			throw new \RuntimeException( sprintf(
				'No post types currently exist in `%s`.',
				CreatePostTypeCommand::POST_TYPES_DIR
			) );
		}

		$files = Utility::scandir( $directory, 'files' );

		if ( empty( $files ) ) {
			throw new \RuntimeException( sprintf(
				'No post types currently exist in `%s`.',
				CreatePostTypeCommand::POST_TYPES_DIR
			) );
		}

		// Ask which post type
		$question = new ChoiceQuestion(
			StyleUtil::color( 'Select which custom post type to use:', ConsoleColor::BrightYellow ),
			$files
		);

		$question->setErrorMessage( 'Post type %s is invalid.' );
		return $bundle->helper->ask( $bundle->input, $bundle->output, $question );
	}

	/**
	 * Build the post screen file path for the current plugin.
	 *
	 * @param string $slug The post screen class slug.
	 *
	 * @return string The post screen file path.
	 */
	private static function postScreenFilePath( string $slug ): string
	{
		return sprintf( '%s/admin/includes/screens/class-%s.php', getcwd(), $slug );
	}

	/**
	 * Create the post screen file from the completed template string.
	 *
	 * @param string $template The file contents to write out.
	 * @param string $filePath The full output file path.
	 *
	 * @return bool The status of the file write operation.
	 */
	private static function createPostScreenFile( string $template, string $filePath ): bool
	{
		$outputDir = dirname( $filePath );

		if ( ! is_dir( $outputDir ) ) {
			if ( ! mkdir( $outputDir, 0755, true ) && ! is_dir( $outputDir ) ) {
				return false;
			}
		}

		return file_put_contents( $filePath, $template );
	}

}
