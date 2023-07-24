<?php

/**
 * @author kofimokome
 */

if ( ! class_exists( 'KMMigration' ) ) {

	class KMMigration {
		private static array $migrations = [];
		private static int $revisions = 0;
		private string $table_name;
		private array $columns;
		private bool $is_update;
		private int $revision_id = 0;

		public function __construct( string $table_name, bool $is_update = false ) {
			$env              = KMEnv::getEnv();
			$this->table_name = $env['TABLE_PREFIX'] . $table_name;
			$this->is_update  = $is_update;
			$this->columns    = [];
			if ( $is_update ) {
				$this->revision_id = self::$revisions + 1;
				self::$revisions ++;
			}
			array_push( self::$migrations, $this );

			return $this;
		}

		/**
		 * @param string $table
		 * @param string $field
		 * @param string $type
		 * @param string $default
		 *
		 * @return void
		 * @author kofimokome
		 *
		 * @since 1.0.0
		 */
		public static function addColumn( string $table, string $field, string $type, string $default = '' ) {
			global $wpdb;
			$query   = $wpdb->prepare( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '%s' AND column_name = '%s'", [
				$table,
				$field
			] );
			$results = $wpdb->get_results( $query );
			if ( empty( $results ) ) {
				$default_string = is_numeric( $default ) ? "DEFAULT $default" : "DEFAULT " . "'$default'";
				$query          = $wpdb->prepare( "ALTER TABLE  %s  ADD  %s  %s  NOT NULL %s", [
					$table,
					$field,
					$type,
					$default_string
				] );
				$wpdb->query( $query );
			}
		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Run update migrations
		 */
		public static function runUpdateMigrations() {
			$migrations = array_filter( self::$migrations, function ( $migration ) {
				return $migration->is_update;
			} );
			foreach ( $migrations as $migration ) {
				$migration->update();
			}
		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Updates a table
		 */
		public function update(): void {
			global $wpdb;

			$env               = KMEnv::getEnv();
			$last_revision_run = get_option( $env['TABLE_PREFIX'] . '_last_revision', 0 );
			if ( $last_revision_run < $this->revision_id ) {
				foreach ( $this->columns as $column ) {
					$query = $wpdb->prepare( "ALTER TABLE `%1s` %1s", [ $this->table_name, $column->toString() ] );
					$wpdb->query( $query );
				}
				update_option( $env['TABLE_PREFIX'] . '_last_revision', $this->revision_id );
			}
		}

		/**
		 * @param string $table_name Name of the table
		 *
		 * @since 1.0.0
		 * @author kofimokome
		 * Creates a table
		 */
		public static function runMigration( string $table_name ) {
			$env        = KMEnv::getEnv();
			$table_name = $env['TABLE_PREFIX'] . trim( $table_name );
			foreach ( self::$migrations as $migration ) {
				if ( $migration->getTableName() == $table_name ) {
					$migration->up();
				}
			}
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function getTableName(): string {
			return $this->table_name;
		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Creates a table
		 */
		public function up() {
			global $wpdb;

			if ( $this->is_update ) {
				$this->update();
			} else {
				$column_string = '';
				foreach ( $this->columns as $column ) {
					$column_string .= $column->toString() . ',';
				}
				$column_string = rtrim( $column_string, ", " );
				$query         = $wpdb->prepare( "CREATE TABLE IF NOT EXISTS `%1s` ( $column_string )", [
					$this->table_name,
				] );

				$wpdb->query( $query );
			}
		}

		/**
		 * @param string $table_name Name of the table without the prefix
		 *
		 * @since 1.0.0
		 * @author kofimokome
		 * Delete a particular table
		 */
		public static function drop( string $table_name ) {
			$env        = KMEnv::getEnv();
			$table_name = $env['TABLE_PREFIX'] . trim( $table_name );
			foreach ( self::$migrations as $migration ) {
				if ( $migration->getTableName() == $table_name ) {
					$migration->down();
				}
			}
		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Deletes a table
		 */
		public function down(): void {
			global $wpdb;
			if ( ! $this->is_update ) {
				$query = $wpdb->prepare( "DROP TABLE IF EXISTS %1s", [ $this->table_name ] );
				$wpdb->query( $query );
			}
		}

		/*public function longText( string $name ) {
			$column = new Column( $name, [ 'TEXT' ] );
			array_push( $this->columns, $column );

			return $column;
		}*/

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Deletes and recreate database tables
		 */
		public static function refresh(): void {
			self::dropAll();
			self::runMigrations();
		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Deletes all tables
		 */
		public static function dropAll(): void {
			$env = KMEnv::getEnv();
			foreach ( self::$migrations as $migration ) {
				$migration->down();
			}
			update_option( $env['TABLE_PREFIX'] . '_last_revision', 0 );

		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Run all migrations
		 */
		public static function runMigrations(): void {
			foreach ( self::$migrations as $migration ) {
				$migration->up();
			}
		}

		/*public function change( $name, $new_name ): Column {
			$column = new Column( $name, [], [ 'new_name' => $new_name, 'is_change' => true ] );
			array_push( $this->columns, $column );

			return $column;
		}*/

		/**
		 * Returns a migration instance
		 * @since 1.0.0
		 * @author kofimokome
		 */
		public static function getMigration( string $table_name, bool $is_full_table_name = false ) {
			$env        = KMEnv::getEnv();
			$table_name = $is_full_table_name ? $table_name : $env['TABLE_PREFIX'] . trim( $table_name );
			foreach ( self::$migrations as $migration ) {
				if ( $migration->getTableName() == $table_name ) {
					return $migration;
				}
			}

			return false;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function string( string $name, int $size = 255 ): KMColumn {
			$column = new KMColumn( $name, [ 'VARCHAR(' . $size . ')' ], [ 'is_update' => $this->is_update ] );
			array_push( $this->columns, $column );

			return $column;

		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function text( string $name ): KMColumn {
			$column = new KMColumn( $name, [ 'TEXT' ], [ 'is_update' => $this->is_update ] );
			array_push( $this->columns, $column );

			return $column;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function integer( string $name ): KMColumn {
			$column = new KMColumn( $name, [ 'INTEGER', 'SIGNED' ], [ 'is_update' => $this->is_update ] );
			array_push( $this->columns, $column );

			return $column;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function bigInt( string $name ): KMColumn {
			$column = new KMColumn( $name, [ 'BIGINT', 'SIGNED' ], [ 'is_update' => $this->is_update ] );
			array_push( $this->columns, $column );

			return $column;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function id(): KMColumn {
			$column = new KMColumn( 'id', [
				'BIGINT',
				'UNSIGNED',
				'AUTO_INCREMENT',
				'PRIMARY KEY'
			], [ 'is_update' => $this->is_update ] );
			array_push( $this->columns, $column );

			return $column;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function timestamps() {
			$this->dateTime( 'created_at' )->nullable();
			$this->dateTime( 'updated_at' )->nullable();
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function dateTime( string $name ): KMColumn {
			$column = new KMColumn( $name, [ 'DATETIME' ], [ 'is_update' => $this->is_update ] );
			array_push( $this->columns, $column );

			return $column;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function softDelete() {
			$this->boolean( 'deleted' )->nullable()->default( 0 );
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function boolean( string $name ): KMColumn {
			$column = new KMColumn( $name, [ 'BOOL' ], [ 'is_update' => $this->is_update ] );
			array_push( $this->columns, $column );

			return $column;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function date( string $name ): KMColumn {
			$column = new KMColumn( $name, [ 'DATE' ], [ 'is_update' => $this->is_update ] );
			array_push( $this->columns, $column );

			return $column;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function dropColumn( $name ): void {
			$column = new KMColumn( $name, [], [ 'is_delete' => true ] );
			array_push( $this->columns, $column );
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function rename( $name, $new_name ): void {
			$column = new KMColumn( $name, [], [ 'new_name' => $new_name, 'is_rename' => true ] );
			array_push( $this->columns, $column );
		}

		/**
		 * checks if a migration has a column
		 *
		 * @param string $field
		 *
		 * @return boolean
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function hasColumn( string $field ): bool {
			foreach ( $this->columns as $column ) {
				if ( $column->getName() == $field ) {
					return true;
				}
			}

			return false;
		}
	}
}
