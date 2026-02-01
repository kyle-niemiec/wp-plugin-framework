<?php
/**
 * Tests for the CreateMetaBoxCommand CLI command.
 *
 * @package WPPF
 */

namespace WPPF\Tests\CLI\Command;

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
	protected static bool $usesMockPostType = true;

	/**
	 * @inheritDoc
	 */
	public static function getCommand(): Command { return new CreateMetaBoxCommand; }

	/**
	 * Ensure the mock post type includes a constructor for insertion.
	 */
	protected function setUp(): void
	{
		parent::setUp();

		if ( ! is_dir( 'admin' ) ) {
			mkdir( 'admin', 0777, true );
		}

		file_put_contents( 'admin/' . self::PLUGIN_SLUG . '-admin.php', "<?php\n// Test admin plugin.\n" );

		$postTypeFile = CreatePostTypeCommand::POST_TYPES_DIR . '/class-test-post-type.php';
		file_put_contents(
			$postTypeFile,
			"<?php\nfinal class Test_Post_Type {\n\tpublic function __construct() {\n\t}\n}\n"
		);
	}

	/**
	 * Pass: Create a meta box class and render template.
	 */
	#[Test]
	public function testCommandCreatesMetaBoxPass(): void
	{
		$this->tester->setInputs( [
			'0',
			'Test Box',
			'',
			'',
			'y',
		] );

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
}
