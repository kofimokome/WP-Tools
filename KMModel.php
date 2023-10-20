<?php

/**
 * @author kofimokome
 */

if ( ! class_exists( 'KMModel' ) ) {

	#[AllowDynamicProperties]
	class KMModel {
		protected $timestamps = false;
		protected $table_name = '';
		protected $soft_delete = false;
		public $id = 0;
		private $context;

		function __construct( string $context = '' ) {
			if ( $context == '' ) {
				$t = debug_backtrace();

				$context = $t[0]['file'];
			}
			$this->context = $context;
			// do something here
		}

		/**
		 * @author kofmokome
		 * Checks if a model has timestamps enabled
		 */
		public function hasTimeStamps() {
			return $this->timestamps;
		}

		/**
		 * @author kofmokome
		 * Checks if a model has soft delete enabled
		 */
		public function isSoftDelete() {
			return $this->soft_delete;
		}

		public function __call( $method, $arguments ) {
			$builder = new KMBuilder( $this->getTableName(), $this, $this->context );

			return $builder->$method( ...$arguments );
		}

		public static function __callStatic( $method, $arguments ) {
			$called = get_called_class();
			$t      = debug_backtrace();

			$context = $t[0]['file'];
			$model   = new $called( $context );

			$query = new KMBuilder( $model->getTableName(), $model, $context );

			return $query->$method( ...$arguments );
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function getTableName(): string {
			global $wpdb;

			$env        = ( new KMEnv( $this->context ) )->getEnv();
			$table_name = $this->table_name;
			if ( $table_name == '' ) {
				$model      = get_called_class();
				$table_name = strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $model ) );
				if ( sizeof( $names = explode( '\\', $table_name ) ) > 0 ) {
					$table_name = $names[1] ?? $names[0];
				}
				$table_name = ltrim( $table_name, '_' );
				$table_name = Plural( $table_name );
				$table_name = $wpdb->prefix . trim( $env['TABLE_PREFIX'] ) . $table_name;
			}


			return $table_name;

		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function setTableName( $table_name ) {
			$this->table_name = $table_name;
		}
	}
}
