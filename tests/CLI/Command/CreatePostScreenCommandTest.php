<?php
/**
 * Tests for the CreatePostScreenCommand CLI command.
 *
 * @package WPPF
 */

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
	 * @inheritDoc
	 */
	protected function setUp(): void
	{
		parent::setUp();
		self::rmdirRecursive( 'admin' );
		self::createMockPostTypeFile();
	}

	/**
	 * Pass: Create a post screen class file in the expected folder.
	 */
	#[Test]
	public function testCommandCreatesPostScreenPass(): void
	{
		$this->tester->setInputs( [ '0' ] );

		$status = $this->tester->execute( [], [ 'interactive' => true ] );
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
		$output = 'admin/includes/screens/class-test-post-type-post-screens.php';

		if ( ! is_dir( dirname( $output ) ) ) {
			mkdir( dirname( $output ), 0777, true );
		}

		if ( ! file_exists( $output ) ) {
			file_put_contents( $output, "<?php\n// Test post screen.\n" );
		}

		$this->tester->setInputs( [ '0' ] );

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
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'No post types currently exist' );

		self::rmdirRecursive( CreatePostTypeCommand::POST_TYPES_DIR );

		try {
			$this->tester->execute( [], [ 'interactive' => true ] );
		} finally {
			self::createMockPostTypeFile();
		}
	}

}
