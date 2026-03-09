<?php
/**
 * Tests for the CreatePluginCommand CLI command.
 *
 * @package WPPF
 */

namespace WPPF\Tests\CLI\Command;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use WPPF\CLI\Command\CreatePluginCommand;
use WPPF\CLI\Util\CliUtil;
use WPPF\Tests\Support\CliPluginTestCase;

final class CreatePluginCommandTest extends CliPluginTestCase
{
	/**
	 * @inheritDoc
	 */
	public static function getCommand(): Command { return new CreatePluginCommand; }

	/**
	 * Pass: Create a new folder and use the command to create a file inside of it.
	 */
	#[Test]
	public function testCommandCreatesPluginPass(): void
	{
		$output = self::PLUGIN_SLUG . '.php';
		if ( file_exists( $output ) ) {
			unlink( $output );
		}

		$this->tester->setInputs( [
			'Sample Plugin',
			'https://example.com/plugin',
			'A sample description.',
			'Jane Developer',
			'https://example.com/author',
		] );

		// Assert command status code success
		$status = $this->tester->execute( [], [ 'interactive' => true ] );
		self::assertSame( Command::SUCCESS, $status );

		// Assert the generate file exists
		self::assertFileExists( $output );

		// Assert the template variables are all as expected
		$contents = file_get_contents( $output );
		$class = CliUtil::underscorify( self::PLUGIN_SLUG, true );

		self::assertStringContainsString( 'Plugin Name: Sample Plugin', $contents );
		self::assertStringContainsString( 'Plugin URI: https://example.com/plugin', $contents );
		self::assertStringContainsString( 'Description: A sample description.', $contents );
		self::assertStringContainsString( 'Author: Jane Developer', $contents );
		self::assertStringContainsString( 'Author URI: https://example.com/author', $contents );
		self::assertStringContainsString( "class {$class} extends Plugin", $contents );
	}

	/**
	 * Pass: Create a WooCommerce plugin wrapper when the --woocommerce option is passed.
	 */
	#[Test]
	public function testCommandCreatesWooCommercePluginPass(): void
	{
		$output = self::PLUGIN_SLUG . '.php';
		if ( file_exists( $output ) ) {
			unlink( $output );
		}

		$this->tester->setInputs( [
			'Sample WC Plugin',
			'https://example.com/wc-plugin',
			'A sample WooCommerce plugin.',
			'Jane Developer',
			'https://example.com/author',
		] );

		$status = $this->tester->execute( [ '--woocommerce' => true ], [ 'interactive' => true ] );
		self::assertSame( Command::SUCCESS, $status );

		self::assertFileExists( $output );

		$contents = file_get_contents( $output );

		self::assertStringContainsString( 'Plugin Name: Sample WC Plugin', $contents );
		self::assertStringContainsString( 'Plugin URI: https://example.com/wc-plugin', $contents );
		self::assertStringContainsString( 'Description: A sample WooCommerce plugin.', $contents );
		self::assertStringContainsString( 'Author: Jane Developer', $contents );
		self::assertStringContainsString( 'Author URI: https://example.com/author', $contents );
		self::assertStringContainsString( 'use WPPF\\v1_2_2\\WooCommerce\\WooCommerce_Plugin;', $contents );
		self::assertStringContainsString( 'class WC_Plugin extends WooCommerce_Plugin', $contents );
	}

	/**
	 * Fail: A plugin file already exists in the current directory.
	 */
	#[Test]
	public function testPluginFileAlreadyExistsFail(): void
	{
		$pluginFile = self::PLUGIN_SLUG . '.php';
		file_put_contents( $pluginFile, "<?php\n// Existing plugin file.\n" );
		$status = $this->tester->execute( [] );

		// Assert the Command fails if the plugin file exists
		self::assertSame( Command::FAILURE, $status );
		self::assertFileExists( $pluginFile );

		self::assertStringContainsString(
			'Error: A plugin file already exists in this directory.',
			$this->tester->getDisplay()
		);
	}

}
