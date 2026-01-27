<?php
/**
 * Tests for the CreatePluginCommand CLI command.
 *
 * @package WPPF
 */

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use WPPF\CLI\Command\CreatePluginCommand;
use WPPF\CLI\Static\CliUtil;

final class CreatePluginCommandTest extends TestCase
{
	/** @var string The intended slug used for the root folder of the plugin. */
	public const PLUGIN_SLUG = 'my-test-plugin';

	/** @var Application|null The Symfony Command application which executes our context. */
	private static ?Application $console = null;

	/** @var string|null The directory the command was initiated inside of. */
	private static ?string $cmdDir = null;

	/** @var string|null The writable directory the command will be executed inside of. */
	private static ?string $tmpDir = null;

	/**
	 * Create the test fixtures and temporary directory for the command.
	 */
	public static function setUpBeforeClass(): void
	{
		// Tear down before set up to unlink possibly existing files from previous test runs.
		parent::tearDownAfterClass();
		parent::setUpBeforeClass();

		// Save original directory and go into new one.
		self::$cmdDir = getcwd();

		self::$tmpDir = sprintf(
			'%s/%s/%s',
			rtrim( sys_get_temp_dir(), '/' ),
			'wppf-tests',
			self::PLUGIN_SLUG
		);

		if ( ! is_dir( self::$tmpDir ) ) {
			mkdir( self::$tmpDir, 0777, true );
		}

		chdir( self::$tmpDir );

		// Create the Symfony application
		self::$console = new Application;
		self::$console->add( new CreatePluginCommand );
	}

	/**
	 * Remove the temporary directory after the command runs.
	 */
	public static function tearDownAfterClass(): void
	{
		parent::tearDownAfterClass();
		$filename = self::PLUGIN_SLUG . '.php';

		if ( file_exists( $filename ) ) {
			unlink( $filename );
		}

		chdir( self::$cmdDir );

		if ( is_dir( self::$tmpDir ) && count( scandir( self::$tmpDir ) ) === 2 ) {
			rmdir( self::$tmpDir );
		}

		self::$console = null;
		self::$cmdDir = null;
		self::$tmpDir = null;
	}

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
