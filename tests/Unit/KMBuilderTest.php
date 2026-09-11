<?php

namespace KofiMokome\WordPressTools\Tests\Unit;

use Brain\Monkey\Functions;
use KMBuilder;
use KMModel;
use PHPUnit\Framework\TestCase;

class KMBuilderTest extends TestCase {

	private string $pluginDir;

	protected function setUp(): void {
		parent::setUp();
		$this->pluginDir = WP_PLUGIN_DIR . '/my-plugin';
		if ( ! is_dir( $this->pluginDir ) ) {
			mkdir( $this->pluginDir, 0777, true );
		}

		$GLOBALS['wpdb'] = (object) [
			'prefix' => 'wp_',
		];

		Functions\expect( 'plugin_dir_path' )
			->andReturn( '/plugins/my-plugin/' );

		Functions\expect( 'plugin_basename' )
			->andReturn( 'my-plugin/my-plugin.php' );

		file_put_contents( $this->pluginDir . '/config.env', "TABLE_PREFIX=mp_\n" );
	}

	protected function tearDown(): void {
		parent::tearDown();
		\Brain\Monkey\tearDown();

		$files = glob( $this->pluginDir . '/*' );
		if ( $files ) {
			foreach ( $files as $file ) {
				unlink( $file );
			}
		}
	}

	private function createBuilder( string $table = 'users' ): KMBuilder {
		$model   = new KMModel( '/plugins/my-plugin/my-plugin.php' );
		$model->setTableName( 'wp_mp_' . $table );

		return new KMBuilder( 'wp_mp_' . $table, $model, '/plugins/my-plugin/my-plugin.php' );
	}

	public function test_select_sets_select_fields(): void {
		$builder = $this->createBuilder();
		$builder->select( [ 'id', 'name' ] );

		$reflection = new \ReflectionClass( $builder );
		$property   = $reflection->getProperty( 'selects' );

		$this->assertSame( [ 'id', 'name' ], $property->getValue( $builder ) );
	}

	public function test_where_builds_condition(): void {
		Functions\expect( 'esc_sql' )
			->andReturnUsing( fn( $value ) => $value );

		$builder = $this->createBuilder();
		$builder->where( 'id', '=', 1 );

		$reflection = new \ReflectionClass( $builder );
		$property   = $reflection->getProperty( 'where' );

		$this->assertSame( ' WHERE wp_mp_users.id = 1', $property->getValue( $builder ) );
	}

	public function test_order_by_builds_ordering(): void {
		$builder = $this->createBuilder();
		$builder->orderBy( 'id', 'desc' );

		$reflection = new \ReflectionClass( $builder );
		$property   = $reflection->getProperty( 'orderBys' );

		$this->assertSame( [ [ 'id', 'desc' ] ], $property->getValue( $builder ) );
	}

	public function test_group_by_builds_grouping(): void {
		$builder = $this->createBuilder();
		$builder->groupBy( 'role' );

		$reflection = new \ReflectionClass( $builder );
		$property   = $reflection->getProperty( 'groupBys' );

		$this->assertSame( [ [ 'role' ] ], $property->getValue( $builder ) );
	}

	public function test_paginate_sets_pagination_values(): void {
		$builder = $this->createBuilder();
		$builder->paginate( 10, 2 );

		$reflection  = new \ReflectionClass( $builder );
		$perPage     = $reflection->getProperty( 'per_page' );
		$currentPage = $reflection->getProperty( 'current_page' );
		$this->assertSame( 10, $perPage->getValue( $builder ) );
		$this->assertSame( 2, $currentPage->getValue( $builder ) );
	}

	public function test_inner_join_builds_join(): void {
		$builder = $this->createBuilder();
		$builder->innerJoin( 'roles' );

		$reflection = new \ReflectionClass( $builder );
		$property   = $reflection->getProperty( 'join' );

		$this->assertStringContainsString( 'INNER JOIN', $property->getValue( $builder ) );
	}

	public function test_table_static_method_returns_model(): void {
		Functions\expect( 'esc_sql' )
			->andReturnUsing( fn( $value ) => $value );

		$model = KMBuilder::table( 'users', true, '/plugins/my-plugin/my-plugin.php' );

		$this->assertInstanceOf( KMModel::class, $model );
		$this->assertSame( 'wp_mp_users', $model->getTableName() );
	}
}
