<?php
/**
 * Tests for the CreateModuleCommand CLI command.
 *
 * @package WPPF
 */

namespace WPPF\Tests\CLI\Command;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use WPPF\CLI\Command\CreateModuleCommand;
use WPPF\Tests\Support\CliPluginTestCase;

final class CreateModuleCommandTest extends CliPluginTestCase
{
	/** @inheritDoc */
	protected static bool $usesMockPlugin = true;

	/**
	 * @inheritDoc
	 */
	public static function getCommand(): Command { return new CreateModuleCommand; }

	/**
	 * Pass: Create a new module class in includes/modules.
	 */
	#[Test]
	public function testCommandCreatesModulePass(): void
	{
		$output = 'includes/modules/class-my-module.php';

		if ( file_exists( $output ) ) {
			unlink( $output );
		}

		$this->tester->setInputs( [
			'My Module',
		] );

		$status = $this->tester->execute( [], [ 'interactive' => true ] );
		self::assertSame( Command::SUCCESS, $status );
		self::assertFileExists( $output );

		$contents = file_get_contents( $output );
		self::assertStringContainsString( 'final class My_Module extends Module', $contents );
	}

	/**
	 * Fail: A module file already exists.
	 */
	#[Test]
	public function testModuleFileAlreadyExistsFail(): void
	{
		$output = 'includes/modules/class-my-module.php';

		if ( ! is_dir( dirname( $output ) ) ) {
			mkdir( dirname( $output ), 0777, true );
		}

		file_put_contents( $output, "<?php\n// Existing module file.\n" );

		$this->tester->setInputs( [
			'My Module',
		] );

		$status = $this->tester->execute( [], [ 'interactive' => true ] );
		self::assertSame( Command::FAILURE, $status );
		self::assertFileExists( $output );

		self::assertStringContainsString(
			'Error: The module file already exists.',
			$this->tester->getDisplay()
		);
	}
}
