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
use WPPF\CLI\Enum\ConsoleColor;
use WPPF\CLI\Support\PluginCliCommand;
use WPPF\v1_2_1\Framework\Utility;

/**
 * A command to create a post screen class for the current plugin.
 */
#[AsCommand(
	description: 'Create a post screen class from a template.',
	name: 'make:post-screen'
)]
final class CreatePostScreenCommand extends PluginCliCommand
{
	/** @var bool {@inheritDoc} */
	protected static bool $requiresPostTypes = true;

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

		// Ask the user which post type to use
		$selectedPostType = $this->promptForPostTypeFile( $bundle );

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
