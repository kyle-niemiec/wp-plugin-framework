<?php
/**
 * Tests for the CreatePostScreenCommand CLI command.
 *
 * @package WPPF
 */

namespace WPPF\Tests\CLI\Command;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use WPPF\CLI\Command\CreatePostScreenCommand;
use WPPF\CLI\Command\CreatePostTypeCommand;
use WPPF\Tests\Support\CliPluginTestCase;

/**
 * Test the command to create a post screen.
 */
final class CreatePostScreenCommandTest extends CliPluginTestCase
{
	/** @inheritDoc */
	protected static bool $usesMockPostType = true;

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
		// Select the first post type
		$this->tester->setInputs( [ '0' ] );

		// Ensure the command completes successfully
		$status = $this->tester->execute( [], [ 'interactive' => true ] );
		self::assertSame( Command::SUCCESS, $status );

		// Test if the file is created
		$output = 'admin/includes/screens/class-test-post-type-post-screens.php';
		self::assertFileExists( $output );

		// Test the file contents
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
		$output = 'admin/includes/screens/class-test-post-type-post-screens.php';

		// Create the file before the test
		$directory = dirname( $output );

		if ( ! is_dir( $directory ) ) {
			mkdir( $directory, 0777, true );
		}

		file_put_contents( $output, "<?php\n// Test post screen.\n" );

		// Select the first post type
		$this->tester->setInputs( [ '0' ] );

		// Ensure the command fails
		$status = $this->tester->execute( [], [ 'interactive' => true ] );
		self::assertSame( Command::FAILURE, $status );
		self::assertFileExists( $output );

		self::assertStringContainsString(
			'already exists',
			$this->tester->getDisplay()
		);
	}

	/**
	 * Fail: No post types exist for selection.
	 */
	#[Test]
	public function testNoPostTypesAvailableFail(): void
	{
		// Delete all existing post types
		self::rmdirRecursive( CreatePostTypeCommand::POST_TYPES_DIR );

		// Expect command to throw an exception
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'No post types currently exist' );
		$this->tester->execute( [], [ 'interactive' => true ] );
	}

}
