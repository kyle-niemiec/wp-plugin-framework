<?php
/**
 * Tests for the CreatePostTypeCommand CLI command.
 *
 * @package WPPF
 */

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use WPPF\CLI\Command\CreatePostTypeCommand;
use WPPF\Tests\Support\CliPluginTestCase;

/**
 * A test case for ensuring the Post Type creation script works.
 */
final class CreatePostTypeCommandTest extends CliPluginTestCase
{
	/**
	 * @inheritDoc
	 */
	public static function getCommand(): Command { return new CreatePostTypeCommand; }

	/**
	 * Pass: Create a post type class in the expected folder.
	 */
	#[Test]
	public function testCommandCreatesPostTypePass(): void
	{
		$command = self::$console->find( 'make:post-type' );
		$tester = new CommandTester( $command );

		$tester->setInputs( [
			'Book',
			'Books',
			'yes',
			'Library',
		] );

		$status = $tester->execute( [], [ 'interactive' => true ] );
		self::assertSame( Command::SUCCESS, $status );

		$output = 'includes/post-types/class-book.php';
		self::assertFileExists( $output );

		$contents = file_get_contents( $output );

		self::assertStringContainsString( "final class Book extends Post_Type", $contents );
		self::assertStringContainsString( "return 'book';", $contents );
		self::assertStringContainsString( "'menu_name' => __( 'Library' )", $contents );
		self::assertStringContainsString( "'singular_name'\t=> __( 'Book' )", $contents );
		self::assertStringContainsString( "'plural_name'\t=> __( 'Books' )", $contents );
		self::assertStringContainsString( "'show_in_menu'\t=> true", $contents );
	}

	/**
	 * Fail: A post type file already exists in the current directory.
	 */
	#[Test]
	public function testPostTypeFileAlreadyExistsFail(): void
	{
		$targetDir = 'includes/post-types';
		$targetFile = $targetDir . '/class-album.php';

		if ( ! is_dir( $targetDir ) ) {
			mkdir( $targetDir, 0777, true );
		}

		if ( ! file_exists( $targetFile ) ) {
			file_put_contents( $targetFile, "<?php\n// Test post type.\n" );
		}

		$command = self::$console->find( 'make:post-type' );
		$tester = new CommandTester( $command );

		$tester->setInputs( [
			'Album',
			'Albums',
			'no',
		] );

		$status = $tester->execute( [], [ 'interactive' => true ] );

		self::assertSame( Command::FAILURE, $status );
		self::assertFileExists( $targetFile );

		self::assertStringContainsString(
			'Error: The post type file already exists.',
			$tester->getDisplay()
		);
	}
}
