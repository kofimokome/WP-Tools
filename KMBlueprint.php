<?php

/**
 * @author kofimokome
 */

if ( ! class_exists( 'KMBlueprint' ) ) {
	#[AllowDynamicProperties]
	class KMBlueprint {
		private $columns;
		private $is_update;
		private $drop_table;


		public function __construct( bool $is_update = false ) {
			$this->is_update  = $is_update;
			$this->columns    = [];
			$this->drop_table = false;

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

		/*public function longText( string $name ) {
			$column = new Column( $name, [ 'TEXT' ] );
			array_push( $this->columns, $column );

			return $column;
		}*/


		/*public function change( $name, $new_name ): Column {
			$column = new Column( $name, [], [ 'new_name' => $new_name, 'is_change' => true ] );
			array_push( $this->columns, $column );

			return $column;
		}*/

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

		public function drop() {
			$this->drop_table = true;
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

		/**
		 * @author kofimokome
		 * Get the columns in this blueprint
		 */
		public function getColumns(): array {
			return $this->columns;
		}

		/**
		 * @author kofimokome
		 * checks if the query is a drop table uery
		 */
		public function isDropTable(): bool {
			return $this->drop_table;
		}

		public function toString(): string {

			$column_string = '';
			foreach ( $this->columns as $column ) {
				$column_string .= $column->toString() . ',';
			}
			$column_string = rtrim( $column_string, ", " );

			return $column_string;
		}
	}
}
