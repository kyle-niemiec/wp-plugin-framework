<?php
/**
 * Tests for the CreatePluginAdminCommand CLI command.
 *
 * @package WPPF
 */

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use WPPF\CLI\Command\CreatePluginAdminCommand;
use WPPF\CLI\Static\CliUtil;

final class CreatePluginAdminCommandTest extends TestCase
{
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
			CreatePluginCommandTest::PLUGIN_SLUG
		);

		if ( ! is_dir( self::$tmpDir ) ) {
			mkdir( self::$tmpDir, 0777, true );
		}

		chdir( self::$tmpDir );

		// Ensure a base plugin file exists for the admin module to attach to.
		$plugin_file = CreatePluginCommandTest::PLUGIN_SLUG . '.php';
		if ( ! file_exists( $plugin_file ) ) {
			file_put_contents( $plugin_file, "<?php\n// Test plugin.\n" );
		}

		// Create the Symfony application
		self::$console = new Application;
		self::$console->add( new CreatePluginAdminCommand );
	}

	/**
	 * Remove the temporary directory after the command runs.
	 */
	public static function tearDownAfterClass(): void
	{
		parent::tearDownAfterClass();

		$plugin_file = CreatePluginCommandTest::PLUGIN_SLUG . '.php';
		$admin_file = sprintf( 'admin/%s-admin.php', CreatePluginCommandTest::PLUGIN_SLUG );

		if ( file_exists( $admin_file ) ) {
			unlink( $admin_file );
		}

		if ( is_dir( 'admin' ) && count( scandir( 'admin' ) ) === 2 ) {
			rmdir( 'admin' );
		}

		if ( file_exists( $plugin_file ) ) {
			unlink( $plugin_file );
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
	 * Pass: Create the admin module file in the admin folder.
	 */
	#[Test]
	public function testCommandCreatesAdminModulePass(): void
	{
		$command = self::$console->find( 'make:plugin-admin' );
		$tester = new CommandTester( $command );

		// Assert command status code success
		$status = $tester->execute( [] );
		self::assertSame( Command::SUCCESS, $status );

		// Assert the generated file exists
		$output = sprintf( 'admin/%s-admin.php', CreatePluginCommandTest::PLUGIN_SLUG );
		self::assertFileExists( $output );

		// Assert the template variables are all as expected
		$contents = file_get_contents( $output );
		$class = sprintf( '%s_Admin', CliUtil::plugin_class_name( CreatePluginCommandTest::PLUGIN_SLUG ) );

		self::assertStringContainsString( "final class {$class} extends Admin_Module", $contents );
	}

	/**
	 * Fail: An admin module file already exists in the current directory.
	 */
	#[Test]
	public function testAdminModuleFileAlreadyExistsFail(): void
	{
		$admin_file = sprintf( 'admin/%s-admin.php', CreatePluginCommandTest::PLUGIN_SLUG );
		$command = self::$console->find( 'make:plugin-admin' );
		$tester = new CommandTester( $command );
		$status = $tester->execute( [] );

		// Assert the Command fails if the admin module file exists
		self::assertSame( Command::FAILURE, $status );
		self::assertFileExists( $admin_file );

		self::assertStringContainsString(
			'Error: The admin module file already exists.',
			$tester->getDisplay()
		);
	}

}
