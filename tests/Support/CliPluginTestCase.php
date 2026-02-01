<?php
/**
 * Base test case for CLI plugin filesystem setup/teardown.
 *
 * @package WPPF\Tests
 */

namespace WPPF\Tests\Support;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use WPPF\CLI\Command\CreatePostTypeCommand;

/**
 * An overlying class for WPPF CLI commands that need to construct a mock file system to run.
 */
abstract class CliPluginTestCase extends TestCase
{
	/** @var string The intended slug used for the root folder of the plugin. */
	public const PLUGIN_SLUG = 'my-test-plugin';

	/** @var Command The CLI command to be tested. */
	protected static Command $command;

	/** @var string|null The name of the CLI command to be tested. */
	protected static ?string $commandName = null;

	/** @var Application|null The Symfony Command application which executes our context. */
	protected static ?Application $console = null;

	/** @var string|null The directory the command was initiated inside of. */
	protected static ?string $cmdDir = null;

	/** @var bool True if the test case requires a mock admin file and folder to be generated. */
	protected static bool $usesMockAdmin = false;

	/** @var bool True if the test case requires a mock post type fixture. */
	protected static bool $usesMockPostType = false;

	/** @var bool True if the test case requires a mock plugin file to be generated. */
	protected static bool $usesMockPlugin = false;

	/** @var string|null The writable directory the command will be executed inside of. */
	protected static ?string $tmpDir = null;

	/** @var CommandTester The command tester for the current test. */
	protected CommandTester $tester;

	/**
	 * Set the command the test will be running on.
	 */
	abstract public static function getCommand(): Command;

	/**
	 * Create the test fixtures and temporary directory for the command.
	 */
	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		// Save original directory and go into the temp directory.
		static::$cmdDir = getcwd();

		static::$tmpDir = sprintf(
			'%s/%s/%s',
			rtrim( sys_get_temp_dir(), '/' ),
			'wppf-tests',
			self::PLUGIN_SLUG
		);

		self::rmdirRecursive( static::$tmpDir );
		mkdir( static::$tmpDir, 0777, true );
		chdir( static::$tmpDir );

		// Create the Symfony application
		static::$console = new Application;
		static::$command = static::getCommand();
		static::$console->add( static::$command );

		static::$commandName = static::$command->getName();
		if ( null === static::$commandName ) {
			foreach ( static::$console->all() as $name => $command ) {
				if ( $command === static::$command ) {
					static::$commandName = $name;
					break;
				}
			}
		}

		// Create mock files
		if ( static::$usesMockPlugin ) {
			self::createMockPluginFile();
		}

		if ( static::$usesMockAdmin ) {
			self::createMockAdminFile();
		}

		if ( static::$usesMockPostType ) {
			self::createMockPostTypeFile();
		}
	}

	/**
	 * Set up the CommandTester for the current test.
	 */
	protected function setUp(): void
	{
		parent::setUp();

		if ( null === static::$commandName ) {
			foreach ( static::$console?->all() ?? [] as $name => $command ) {
				if ( $command === static::$command ) {
					static::$commandName = $name;
					break;
				}
			}
		}

		if ( null === static::$commandName ) {
			throw new \RuntimeException( 'Command name not available for CommandTester.' );
		}

		$this->tester = new CommandTester( static::$console->find( static::$commandName ) );
	}

	/**
	 * Remove the temporary directory after the command runs.
	 */
	public static function tearDownAfterClass(): void
	{
		chdir( self::$cmdDir );
		self::rmdirRecursive( static::$tmpDir );

		static::$cmdDir = null;
		static::$console = null;
		static::$tmpDir = null;

		parent::tearDownAfterClass();
	}

	/**
	 * Create a mock plugin file for commands to use.
	 */
	private static function createMockPluginFile(): void
	{
		// Ensure a base plugin file exists for the admin module to attach to.
		$pluginFile = self::PLUGIN_SLUG . '.php';

		if ( ! file_exists( $pluginFile ) ) {
			file_put_contents( $pluginFile, "<?php\n// Test plugin.\n" );
		}
	}


	/**
	 * Create a mock plugin admin file for commands to use.
	 */
	private static function createMockAdminFile(): void
	{
		// Ensure a base plugin file exists for the admin module to attach to.
		$pluginAdminFile = sprintf( 'admin/%s.php', self::PLUGIN_SLUG );

		if ( ! file_exists( $pluginAdminFile ) ) {
			file_put_contents( $pluginAdminFile, "<?php\n// Test admin plugin.\n" );
		}
	}

	/**
	 * Create a mock post type file for commands to use.
	 */
	protected static function createMockPostTypeFile(): void
	{
		if ( ! is_dir( CreatePostTypeCommand::POST_TYPES_DIR ) ) {
			mkdir( CreatePostTypeCommand::POST_TYPES_DIR, 0777, true );
		}

		$postTypeFile = CreatePostTypeCommand::POST_TYPES_DIR . '/class-test-post-type.php';

		if ( ! file_exists( $postTypeFile ) ) {
			file_put_contents( $postTypeFile, "<?php\nfinal class Test_Post_Type {}\n" );
		}
	}

	/**
	 * Force remve a folder and all it's contents. Graciously sourced from https://stackoverflow.com/questions/1296681/php-simplest-way-to-delete-a-folder-including-its-contents.
	 * 
	 * @param string $dir The directory to remove.
	 */
	protected static function rmdirRecursive( string $dir ): void
	{
		if ( ! is_dir( $dir ) ) {
			return;
		}

		foreach ( glob( $dir . '/*' ) as $file ) {
			if ( is_dir( $file ) ) {
				self::rmdirRecursive( $file );
			} else {
				unlink( $file );
			}
		}

		rmdir( $dir );
	}

}
