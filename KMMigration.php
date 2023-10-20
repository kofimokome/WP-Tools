<?php

/**
 * @author kofimokome
 */

if ( ! class_exists( 'KMMigration' ) ) {

	abstract class KMMigration {
		protected $table_name;
		protected $is_update = false;
		protected $add_prefix = true;
		protected $context;


		public function __construct( KMMigrationManager $migration_manager, string $context ) {
			$this->context                   = $context;
			$migration_manager->migrations[] = $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function getTableName(): string {
			global $wpdb;

			if ( $this->add_prefix ) {
				$env        = ( new KMEnv( $this->context ) )->getEnv();
				$table_name = $wpdb->prefix . trim( $env['TABLE_PREFIX'] ) . $this->table_name;

				return $table_name;
			}

			return $this->table_name;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function isUpdate(): string {
			return $this->is_update;
		}


		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * The reverser of a migration
		 */
		abstract public function down( KMBlueprint $blueprint );

		/**
		 * @since 1.0.0
		 * @author kofimokome
		 * The actions of a migration
		 */
		abstract public function up( KMBlueprint $blueprint );
	}
}
