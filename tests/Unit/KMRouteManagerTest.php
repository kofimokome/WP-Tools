<?php

namespace KofiMokome\WordPressTools\Tests\Unit;

use Brain\Monkey\Functions;
use KMRouteManager;
use PHPUnit\Framework\TestCase;

class KMRouteManagerTest extends TestCase {

	protected function tearDown(): void {
		parent::tearDown();
		\Brain\Monkey\tearDown();
	}

	public function test_routes_array_is_initially_empty(): void {
		$manager = new KMRouteManager( '/plugins/my-plugin/my-plugin.php' );

		$this->assertEmpty( $manager->routes );
		$this->assertEmpty( $manager->middlewares );
	}

	public function test_register_middleware_stores_callback(): void {
		$manager = new KMRouteManager( '/plugins/my-plugin/my-plugin.php' );
		$callback = function ( $view ) {
			return $view;
		};

		$manager->registerMiddleware( 'auth', $callback );

		$this->assertArrayHasKey( 'auth', $manager->middlewares );
		$this->assertSame( $callback, $manager->middlewares['auth'] );
	}

	public function test_middleware_sets_current_middleware(): void {
		$manager = new KMRouteManager( '/plugins/my-plugin/my-plugin.php' );

		$manager->middleware( 'auth', function () use ( $manager ) {
			$this->assertSame( 'auth', $manager->currentMiddleware );
		} );

		$this->assertSame( '', $manager->currentMiddleware );
	}

	public function test_get_route_returns_false_when_not_found(): void {
		$manager = new KMRouteManager( '/plugins/my-plugin/my-plugin.php' );

		$this->assertFalse( $manager->getRoute( 'missing' ) );
	}

	public function test_get_route_returns_registered_route(): void {
		$manager = new KMRouteManager( '/plugins/my-plugin/my-plugin.php' );
		$manager->names['home'] = 'index.php?home=1';

		$this->assertSame( 'index.php?home=1', $manager->getRoute( 'home' ) );
	}

	public function test_view_path_returns_expected_path(): void {
		Functions\expect( 'plugin_dir_path' )
			->andReturn( '/plugins/my-plugin/' );

		Functions\expect( 'plugin_basename' )
			->andReturn( 'my-plugin/my-plugin.php' );

		$pluginDir = WP_PLUGIN_DIR . '/my-plugin';
		if ( ! is_dir( $pluginDir ) ) {
			mkdir( $pluginDir, 0777, true );
		}
		file_put_contents( $pluginDir . '/config.env', "VIEWS_DIR=/views\n" );

		$manager = new KMRouteManager( '/plugins/my-plugin/my-plugin.php' );
		$path    = $manager->viewPath( 'dashboard' );

		$this->assertSame( $pluginDir . '/views/dashboard.php', $path );

		unlink( $pluginDir . '/config.env' );
	}
}
