<?php
/**
 * Tests for the CreatePostTypeMetaCommand CLI command.
 *
 * @package WPPF
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use WPPF\CLI\Command\CreatePostTypeMetaCommand;
use WPPF\Tests\Support\CliPluginTestCase;

/**
 * Test the command to create a post type meta.
 */
final class CreatePostTypeMetaCommandTest extends CliPluginTestCase
{
	/** @var string The expected meta data relative file path. */
	private static $expectedFile = 'includes/classes/class-test-post-type-meta.php';

	/** @inheritDoc */
	protected static bool $usesMockPostType = true;

	/**
	 * @inheritDoc
	 */
	public static function getCommand(): Command { return new CreatePostTypeMetaCommand; }

	/**
	 * @inheritDoc
	 * 
	 * Set up the necessary files.
	 */
	public function setUp(): void
	{
		parent::setUp();

		// Make the meta class directory
		if ( ! is_dir( dirname( self::$expectedFile ) ) ) {
			mkdir( dirname( self::$expectedFile ), 0777, true );
		}
	}

	/**
	 * Pass: Create a post meta class file in the expected folder.
	 */
	#[Test]
	#[DataProvider( 'consoleInputProvider' )]
	public function testCommandCreatesPostTypeMetaPass( array $inputLines ): void
	{
		$input = self::buildConsoleInput( $inputLines );
		$output = new NullOutput;

		$command = new CreatePostTypeMetaCommand;
		$status = $command->run( $input, $output );

		self::assertSame( Command::SUCCESS, $status );
		self::assertFileExists( self::$expectedFile );

		$contents = file_get_contents( self::$expectedFile );

		self::assertStringContainsString( 'final class Test_Post_Type_Meta extends Post_Meta', $contents );
		// Test string
		self::assertStringContainsString( 'public string $str_value;', $contents );
		self::assertStringContainsString( "'str_value' => '',", $contents );
		// Test bool
		self::assertStringContainsString( 'public bool $bool_value;', $contents );
		self::assertStringContainsString( "'bool_value' => true,", $contents );
		// Test integer
		self::assertStringContainsString( 'public int $int_value;', $contents );
		self::assertStringContainsString( "'int_value' => 0,", $contents );
		// Test float
		self::assertStringContainsString( 'public float $float_value;', $contents );
		self::assertStringContainsString( "'float_value' => 0.0,", $contents );
		// Test array
		self::assertStringContainsString( 'public array $arr_value;', $contents );
		self::assertStringContainsString( "'arr_value' => [],", $contents );
	}

	/**
	 * Fail: A post meta file already exists in the current directory.
	 */
	#[Test]
	#[DataProvider( 'consoleInputProvider' )]
	public function testPostTypeMetaFileAlreadyExistsFail( array $inputLines ): void
	{
		if ( ! file_exists( self::$expectedFile ) ) {
			file_put_contents( self::$expectedFile, "<?php\n// Test post meta.\n" );
		}

		$input = self::buildConsoleInput( $inputLines );
		$output = new NullOutput;

		$command = new CreatePostTypeMetaCommand;
		$status = $command->run( $input, $output );

		self::assertSame( Command::FAILURE, $status );
		self::assertFileExists( self::$expectedFile );
	}

	/**
	 * Provide console inputs for the command prompts.
	 *
	 * @return array<string, array<int, array<int, string>>>
	 */
	public static function consoleInputProvider(): array
	{
		return [
			'select first post type, single string field' => [
				[
					'0',
					'str_value', 'string',
					'bool_value', 'boolean',
					'float_value', 'float',
					'int_value', 'integer',
					'arr_value', 'array',
					'' ],
			],
		];
	}

	/**
	 * Build an ArrayInput backed by a memory stream.
	 *
	 * @param array<int, string> $lines The input lines to feed into the command.
	 *
	 * @return ArrayInput The configured input instance.
	 */
	private static function buildConsoleInput( array $lines ): ArrayInput
	{
		$inputStream = fopen( 'php://memory', 'r+' );
		fwrite( $inputStream, implode( "\n", $lines ) . "\n" );
		rewind( $inputStream );

		$input = new ArrayInput( [] );
		$input->setStream( $inputStream );
		$input->setInteractive( true );

		return $input;
	}
}
