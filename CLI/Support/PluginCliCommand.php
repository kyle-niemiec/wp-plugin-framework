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

namespace WPPF\CLI\Support;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use WPPF\CLI\Command\CreatePostTypeCommand;
use WPPF\CLI\Enum\ConsoleColor;
use WPPF\CLI\Util\CliUtil;
use WPPF\CLI\Util\StyleUtil;
use WPPF\v1_2_1\Framework\Utility;

/**
 * A shared command base for plugin-related CLI commands.
 */
abstract class PluginCliCommand extends Command
{
	/** @var bool True if the command requires the plugin file to exist first. */
	protected static bool $requiresPlugin = false;

	/** @var bool True if the command requires the admin module file to exist first. */
	protected static bool $requiresAdminModule = false;

	/** @var bool True if the command requires at least one post type file to exist first. */
	protected static bool $requiresPostTypes = false;

	/**
	 * Ensure required components exist before executing the command.
	 *
	 * @param InputInterface $input The terminal input interface.
	 * @param OutputInterface $output The terminal output interface.
	 *
	 * @return int The command status.
	 */
	public function run( InputInterface $input, OutputInterface $output ): int
	{
		CliUtil::requireFrameworkUtility();
		$requirementsStatus = $this->ensureRequiredComponents( $input, $output );

		if ( null !== $requirementsStatus ) {
			return $requirementsStatus;
		}

		return parent::run( $input, $output );
	}

	/**
	 * Prompt the user to select a post type file from includes/post-types.
	 *
	 * @param HelperBundle $bundle The IO interfaces for the terminal.
	 *
	 * @throws \RuntimeException Throws an error if post types are not available for selection.
	 * @return string The selected file name.
	 */
	protected function promptForPostTypeFile( HelperBundle $bundle ): string
	{
		$files = $this->postTypeFiles();

		if ( empty( $files ) ) {
			throw new \RuntimeException( sprintf(
				'No post types currently exist in `%s`.',
				CreatePostTypeCommand::POST_TYPES_DIR
			) );
		}

		$question = new ChoiceQuestion(
			StyleUtil::color( 'Select which custom post type to use:', ConsoleColor::BrightYellow ),
			$files
		);

		$question->setErrorMessage( 'Post type %s is invalid.' );
		return $bundle->helper->ask( $bundle->input, $bundle->output, $question );
	}

	/**
	 * Build the admin module file path for the current plugin.
	 *
	 * @param string $slug The lower-dash-case slug pulled from the folder name.
	 *
	 * @return string The admin module file path.
	 */
	protected function adminModuleFilePath( string $slug ): string
	{
		return sprintf( '%s/admin/%s-admin.php', getcwd(), $slug );
	}

	/**
	 * Check if the admin module file exists for the current plugin.
	 *
	 * @param string $slug The current plugin slug.
	 *
	 * @return bool True if an admin module file is present.
	 */
	protected function adminModuleExists( string $slug ): bool
	{
		return file_exists( $this->adminModuleFilePath( $slug ) );
	}

	/**
	 * Check if the plugin file exists for the current plugin.
	 *
	 * @param string $slug The current plugin slug.
	 *
	 * @return bool True if the plugin file exists.
	 */
	protected function pluginExists( string $slug ): bool
	{
		$pluginFile = sprintf( '%s/%s.php', getcwd(), $slug );
		return file_exists( $pluginFile );
	}

	/**
	 * Ensure all declared command requirements are satisfied.
	 *
	 * @param InputInterface $input The terminal input interface.
	 * @param OutputInterface $output The terminal output interface.
	 *
	 * @return int|null A command status if execution should stop; null to continue.
	 */
	private function ensureRequiredComponents( InputInterface $input, OutputInterface $output ): ?int
	{
		$slug = basename( getcwd() );

		if ( static::$requiresPlugin ) {
			$status = $this->ensurePluginExists( $slug, $input, $output );
			if ( null !== $status ) {
				return $status;
			}
		}

		if ( static::$requiresAdminModule ) {
			$status = $this->ensureAdminModuleExists( $slug, $input, $output );
			if ( null !== $status ) {
				return $status;
			}
		}

		if ( static::$requiresPostTypes ) {
			$status = $this->ensurePostTypesExist( $input, $output );
			if ( null !== $status ) {
				return $status;
			}
		}

		return null;
	}

	/**
	 * Ensure the plugin file exists, optionally creating it through the plugin command.
	 *
	 * @param string $slug The current plugin slug.
	 * @param InputInterface $input The terminal input interface.
	 * @param OutputInterface $output The terminal output interface.
	 *
	 * @return int|null A command status if execution should stop; null to continue.
	 */
	private function ensurePluginExists( string $slug, InputInterface $input, OutputInterface $output ): ?int
	{
		if ( $this->pluginExists( $slug ) ) {
			return null;
		}

		$output->writeln(
			StyleUtil::color(
				sprintf( 'Main plugin file not found (%s.php).', $slug ),
				ConsoleColor::Yellow
			)
		);

		if ( ! $input->isInteractive() ) {
			$output->writeln( StyleUtil::error(
				'This command requires an existing plugin file in non-interactive mode.'
			) );
			return Command::FAILURE;
		}

		if ( ! $this->askYesNo( $input, $output, 'Do you want to create the primary plugin file?' ) ) {
			$output->writeln( StyleUtil::color(
				'You must create a plugin before running this command.',
				ConsoleColor::Yellow
			) );
			return Command::SUCCESS;
		}

		// Run the `make:plugin` command
		$status = $this->runDependencyCommand( 'make:plugin', $input, $output );

		if ( Command::SUCCESS !== $status || ! $this->pluginExists( $slug ) ) {
			$output->writeln( StyleUtil::error(
				'The plugin file was not created. Aborting command execution.'
			) );
			return Command::FAILURE;
		}

		return null;
	}

