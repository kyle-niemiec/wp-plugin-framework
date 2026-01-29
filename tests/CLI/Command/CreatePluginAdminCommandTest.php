<?php
/**
 * Tests for the CreatePluginAdminCommand CLI command.
 *
 * @package WPPF
 */

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use WPPF\CLI\Command\CreatePluginAdminCommand;
use WPPF\CLI\Static\CliUtil;
use WPPF\Tests\Support\CliPluginTestCase;

final class CreatePluginAdminCommandTest extends CliPluginTestCase
{
	/** @inheritDoc */
	protected static bool $usesMockPlugin = true;

	/**
	 * @inheritDoc
	 */
	public static function getCommand(): Command { return new CreatePluginAdminCommand; }

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
