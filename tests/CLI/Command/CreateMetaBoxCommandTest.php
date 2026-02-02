<?php
/**
 * Tests for the CreateMetaBoxCommand CLI command.
 *
 * @package WPPF
 */

namespace WPPF\Tests\CLI\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use WPPF\CLI\Command\CreateMetaBoxCommand;
use WPPF\CLI\Command\CreatePostTypeCommand;
use WPPF\Tests\Support\CliPluginTestCase;

/**
 * Test the command to create a meta box.
 */
final class CreateMetaBoxCommandTest extends CliPluginTestCase
{
	/** @inheritDoc */
	protected static bool $usesMockAdmin = true;

	/** @inheritDoc */
	protected static bool $usesMockPostType = true;

	/**
	 * @inheritDoc
	 */
	public static function getCommand(): Command { return new CreateMetaBoxCommand; }

	/**
	 * Pass: Create a meta box class and render the template.
	 */
	#[Test]
	#[DataProvider( 'metaBoxInputProvider' )]
	public function testCommandCreatesMetaBoxPass( array $inputs ): void
	{
		$this->tester->setInputs( array_merge( $inputs, [ 'y' ] ) );

		$status = $this->tester->execute( [], [ 'interactive' => true ] );
		self::assertSame( Command::SUCCESS, $status );

		$classFile = 'admin/includes/meta-boxes/class-test-box-meta-box.php';
		$templateFile = 'admin/templates/test-box-meta-box-template.php';

		self::assertFileExists( $classFile );
		self::assertFileExists( $templateFile );

		$contents = file_get_contents( $classFile );
		self::assertStringContainsString( 'final class Test_Box_Meta_Box extends Meta_Box', $contents );
		self::assertStringContainsString( "return 'test_box_meta';", $contents );
		self::assertStringContainsString( "return 'test_box';", $contents );

		$templateContents = file_get_contents( $templateFile );
		self::assertStringContainsString( 'This is the output of Test_Box_Meta_Box.', $templateContents );

		$postTypeContents = file_get_contents( CreatePostTypeCommand::POST_TYPES_DIR . '/class-test-post-type.php' );
		self::assertStringContainsString( '$this->add_meta_box( Test_Box_Meta_Box::instance() );', $postTypeContents );
	}

	/**
	 * Fail: A meta box file already exists in the current directory.
	 */
	#[Test]
	#[DataProvider( 'metaBoxInputProvider' )]
	public function testMetaBoxFileAlreadyExistsFail( array $inputs ): void
	{
		$classFile = 'admin/includes/meta-boxes/class-test-box-meta-box.php';

		if ( ! is_dir( dirname( $classFile ) ) ) {
			mkdir( dirname( $classFile ), 0777, true );
		}

		file_put_contents( $classFile, "<?php\n// Test meta box.\n" );

		$this->tester->setInputs( $inputs );

		$status = $this->tester->execute( [], [ 'interactive' => true ] );
		self::assertSame( Command::FAILURE, $status );
		self::assertFileExists( $classFile );

		self::assertStringContainsString(
			'Error: The meta box file or template file already exist.',
			$this->tester->getDisplay()
		);
	}

	/**
	 * Provide console inputs for the command prompts.
	 *
	 * @return array The user inputs for the command
	 */
	public static function metaBoxInputProvider(): array
	{
		return [
			'default inputs' => [
				[
					'0',
					'Test Box',
					'',
					'',
				],
			],
		];
	}
}
