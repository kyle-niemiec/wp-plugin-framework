<?php
/**
 * Tests for the CreatePostScreenCommand CLI command.
 *
 * @package WPPF
 */

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use WPPF\CLI\Command\CreatePostScreenCommand;
use WPPF\CLI\Command\CreatePostTypeCommand;
use WPPF\Tests\Support\CliPluginTestCase;

/**
 * Test the command to create a post screen.
 */
final class CreatePostScreenCommandTest extends CliPluginTestCase
{
	/**
	 * @inheritDoc
	 */
	public static function getCommand(): Command { return new CreatePostScreenCommand; }

	/**
	 * Pass: Create a post screen class file in the expected folder.
	 */
	#[Test]
	public function testCommandCreatesPostScreenPass(): void
	{
		self::resetFixtureDirs();
		self::createPostTypeFixture();

		$command = self::$console->find( 'make:post-screen' );
		$tester = new CommandTester( $command );
		$tester->setInputs( [ '0' ] );

		$status = $tester->execute( [], [ 'interactive' => true ] );
		self::assertSame( Command::SUCCESS, $status );

		$output = 'admin/includes/screens/class-test-post-type-post-screens.php';
		self::assertFileExists( $output );

		$contents = file_get_contents( $output );
		self::assertStringContainsString(
			'final class Test_Post_Type_Post_Screens extends Post_Screens',
			$contents
		);
		self::assertStringContainsString(
			'return Test_Post_Type::post_type();',
			$contents
		);
	}

	/**
	 * Fail: A post screen file already exists in the current directory.
	 */
	#[Test]
	public function testPostScreenFileAlreadyExistsFail(): void
	{
		self::resetFixtureDirs();
		self::createPostTypeFixture();

		$output = 'admin/includes/screens/class-test-post-type-post-screens.php';

		if ( ! is_dir( dirname( $output ) ) ) {
			mkdir( dirname( $output ), 0777, true );
		}

		if ( ! file_exists( $output ) ) {
			file_put_contents( $output, "<?php\n// Test post screen.\n" );
		}

		$command = self::$console->find( 'make:post-screen' );
		$tester = new CommandTester( $command );
		$tester->setInputs( [ '0' ] );

		$status = $tester->execute( [], [ 'interactive' => true ] );
		self::assertSame( Command::FAILURE, $status );
		self::assertFileExists( $output );

		self::assertStringContainsString(
			'already exists',
			$tester->getDisplay()
		);
	}

	/**
	 * Fail: No post types exist for selection.
	 */
	#[Test]
	public function testNoPostTypesAvailableFail(): void
	{
		self::resetFixtureDirs();

		$command = self::$console->find( 'make:post-screen' );
		$tester = new CommandTester( $command );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'No post types currently exist' );

		$tester->execute( [], [ 'interactive' => true ] );
	}

	/**
	 * Create the post type fixture used for selecting a post type.
	 */
	private static function createPostTypeFixture(): void
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
	 * Reset any fixture directories used in the tests.
	 */
	private static function resetFixtureDirs(): void
	{
		self::rmdirRecursive( 'includes' );
		self::rmdirRecursive( 'admin' );
	}

}