	/**
	 * Ensure the admin module exists, optionally creating it through the admin command.
	 *
	 * @param string $slug The current plugin slug.
	 * @param InputInterface $input The terminal input interface.
	 * @param OutputInterface $output The terminal output interface.
	 *
	 * @return int|null A command status if execution should stop; null to continue.
	 */
	private function ensureAdminModuleExists( string $slug, InputInterface $input, OutputInterface $output ): ?int
	{
		if ( $this->adminModuleExists( $slug ) ) {
			return null;
		}

		$output->writeln(
			StyleUtil::color(
				sprintf( 'Admin module file not found (%s/admin/%s-admin.php).', getcwd(), $slug ),
				ConsoleColor::Yellow
			)
		);

		if ( ! $input->isInteractive() ) {
			$output->writeln( StyleUtil::error(
				'This command requires an admin module file in non-interactive mode.'
			) );
			return Command::FAILURE;
		}

		if ( ! $this->askYesNo( $input, $output, 'Do you want to create the admin module file?' ) ) {
			$output->writeln( StyleUtil::color(
				'You must create an admin module before running this command.',
				ConsoleColor::Yellow
			) );
			return Command::SUCCESS;
		}

		// Run the `make:plugin-admin` command
		$status = $this->runDependencyCommand( 'make:plugin-admin', $input, $output );

		if ( Command::SUCCESS !== $status || ! $this->adminModuleExists( $slug ) ) {
			$output->writeln( StyleUtil::error(
				'The admin module file was not created. Aborting command execution.'
			) );
			return Command::FAILURE;
		}

		return null;
	}

	/**
	 * Ensure at least one post type exists, optionally creating one with the post type command.
	 *
	 * @param InputInterface $input The terminal input interface.
	 * @param OutputInterface $output The terminal output interface.
	 *
	 * @return int|null A command status if execution should stop; null to continue.
	 */
	private function ensurePostTypesExist( InputInterface $input, OutputInterface $output ): ?int
	{
		if ( ! empty( $this->postTypeFiles() ) ) {
			return null;
		}

		$output->writeln(
			StyleUtil::color(
				sprintf( 'No post types currently exist in `%s`.', CreatePostTypeCommand::POST_TYPES_DIR ),
				ConsoleColor::Yellow
			)
		);

		if ( ! $input->isInteractive() ) {
			$output->writeln( StyleUtil::error(
				'This command requires at least one post type in non-interactive mode.'
			) );
			return Command::FAILURE;
		}

		if ( ! $this->askYesNo( $input, $output, 'Do you want to create a post type now?' ) ) {
			$output->writeln( StyleUtil::color(
				'You must create a post type before running this command.',
				ConsoleColor::Yellow
			) );
			return Command::SUCCESS;
		}

		// Run the `make:post-type` command
		$status = $this->runDependencyCommand( 'make:post-type', $input, $output );

		if ( Command::SUCCESS !== $status || empty( $this->postTypeFiles() ) ) {
			$output->writeln( StyleUtil::error(
				'No post type was created. Aborting command execution.'
			) );
			return Command::FAILURE;
		}

		return null;
	}

	/**
	 * Ask the user to confirm creation of a missing prerequisite component.
	 *
	 * @param InputInterface $input The terminal input interface.
	 * @param OutputInterface $output The terminal output interface.
	 * @param string $questionText The question to ask.
	 *
	 * @return bool True if the user confirms creation.
	 */
	private function askYesNo( InputInterface $input, OutputInterface $output, string $questionText ): bool
	{
		$q = sprintf( '%s %s ', $questionText, StyleUtil::color( '(yes/no)', ConsoleColor::Yellow ) );
		$question = new Question( $q );
		$question->setValidator( CliUtil::yesNoValidator() );

		// Ask the yes/no question
		$answer = ( new QuestionHelper )->ask( $input, $output, $question );

		return in_array( $answer, [ 'yes', 'y' ], true );
	}

	/**
	 * Resolve and run a dependency command.
	 *
	 * @param string $commandName The Symfony command name.
	 * @param InputInterface $input The terminal input interface.
	 * @param OutputInterface $output The terminal output interface.
	 *
	 * @return int The dependency command status.
	 */
	private function runDependencyCommand( string $commandName, InputInterface $input, OutputInterface $output ): int
	{
		$command = $this->getApplication()->find( $commandName );

		if ( null === $command ) {
			$output->writeln( StyleUtil::error(
				sprintf( 'Dependency command `%s` could not be resolved.', $commandName )
			) );
			return Command::FAILURE;
		}

		$dependencyInput = new ArrayInput( [] );
		$dependencyInput->setInteractive( $input->isInteractive() );

		return $command->run( $dependencyInput, $output );
	}

	/**
	 * List post type files available in includes/post-types.
	 *
	 * @return array<string> The list of post type file names.
	 */
	private function postTypeFiles(): array
	{
		$directory = sprintf( '%s/%s', getcwd(), CreatePostTypeCommand::POST_TYPES_DIR );

		if ( ! is_dir( $directory ) ) {
			return [];
		}

		$files = Utility::scandir( $directory, 'files' );

		if ( false === $files ) {
			return [];
		}

		$files = array_map( 'basename', $files );
		sort( $files );

		return $files;
	}
}
