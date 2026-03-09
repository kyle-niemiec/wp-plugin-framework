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
 * A command to create a module class from a template.
 */
#[AsCommand(
	description: 'Create a module class from a template.',
	name: 'make:module'
)]
final class CreateModuleCommand extends PluginCliCommand
{
	/** @var bool @inheritDoc */
	protected static bool $requiresPlugin = true;

	/** @var string The directory where module files should be placed. */
	private const MODULES_DIR = 'includes/modules';

	/**
	 * Set up the helper variables and control message flow.
	 *
	 * @param InputInterface $input The terminal input interface.
	 * @param OutputInterface $output The terminal output interface.
	 *
	 * @return int The command success status.
	 */
	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$output->writeln( StyleUtil::color(
			sprintf( "\nCreating a new module...\n" ),
			ConsoleColor::BrightCyan
		) );

		$bundle = new HelperBundle( new QuestionHelper, $input, $output );
		$moduleName = self::askModuleName( $bundle );
		$className = CliUtil::underscorify( $moduleName, true );
		$slug = Utility::slugify( $className );
		$filePath = self::moduleFilePath( $slug );

		if ( file_exists( $filePath ) ) {
			$output->writeln( StyleUtil::error( 'Error: The module file already exists.' ) );
			return Command::FAILURE;
		}

		try {
			$template = CliUtil::applyTemplate(
				'Module',
				[
					'{{class_name}}' => $className,
				]
			);
		} catch ( \RuntimeException $e ) {
			throw $e;
		}

		if ( ! self::createModuleFile( $template, $filePath ) ) {
			$output->writeln( StyleUtil::error( 'There was an error writing out the module file to disk.' ) );
			return Command::FAILURE;
		}

		$output->writeln(
			StyleUtil::color(
				sprintf( "\nModule class `%s` created at `%s/class-%s.php`.", $className, self::MODULES_DIR, $slug ),
				ConsoleColor::BrightGreen
			)
		);

		$output->writeln( '' );
		return Command::SUCCESS;
	}

	/**
	 * Ask the user what the module name should be.
	 *
	 * @param HelperBundle $bundle The bundle containing the question and IO interfaces.
	 *
	 * @return string The module name entered by the user.
	 */
	private static function askModuleName( HelperBundle $bundle ): string
	{
		$question = new Question( 'Module name: ' );

		$question->setValidator( function ( $value ): string {
			$value = trim( strval( $value ) );

			if ( '' === $value ) {
				throw new \RuntimeException( 'The module name cannot be empty.' );
			}

			return $value;
		} );

		return $bundle->helper->ask( $bundle->input, $bundle->output, $question );
	}

	/**
	 * Build the module file path for the current plugin.
	 *
	 * @param string $slug The module class slug.
	 *
	 * @return string The module file path.
	 */
	private static function moduleFilePath( string $slug ): string
	{
		return sprintf( '%s/%s/class-%s.php', getcwd(), self::MODULES_DIR, $slug );
	}

	/**
	 * Create the module file from the completed template string.
	 *
	 * @param string $template The file contents to write out.
	 * @param string $filePath The full output file path.
	 *
	 * @return bool The status of the file write operation.
	 */
	private static function createModuleFile( string $template, string $filePath ): bool
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
