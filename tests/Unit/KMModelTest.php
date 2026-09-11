<?php

namespace KofiMokome\WordPressTools\Tests\Unit {

	use Brain\Monkey\Functions;
	use KMModel;
	use PHPUnit\Framework\TestCase;

	class KMModelTest extends TestCase {

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

			$files = glob( $this->pluginDir . '/*' );
			if ( $files ) {
				foreach ( $files as $file ) {
					unlink( $file );
				}
			}
		}

		private function mockPluginPaths(): void {
			Functions\expect( 'plugin_dir_path' )
				->andReturn( '/plugins/my-plugin/' );

			Functions\expect( 'plugin_basename' )
				->andReturn( 'my-plugin/my-plugin.php' );
		}

		public function test_has_timestamps(): void {
			$model = new TestModelWithTimestamps( '/plugins/my-plugin/my-plugin.php' );

			$this->assertTrue( $model->hasTimeStamps() );
		}

		public function test_has_no_timestamps_by_default(): void {
			$model = new TestModel( '/plugins/my-plugin/my-plugin.php' );

			$this->assertFalse( $model->hasTimeStamps() );
		}

		public function test_is_soft_delete(): void {
			$model = new TestSoftDeleteModel( '/plugins/my-plugin/my-plugin.php' );

			$this->assertTrue( $model->isSoftDelete() );
		}

		public function test_is_not_soft_delete_by_default(): void {
			$model = new TestModel( '/plugins/my-plugin/my-plugin.php' );

			$this->assertFalse( $model->isSoftDelete() );
		}

		public function test_get_table_name_uses_custom_table_name(): void {
			$this->mockPluginPaths();
			file_put_contents( $this->pluginDir . '/config.env', "TABLE_PREFIX=mp_\n" );

			$model = new TestModel( '/plugins/my-plugin/my-plugin.php' );
			$model->setTableName( 'wp_mp_custom_table' );

			$this->assertSame( 'wp_mp_custom_table', $model->getTableName() );
		}

		public function test_get_table_name_generates_from_class(): void {
			$this->mockPluginPaths();
			file_put_contents( $this->pluginDir . '/config.env', "TABLE_PREFIX=mp_\n" );

			$GLOBALS['wpdb'] = (object) [
				'prefix' => 'wp_',
			];

			$model      = new \GlobalTestModel( '/plugins/my-plugin/my-plugin.php' );
			$table_name = $model->getTableName();

			$this->assertSame( 'wp_mp_global_test_models', $table_name );
		}

		public function test_set_table_name(): void {
			$model = new TestModel( '/plugins/my-plugin/my-plugin.php' );
			$model->setTableName( 'custom' );

			$reflection = new \ReflectionClass( $model );
		$property   = $reflection->getProperty( 'table_name' );

		$this->assertSame( 'custom', $property->getValue( $model ) );
		}

		public function test_magic_call_runs_builder_method(): void {
			$this->mockPluginPaths();
			file_put_contents( $this->pluginDir . '/config.env', "TABLE_PREFIX=mp_\n" );

			Functions\expect( 'esc_sql' )
				->andReturnUsing( fn( $value ) => $value );

			$GLOBALS['wpdb'] = (object) [
				'prefix' => 'wp_',
			];

			$model = new TestModel( '/plugins/my-plugin/my-plugin.php' );

			$this->assertInstanceOf( \KMBuilder::class, $model->where( 'id', '=', 1 ) );
		}
	}

	class TestModel extends KMModel {
	}

	class TestModelWithTimestamps extends KMModel {
		protected $timestamps = true;
	}

	class TestSoftDeleteModel extends KMModel {
		protected $soft_delete = true;
	}
}

namespace {
	class GlobalTestModel extends \KMModel {
	}
}
