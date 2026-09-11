<?php

namespace KofiMokome\WordPressTools\Tests\Unit;

use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WPTools;

class WPToolsTest extends TestCase {

	protected function tearDown(): void {
		parent::tearDown();
		\Brain\Monkey\tearDown();
	}

	private function mockPluginPaths(): void {
		Functions\expect( 'plugin_basename' )
			->andReturn( 'my-plugin/my-plugin.php' );

		Functions\expect( 'plugin_dir_path' )
			->andReturn( '/plugins/my-plugin/' );

		Functions\expect( 'plugin_dir_url' )
			->andReturn( 'https://example.com/wp-content/plugins/my-plugin/' );
	}

	public function test_constructor_initializes_managers(): void {
		$this->mockPluginPaths();

		$pluginDir = WP_PLUGIN_DIR . '/my-plugin';
		if ( ! is_dir( $pluginDir ) ) {
			mkdir( $pluginDir, 0777, true );
		}
		file_put_contents( $pluginDir . '/config.env', "TABLE_PREFIX=mp_\nVIEWS_DIR=/views\nMIGRATIONS_DIR=/migrations\n" );

		$tools = new WPTools( '/plugins/my-plugin/my-plugin.php' );

		$this->assertInstanceOf( \KMRouteManager::class, $tools->route_manager );
		$this->assertInstanceOf( \KMMigrationManager::class, $tools->migration_manager );
		$this->assertIsArray( $tools->env );

		unlink( $pluginDir . '/config.env' );
	}

	public function test_get_instance_throws_when_not_created(): void {
		Functions\expect( 'plugin_basename' )
			->andReturn( 'unknown-plugin/unknown-plugin.php' );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'WPTools instance not found' );

		WPTools::getInstance( '/plugins/unknown-plugin/unknown-plugin.php' );
	}

	public function test_routes_returns_route_instance(): void {
		$this->mockPluginPaths();

		$pluginDir = WP_PLUGIN_DIR . '/my-plugin';
		if ( ! is_dir( $pluginDir ) ) {
			mkdir( $pluginDir, 0777, true );
		}
		file_put_contents( $pluginDir . '/config.env', "TABLE_PREFIX=mp_\nVIEWS_DIR=/views\nMIGRATIONS_DIR=/migrations\n" );

		$tools = new WPTools( '/plugins/my-plugin/my-plugin.php' );

		$this->assertInstanceOf( \KMRoute::class, $tools->routes() );

		unlink( $pluginDir . '/config.env' );
	}

	public function test_get_plugin_url_returns_trimmed_url(): void {
		$this->mockPluginPaths();

		$pluginDir = WP_PLUGIN_DIR . '/my-plugin';
		if ( ! is_dir( $pluginDir ) ) {
			mkdir( $pluginDir, 0777, true );
		}
		file_put_contents( $pluginDir . '/config.env', "TABLE_PREFIX=mp_\nVIEWS_DIR=/views\nMIGRATIONS_DIR=/migrations\n" );

		$tools = new WPTools( '/plugins/my-plugin/my-plugin.php' );

		$this->assertSame( 'https://example.com/wp-content/plugins/my-plugin', $tools->getPluginURL() );

		unlink( $pluginDir . '/config.env' );
	}
}
