<?php

namespace KofiMokome\WordPressTools\Tests\Unit;

use Brain\Monkey\Functions;
use KMEnv;
use PHPUnit\Framework\TestCase;

class KMEnvTest extends TestCase {

	private string $pluginDir;

	protected function setUp(): void {
		parent::setUp();
		$this->pluginDir = WP_PLUGIN_DIR . '/my-plugin';
		if ( ! is_dir( $this->pluginDir ) ) {
			mkdir( $this->pluginDir, 0777, true );
		}
	}

	protected function tearDown(): void {
		parent::tearDown();
		\Brain\Monkey\tearDown();

		// Clean up files created during the test.
		$files = glob( $this->pluginDir . '/*' );
		if ( $files ) {
			foreach ( $files as $file ) {
				unlink( $file );
			}
		}
	}

	private function mockPluginPaths(): void {
		Functions\expect( 'plugin_dir_path' )
			->once()
			->with( '/plugins/my-plugin/my-plugin.php' )
			->andReturn( '/plugins/my-plugin/' );

		Functions\expect( 'plugin_basename' )
			->once()
			->with( '/plugins/my-plugin/my-plugin.php' )
			->andReturn( 'my-plugin/my-plugin.php' );
	}

	public function test_get_env_returns_parsed_values(): void {
		$this->mockPluginPaths();

		file_put_contents(
			$this->pluginDir . '/config.env',
			"TABLE_PREFIX=mp_\nVIEWS_DIR=/views\n# comment\n\nMIGRATIONS_DIR=/migrations\n"
		);

		$env    = new KMEnv( '/plugins/my-plugin/my-plugin.php' );
		$values = $env->getEnv();

		$this->assertSame( 'mp_', $values['TABLE_PREFIX'] );
		$this->assertSame( '/views', $values['VIEWS_DIR'] );
		$this->assertSame( '/migrations', $values['MIGRATIONS_DIR'] );
	}

	public function test_get_env_uses_custom_env_file(): void {
		$this->mockPluginPaths();

		file_put_contents( $this->pluginDir . '/.env', "KEY=value\n" );

		$env = new KMEnv( '/plugins/my-plugin/my-plugin.php' );
		$env->setEnvFile( '.env' );

		$values = $env->getEnv();
		$this->assertSame( 'value', $values['KEY'] );
	}

	public function test_get_env_throws_when_file_missing(): void {
		$this->mockPluginPaths();

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Env file not found' );

		$env = new KMEnv( '/plugins/my-plugin/my-plugin.php' );
		$env->getEnv();
	}

	public function test_get_env_caches_values(): void {
		$this->mockPluginPaths();

		file_put_contents( $this->pluginDir . '/config.env', "KEY=value\n" );

		$env    = new KMEnv( '/plugins/my-plugin/my-plugin.php' );
		$first  = $env->getEnv();
		$second = $env->getEnv();

		$this->assertSame( $first, $second );
	}
}
