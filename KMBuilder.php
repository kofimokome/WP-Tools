<?php

/**
 * @author kofimokome
 */

if ( ! class_exists( 'KMBuilder' ) ) {

	#[AllowDynamicProperties]
	class KMBuilder {
		private $table_name;
		public $where = '';
		public $orderBys = [];
		public $groupBys = [];
		public $pagination = '';
		public $join = '';
		public $join_table = '';
		public $per_page = 0;
		public $current_page = 1;
		private $model;
		private $context;

		function __construct( string $table, KMModel $model, string $context) {
			$this->table_name = $table;
			$this->model      = $model;
			$this->context    = $context;
		}

		/**
		 * @author kofimokome
		 * Creates a new model instance
		 */
		public static function table( $table_name, bool $add_prefix = true, string $context = '' ): KMModel {
			global $wpdb;

			if ( $context == '' ) {
				$t = debug_backtrace();
//				var_dump( "called from {$t[0]['file']}" );

				$context = $t[0]['file'];
			}
			$env = ( new KMEnv( $context ) )->getEnv();
			if ( $add_prefix ) {
				$table_name = $wpdb->prefix . trim( $env['TABLE_PREFIX'] ) . $table_name;
			}
			$model = new KMModel( $context );
			$model->setTableName( $table_name );

//			$query = new KMBuilder( $table_name, $model );

			return $model;
		}

		/**
		 * @author kofimokome
		 * Truncates a table
		 */
		public function truncate() {
			global $wpdb;
			$table_name = $this->table_name;
			$wpdb->query( "TRUNCATE TABLE $table_name" );
		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Finds a model in the database
		 * Returns boolean|object
		 */
		public function find( int $id ): ?KMModel {
			return $this->where( "id", "=", $id )->first();
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function first(): ?KMModel {
			$data = $this->get();
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
		public function get( array $fields = [] ) {
			global $wpdb;
			$table_name = $this->table_name;

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
			$additions = $this->join; // if we have joins, we first add it to the next part of the query eg select * from table_name INNER JOIN ......

			$is_deleted_in_where = strpos( $this->where, 'deleted' );

			if ( $this->model->isSoftDelete() && $is_deleted_in_where === false ) {
				if ( trim( $this->where ) == '' ) {
					$this->where( 'deleted', '=', 0 );
				} else {
					$this->andWhere( 'deleted', '=', 0 ); // if the table has the deleted column, we should get the fields that have not been soft deleted by default
				}
			}
			$additions .= $this->where;

			if ( sizeof( $this->groupBys ) > 0 ) {
				$additions .= " GROUP BY ";
				foreach ( $this->groupBys as $group_by ) {
					$additions .= $group_by[0] . ", ";
				}
				$additions = rtrim( $additions, ', ' ); // removes the last comma (,) from the select statement
			}
			$total_query = "SELECT COUNT(*) as total FROM `{$db_name}` {$additions}"; // added here becuse we do not need the orderby section in the query

			if ( sizeof( $this->orderBys ) > 0 ) {
				foreach ( $this->orderBys as $order_by ) { // ordering will be the last section of the query
					$additions .= " ORDER BY " . $order_by[0] . " " . $order_by[1];
//					$additions .= " ORDER BY " . $db_name . '.' . $order_by[0] . " " . $order_by[1];
				}
			}

			if ( $this->per_page > 0 || $this->per_page == - 1 ) { // check if the query requires pagination
				$total = intval( $wpdb->get_var( $total_query ) );
				$query .= $additions;

				// prevent calculating offset for negative one
				// negative one was used to show all results in get all requests
				// we could have not used negative one since the else will return everything, but the structure of $data will not be the same

				$this->per_page = $this->per_page == - 1 ? $total : $this->per_page;
				$offset         = ( $this->current_page * $this->per_page ) - $this->per_page;
				$query          .= " LIMIT " . $offset . ' , ' . $this->per_page;

				$data        = $this->getResults( $query );
				$total_pages = $this->per_page == 0 ? 0 : ( $total / $this->per_page );
				$total_pages = $total_pages > round( $total_pages ) ? round( $total_pages ) + 1 : round( $total_pages );
				$data        = [
					'data'       => $data,
					'page'       => $this->current_page,
					'totalPages' => $total_pages,
					'perPage'    => $this->per_page,
					'totalItems' => $total
				];

			} else { // query does not require pagination
				$query .= $additions;
				$data  = $this->getResults( $query );
			}
//			echo( $query );
			// reset query variables;
			$this->where        = '';
			$this->orderBys     = [];
			$this->groupBys     = [];
			$this->pagination   = '';
			$this->join         = '';
			$this->join_table   = '';
			$this->per_page     = 0;
			$this->current_page = 1;

			return $data;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function where( string $field, string $comparison, $value, $add_table_name = true ): KMBuilder {
			$table_name = $add_table_name ? $this->table_name . '.' : '';
			if ( strlen( $this->where ) == 0 ) {

				if ( ! is_numeric( $value ) ) {
					$value = "'" . $value . "'";
				}
				$this->where = " WHERE " . $table_name . $field . " " . $comparison . " " . $value;

				return $this;
			} else {
				return $this->andWhere( $field, $comparison, $value );
			}
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function andWhere( string $field, string $comparison, $value ): KMBuilder {
			$table_name = $this->table_name;
			if ( ! is_numeric( $value ) ) {
				$value = "'" . $value . "'";
			}
			$this->where .= " AND " . $table_name . '.' . $field . " " . $comparison . " " . $value;

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function getResults( $query ) {
			global $wpdb;
			$results = $wpdb->get_results( $query );
			$data    = [];
			if ( $results ) {
				if ( trim( $this->join ) == '' ) {
					foreach ( $results as $result ) {
						$object = clone $this->model;
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
		public function name( string $name ) {
			return $this->where( 'name', 'like', "'" . $name . "'" )->get();
		}

		/**
		 * @return array<KMModel>
		 * @since 1.0.0
		 * @author kofimokome
		 * Gets all data in the database
		 */
		public function all(): array {
			return $this->orderBy( 'id', 'desc' )->get();
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function orderBy( string $field, string $order ): KMBuilder {
			array_push( $this->orderBys, [ $field, $order ] );

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function groupBy( string $field ): KMBuilder {
			array_push( $this->groupBys, [ $field ] );

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function orWhere( string $field, string $comparison, $value ): KMBuilder {
			$table_name = $this->table_name;
			if ( ! is_numeric( $value ) ) {
				$value = "'" . $value . "'";
			}
			$this->where .= " OR " . $table_name . '.' . $field . " " . $comparison . " " . $value;

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function whereJoin( string $field, string $comparison, $value, $table ): KMBuilder {
			if ( ! is_numeric( $value ) ) {
				$value = "'" . $value . "'";
			}
			$this->where = " WHERE " . $table . '.' . $field . " " . $comparison . " " . $value;

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function andWhereJoin( string $field, string $comparison, $value, $table ): KMBuilder {
			if ( ! is_numeric( $value ) ) {
				$value = "'" . $value . "'";
			}
			$this->where .= " AND " . $table . '.' . $field . " " . $comparison . " " . $value;

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function orWhereJoin( string $field, string $comparison, $value, $table ): KMBuilder {
			if ( ! is_numeric( $value ) ) {
				$value = "'" . $value . "'";
			}
			$this->where .= " OR " . $table . '.' . $field . " " . $comparison . " " . $value;

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function paginate( int $per_page = 1, int $current_page = 1 ): KMBuilder {
			$this->per_page     = $per_page;
			$this->current_page = $current_page;

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function innerJoin( string $table_name ): KMBuilder {
			global $wpdb;

			$env              = ( new KMEnv( $this->context ) )->getEnv();
			$this->join       .= ' INNER JOIN ' . $wpdb->prefix . trim( $env['TABLE_PREFIX'] ) . $table_name . ' ';
			$this->join_table = $wpdb->prefix . trim( $env['TABLE_PREFIX'] ) . $table_name;

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function leftJoin( string $table_name ): KMBuilder {
			global $wpdb;
			$env    = ( new KMEnv( $this->context ) )->getEnv();
			$table  = $wpdb->prefix . trim( $env['TABLE_PREFIX'] ) . $table_name;
			$prefix = $wpdb->prefix;
			if ( strpos( $table_name, $prefix ) !== false ) {
				$table = $table_name;
			}
			$this->join       .= ' LEFT JOIN ' . $table . ' ';
			$this->join_table = $table;

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function rightJoin( string $table_name ): KMBuilder {
			global $wpdb;

			$env              = ( new KMEnv( $this->context ) )->getEnv();
			$this->join       .= ' RIGHT JOIN ' . $wpdb->prefix . trim( $env['TABLE_PREFIX'] ) . $table_name . ' ';
			$this->join_table = $wpdb->prefix . trim( $env['TABLE_PREFIX'] ) . $table_name;

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function on( string $field1, string $field2 ): KMBuilder {
			$table_name = $this->table_name;
			$this->join .= ( 'ON ' . $table_name . '.' . $field1 . ' = ' . $this->join_table . '.' . $field2 );

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function take( int $number ): array {
			$this->paginate( $number );
			$data = $this->get();

			return array_slice( $data['data'], 0, $number );
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function lastItem(): ?KMModel {
			return $this->orderBy( 'id', 'desc' )->first();
		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Saves a new model in the database
		 */
		public function save(): bool {
			global $wpdb;
			$wpdb->show_errors = true;

			$fields     = $this->getFields();
			$table_name = $this->table_name;
			if ( $this->model->id == 0 ) { // we are creating
				if ( $this->model->hasTimeStamps() ) {
					$fields['created_at'] = date( "Y-m-d H:i" );
					$fields['updated_at'] = date( "Y-m-d H:i" );
				}
				$result = $wpdb->insert( $table_name, $fields );
			} else { // we are updating
				if ( $this->model->hasTimeStamps() ) {
					$fields['updated_at'] = date( "Y-m-d H:i" );
				}
				unset( $fields['id'] );
				$result = $wpdb->update( $table_name, $fields, [ 'id' => $this->model->id ] );
			}
			if ( $result !== false ) {
				$this->model->id = $this->model->id == 0 ? $wpdb->insert_id : $this->model->id;

				return true;
			} else {
				return false;
			}
		}

		/**
		 * @author kofimokome
		 * Gets all the properties in a model
		 */
		private function getFields(): array {
			$fields   = get_object_vars( $this->model );
			$excludes = [
				'table_name',
				'orderBys',
				'pagination',
				'model',
				'timestamps',
				'soft_delete',
				'per_page',
				'current_page',
				'join',
				'join_table',
				'where'
			];
			foreach ( $excludes as $exclude ) {
				unset( $fields[ $exclude ] );
			}

			return $fields;

		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Hard delete a model from the database
		 * Also does soft delete if table has deleted column
		 */
		public function delete(): bool {
			global $wpdb;
			$table_name = $this->table_name;

			if ( $this->model->isSoftDelete() ) {
				return $this->softDelete();
			} else {
				// delete the item here
				return $wpdb->delete( $table_name, array( 'id' => $this->model->id ) );
			}
		}

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * Soft deletes a model from the database
		 */
		public function softDelete(): bool {
			global $wpdb;
			$table_name = $this->table_name;

			return $wpdb->update( $table_name, [ 'deleted' => 1 ], [ 'id' => $this->model->id ] );
		}
	}
}
