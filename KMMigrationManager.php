<?php

/**
 * @author kofimokome
 */


if ( ! class_exists( 'KMMigrationManager' ) ) {

	class KMMigrationManager {
		public $migrations = [];
		private $name = '';
		private $path = '';
		private $context = '';
		private $plugin_dir = '';
		private $migrations_loaded = false;

		public function getMigrationsDirectory(): string {
			$env            = ( new KMEnv( $this->context ) )->getEnv();
			$migrations_dir = $env['MIGRATIONS_DIR'];
			// remove trailing / from $views_dir if any
			$migrations_dir = rtrim( $migrations_dir, '/' );

			return $this->plugin_dir . $migrations_dir;

		}

		/**
		 * @author kofimokome
		 * Loads the migrations found in the migrations directory
		 */
		public function loadMigrations() {
			$migrations_dir          = $this->getMigrationsDirectory();
			$files                   = scandir( $migrations_dir );
			$last_migration_imported = [];
			foreach ( $files as $file ) {
				if ( is_file( $migrations_dir . '/' . $file ) && strpos( $file, '.php' ) >= 0 ) {
					$contents  = file_get_contents( $migrations_dir . '/' . $file );
					$namespace = '';
					// check if the contents has a namespace
					preg_match( '/namespace\s+\w+;/', $contents, $matches );
					if ( sizeof( $matches ) > 0 ) {
						$match     = $matches[0];
						$match     = str_replace( 'namespace', '', $match );
						$match     = str_replace( ';', '', $match );
						$namespace = trim( $match );
					}

					preg_match( '/class\s+\w+\s+extends\s+KMMigration/', $contents, $matches );
					if ( sizeof( $matches ) > 0 ) {
						$match = $matches[0];
						$match = trim( preg_replace( '/\s+/', ' ', $match ) );
						$class = explode( ' ', $match )[1];

						require_once $migrations_dir . '/' . $file;

						$last_migration_imported['name']  = rtrim( $file, '.php' );
						$last_migration_imported['path']  = $migrations_dir . '/' . $file;
						$last_migration_imported['class'] = $namespace . '\\' . $class;
						$this->migrations[]               = $last_migration_imported;
					}
				}
			}
			$this->migrations_loaded = true;
		}

		public function __construct( $plugin_dir, string $context ) {
			$this->plugin_dir = $plugin_dir;
			$this->context    = $context;
		}

		/**
		 * @throws Exception
		 * @author kofimokome
		 * Updates a table
		 * @since 1.0.0
		 */
		public function update( array $migration, KMMigration $object ): void {
			global $wpdb;
			$blueprint = new KMBlueprint( true );
			$object->up( $blueprint );
			$columns = $blueprint->getColumns();
			foreach ( $columns as $column ) {
				$query = $wpdb->prepare( "ALTER TABLE `%1s` %1s", [ $object->getTableName(), $column->toString() ] );
				if ( ! $wpdb->query( $query ) ) {
					throw new Exception( $wpdb->last_error );
				}
			}

		}

		/**
		 * @param string $table_name Name of the table
		 *
		 * @since 1.0.0
		 * @author kofimokome
		 * Runs a particular migration
		 */
		public function runMigration( array $migration ) {
			global $wpdb;
			$migration_object = new $migration['class']( $this, $this->context );

			try {
				if ( $migration_object->isUpdate() ) {
					$this->update( $migration, $migration_object );
				} else {
					$blueprint = new KMBlueprint();
					$migration_object->up( $blueprint );
					$column_string = $blueprint->toString();

					$query = $wpdb->prepare( "CREATE TABLE IF NOT EXISTS `%1s` ( $column_string )", [
						$migration_object->getTableName(),
					] );

					if ( ! $wpdb->query( $query ) ) {
						throw new Exception( $wpdb->last_error );
					}
				}
				$migration_model        = KMBuilder::table( 'migrations', true, $this->context );
				$migration_model->name  = $migration['name'];
				$migration_model->batch = 0; // todo: add revision id
				$migration_model->save();

			} catch ( Exception $e ) {
			}
		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Deletes and recreate database tables
		 */
		public function refresh(): void {
			$this->dropAll();
			$this->runMigrations();
		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Deletes all tables
		 */
		public function dropAll(): void {
			foreach ( $this->migrations as $migration ) {
				$this->dropMigration( $migration );
			}
		}

		/**
		 * @param string $table_name Name of the table without the prefix
		 *
		 * @throws Exception
		 * @author kofimokome
		 * Delete a particular migration
		 * @since 1.0.0
		 */
		public function dropMigration( array $migration, bool $delete_file = true ) {
			global $wpdb;
			$migration_object = new $migration['class']( $this, $this->context );
			$blueprint        = new KMBlueprint();
			$migration_object->down( $blueprint );

			if ( $blueprint->isDropTable() ) {
				$query = $wpdb->prepare( "DROP TABLE IF EXISTS %1s", [ $migration_object->getTableName() ] );
				if ( ! $wpdb->query( $query ) ) {
					throw new Exception( $wpdb->last_error );
				}
			} else {
				$columns = $blueprint->getColumns();
				foreach ( $columns as $column ) {
					$query = $wpdb->prepare( "ALTER TABLE `%1s` %1s", [
						$migration_object->getTableName(),
						$column->toString()
					] );
					if ( ! $wpdb->query( $query ) ) {
						throw new Exception( $wpdb->last_error );
					}
				}
			}
			$db_migration = KMModel::table( 'migrations', true, $this->context )->where( 'name', ' = ', $migration['name'] )->first();
			if ( $db_migration ) {
				$db_migration->delete();
			}

			if ( $delete_file ) {
				unlink( $migration['path'] );
			}
		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Run all migrations
		 */
		public function runMigrations(): void {
			try {
				// 1. Check if the migrations table exists
				$this->createMigrationsTable();

				// 2. check if migrations have been imported
				if ( ! $this->migrations_loaded ) {
					$this->loadMigrations();
				}
				$migrations = $this->migrations;

				// 3.  get the migrations that have already been run
				$migrations_run = KMBuilder::table( 'migrations', true, $this->context )->get();

				// 4 exclude the migrations that have been run from the array
				foreach ( $migrations_run as $migration_run ) {
					foreach ( $migrations as $key => $migration ) {
						if ( $migration['name'] == $migration_run->name ) {
							unset( $migrations[ $key ] );
						}
					}
				}
				$migrations = array_values( $migrations );
				// 5. run the migrations
				foreach ( $migrations as $migration ) {
					$this->runMigration( $migration );
				}

			} catch ( Exception $e ) {
				throw $e;
				// just ignore the error
			}


		}

		/**
		 * Returns a migration instance
		 * @since 1.0.0
		 * @author kofimokome
		 */
		public function getMigration( string $migration_name ) {

			foreach ( $this->migrations as $migration ) {
				if ( $migration['name'] == $migration_name ) {
					return $migration;
				}
			}

			return false;
		}

		/**
		 * Create the table used to track the migrations
		 * @author kofimokome
		 */
		private function createMigrationsTable() {

			global $wpdb;

			$env        = ( new KMEnv( $this->context ) )->getEnv();
			$table_name = $wpdb->prefix . trim( $env['TABLE_PREFIX'] ) . 'migrations';

			$blueprint = new KMBlueprint();
			$blueprint->id();
			$blueprint->string( 'name' );
			$blueprint->integer( 'batch' );
			$blueprint->timestamps();
			$additions = $blueprint->toString();
			$query     = $wpdb->prepare( "CREATE TABLE IF NOT EXISTS `%1s` ( $additions )", [
				$table_name,
			] );
			if ( ! $wpdb->query( $query ) ) {
				throw new Exception( $wpdb->last_error );
			}
		}
	}
}
