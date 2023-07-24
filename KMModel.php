<?php

/**
 * @author kofimokome
 */

if ( ! class_exists( 'KMModel' ) ) {

	class KMModel {
		protected static bool $timestamps = false;
		protected static string $table_name = '';
		protected static bool $soft_delete = false;
		protected static string $where = '';
		protected static array $orderBys = [];
		protected static string $pagination = '';
		protected static string $join = '';
		protected static string $join_table = '';
		protected static int $per_page = 0;
		protected static int $current_page = 1;
		public int $id = 0;

		public function __construct() {
			// do something here
		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Finds a model in the database
		 * Returns boolean|object
		 */
		public static function find( int $id ): KMModel|null {
			return self::where( "id", "=", $id )->first();
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function first(): ?KMModel {
			$data = self::get();
			if ( sizeof( $data ) > 0 ) {
				return $data[0];
			}

			return null;
		}

		/**
		 * @param array $fields the fields to get. if empty, query will get everything
		 *
		 * @author kofimokome
		 * @since 1.0.0
		 * example
		 * [Job::tableName().'.*',Currency::tableName().'.code',JobType::tableName().'.name AS job_type_name '],
		 */
		public static function get( array $fields = [] ) {
			global $wpdb;
			$table_name = self::getTableName();

			$db_name = $table_name;
			$select  = "SELECT * "; // set select all as the default
			if ( sizeof( $fields ) > 0 ) { // we want to get specific fields, not everything, eg only id, name
				$select = 'SELECT ';
				foreach ( $fields as $field ) {
					$select .= $field . ', ';
				}
			}
			$select    = rtrim( $select, ', ' ); // removes the last comma (,) from the select statement
			$data      = [];
			$query     = $select . " FROM " . $db_name; // build the first section of the query eg Select * from table_name or select id,name from table_name
			$additions = self::$join; // if we have joins, we first add it to the next part of the query eg select * from table_name INNER JOIN ......

			$is_deleted_in_where = strpos( self::$where, 'deleted' );

			if ( static::$soft_delete && $is_deleted_in_where === false ) {
				if ( trim( self::$where ) == '' ) {
					self::where( 'deleted', '=', 0 );
				} else {
					self::andWhere( 'deleted', '=', 0 ); // if the table has the deleted column, we should get the fields that have not been soft deleted by default
				}
			}
			$additions .= self::$where;

			if ( sizeof( self::$orderBys ) > 0 ) {
				foreach ( self::$orderBys as $order_by ) { // ordering will be the last section of the query
					$additions .= " ORDER BY " . $db_name . '.' . $order_by[0] . " " . $order_by[1];
				}
			}

			if ( self::$per_page > 0 || self::$per_page == - 1 ) { // check if the query requires pagination
				$total_query = "SELECT COUNT(*) as total FROM `{$db_name}` {$additions}";
				$total       = intval( $wpdb->get_var( $total_query ) );
				$query       .= $additions;

				// prevent calculating offset for negative one
				// negative one was used to show all results in get all requests
				// we could have not used negative one since the else will return everything, but the structure of $data will not be the same

				self::$per_page = self::$per_page == - 1 ? $total : self::$per_page;
				$offset         = ( self::$current_page * self::$per_page ) - self::$per_page;
				$query          .= " LIMIT " . $offset . ' , ' . self::$per_page;

				$data        = self::getResults( $query );
				$total_pages = self::$per_page == 0 ? 0 : ( $total / self::$per_page );
				$total_pages = $total_pages > round( $total_pages ) ? round( $total_pages ) + 1 : round( $total_pages );
				$data        = [
					'data'       => $data,
					'page'       => self::$current_page,
					'totalPages' => $total_pages,
					'perPage'    => self::$per_page,
					'totalItems' => $total
				];

			} else { // query does not require pagination
				$query .= $additions;
				$data  = self::getResults( $query );
			}
//		var_dump( $query );
			// reset query variables;
			self::$where        = '';
			self::$orderBys     = [];
			self::$pagination   = '';
			self::$join         = '';
			self::$join_table   = '';
			self::$per_page     = 0;
			self::$current_page = 1;

			return $data;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function where( string $field, string $comparison, $value, $add_table_name = true ): KMModel {
			$table_name = $add_table_name ? self::getTableName() . '.' : '';
			if ( strlen( self::$where ) == 0 ) {

				if ( ! is_numeric( $value ) ) {
					$value = "'" . $value . "'";
				}
				self::$where = " WHERE " . $table_name . $field . " " . $comparison . " " . $value;

				return new static();
			} else {
				return self::andWhere( $field, $comparison, $value );
			}
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function andWhere( string $field, string $comparison, $value ): KMModel {
			$table_name = self::getTableName();
			if ( ! is_numeric( $value ) ) {
				$value = "'" . $value . "'";
			}
			self::$where .= " AND " . $table_name . '.' . $field . " " . $comparison . " " . $value;

			return new static();
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		private static function getResults( $query ) {
			global $wpdb;
			$model   = get_called_class();
			$results = $wpdb->get_results( $query );
			$data    = [];
			if ( $results ) {
				if ( trim( self::$join ) == '' ) {
					foreach ( $results as $result ) {
						$object = new $model();
						foreach ( $result as $key => $value ) {
							$object->$key = $value;
						}
						array_push( $data, $object );
					}
				} else {  // if we have joins, we do not need to return the model since the structure will be different
					$data = $results;
				}
			}

			return $data;
		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Finds a model by name in the database
		 * Returns boolean|object
		 */
		public static function name( string $name ) {
			return self::where( 'name', 'like', "'" . $name . "'" )->get();
		}

		/**
		 * @return array<KMModel>
		 * @since 1.0.0
		 * @author kofimokome
		 * Gets all data in the database
		 */
		public static function all(): array {
			return self::orderBy( 'id', 'desc' )->get();
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function orderBy( string $field, string $order ): KMModel {
			array_push( self::$orderBys, [ $field, $order ] );

			return new static();
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function orWhere( string $field, string $comparison, $value ): KMModel {
			$table_name = self::getTableName();
			if ( ! is_numeric( $value ) ) {
				$value = "'" . $value . "'";
			}
			self::$where .= " OR " . $table_name . '.' . $field . " " . $comparison . " " . $value;

			return new static();
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function whereJoin( string $field, string $comparison, $value, $table ): KMModel {
			if ( ! is_numeric( $value ) ) {
				$value = "'" . $value . "'";
			}
			self::$where = " WHERE " . $table . '.' . $field . " " . $comparison . " " . $value;

			return new static();
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function andWhereJoin( string $field, string $comparison, $value, $table ): KMModel {
			if ( ! is_numeric( $value ) ) {
				$value = "'" . $value . "'";
			}
			self::$where .= " AND " . $table . '.' . $field . " " . $comparison . " " . $value;

			return new static();
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function orWhereJoin( string $field, string $comparison, $value, $table ): KMModel {
			if ( ! is_numeric( $value ) ) {
				$value = "'" . $value . "'";
			}
			self::$where .= " OR " . $table . '.' . $field . " " . $comparison . " " . $value;

			return new static();
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function paginate( int $per_page = 1, int $current_page = 1 ): KMModel {
			self::$per_page     = $per_page;
			self::$current_page = $current_page;

			return new static();
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function innerJoin( string $table_name ): KMModel {
			$env              = KMEnv::getEnv();
			self::$join       .= ' INNER JOIN ' . $env['TABLE_PREFIX'] . $table_name . ' ';
			self::$join_table = $env['TABLE_PREFIX'] . $table_name;

			return new static();
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function leftJoin( string $table_name ): KMModel {
			global $wpdb;
			$env    = KMEnv::getEnv();
			$table  = $env['TABLE_PREFIX'] . $table_name;
			$prefix = $wpdb->prefix;
			if ( strpos( $table_name, $prefix ) !== false ) {
				$table = $table_name;
			}
			self::$join       .= ' LEFT JOIN ' . $table . ' ';
			self::$join_table = $table;

			return new static();
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function rightJoin( string $table_name ): KMModel {
			$env              = KMEnv::getEnv();
			self::$join       .= ' RIGHT JOIN ' . $env['TABLE_PREFIX'] . $table_name . ' ';
			self::$join_table = $env['TABLE_PREFIX'] . $table_name;

			return new static();
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function on( string $field1, string $field2 ): KMModel {
			$table_name = self::getTableName();
			self::$join .= ( 'ON ' . $table_name . '.' . $field1 . ' = ' . self::$join_table . '.' . $field2 );

			return new static();
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function take( int $number ): array {
			$data = self::get();

			return array_slice( $data, 0, $number );
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function lastItem(): ?KMModel {
			return self::orderBy( 'id', 'desc' )->first();
		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Saves a new model in the database
		 */
		public function save(): bool {
			$model  = get_called_class();
			$fields = get_object_vars( $this );
			global $wpdb;
			$wpdb->show_errors = true;
			$table_name        = self::getTableName();
			$this_migration    = Migration::getMigration( $table_name, true );
			if ( $this->id == 0 ) { // we are creating
				if ( static::$timestamps ) {
					$fields['created_at'] = date( "Y-m-d H:i" );
					$fields['updated_at'] = date( "Y-m-d H:i" );
				}
				$result = $wpdb->insert( $table_name, $fields );
			} else { // we are updating
				if ( static::$timestamps ) {
					$fields['updated_at'] = date( "Y-m-d H:i" );
				}
				unset( $fields['id'] );
				$result = $wpdb->update( $table_name, $fields, [ 'id' => $this->id ] );
			}
			if ( $result !== false ) {
				if ( $this_migration->hasColumn( 'id' ) ) {
					$this->id = $this->id == 0 ? $wpdb->insert_id : $this->id;
				}

				return true;
			} else {
				return false;
			}
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		private static function getTableName(): string {
			$env        = KMEnv::getEnv();
			$table_name = static::$table_name;
			if ( $table_name == '' ) {
				$model      = get_called_class();
				$table_name = strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $model ) );
				$table_name = strtolower( str_replace( 'omhc\\', '', $table_name ) );
				$table_name = ltrim( $table_name, '_' );
				$table_name = Plural( $table_name );

			}

			$table_name = $env['TABLE_PREFIX'] . $table_name;

			return $table_name;

		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Hard delete a model from the database
		 * Also does soft delete if table has deleted column
		 */
		public function delete(): bool {
			global $wpdb;

			$table_name = self::getTableName();
			if ( static::$soft_delete ) {
				return self::softDelete();
			} else {
				// delete the package here
				return $wpdb->delete( $table_name, array( 'id' => $this->id ) );
			}
		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Soft deletes a model from the database
		 */
		public function softDelete(): bool {
			global $wpdb;
			$table_name = self::getTableName();

			return $wpdb->update( $table_name, [ 'deleted' => 1 ], [ 'id' => $this->id ] );
		}
	}
}
