<?php
/**
 * Tests for the CreatePluginCommand CLI command.
 *
 * @package WPPF
 */

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use WPPF\CLI\Command\CreatePluginCommand;
use WPPF\CLI\Static\CliUtil;
use WPPF\Tests\Support\CliPluginTestCase;

final class CreatePluginCommandTest extends CliPluginTestCase
{
	/**
	 * @inheritDoc
	 */
	public static function getCommand(): Command { return new CreatePluginCommand; }

	/**
	 * Pass: Create a new folder and use the command to create a file inside of it.
	 */
	#[Test]
	public function testCommandCreatesPluginPass(): void
	{
		$command = self::$console->find( 'make:plugin' );
		$tester = new CommandTester( $command );

		$tester->setInputs( [
			'Sample Plugin',
			'https://example.com/plugin',
			'A sample description.',
			'Jane Developer',
			'https://example.com/author',
		] );

		// Assert command status code success
		$status = $tester->execute( [], [ 'interactive' => true ] );
		self::assertSame( Command::SUCCESS, $status );

		// Assert the generate file exists
		$output =  self::PLUGIN_SLUG . '.php';
		self::assertFileExists( $output );

		// Assert the template variables are all as expected
		$contents = file_get_contents( $output );
		$class = CliUtil::plugin_class_name( self::PLUGIN_SLUG );

		self::assertStringContainsString( 'Plugin Name: Sample Plugin', $contents );
		self::assertStringContainsString( 'Plugin URI: https://example.com/plugin', $contents );
		self::assertStringContainsString( 'Description: A sample description.', $contents );
		self::assertStringContainsString( 'Author: Jane Developer', $contents );
		self::assertStringContainsString( 'Author URI: https://example.com/author', $contents );
		self::assertStringContainsString( "class {$class} extends Plugin", $contents );
	}

	/**
	 * Fail: A plugin file already exists in the current directory.
	 */
	#[Test]
	public function testPluginFileAlreadyExistsFail(): void
	{
		$pluginFile = self::PLUGIN_SLUG . '.php';
		$command = self::$console->find( 'make:plugin' );
		$tester = new CommandTester( $command );
		$status = $tester->execute( [] );

		// Assert the Command fails if the plugin file exists
		self::assertSame( Command::FAILURE, $status );
		self::assertFileExists( $pluginFile );

		self::assertStringContainsString(
			'Error: A plugin file already exists in this directory.',
			$tester->getDisplay()
		);
	}

}
