<?php
/**
 * @author kofimokome
 */

if ( ! class_exists( 'KMColumn' ) ) {

	#[AllowDynamicProperties]
	class KMColumn {
		private $name;
		private $attributes;
		private $is_update;
		private $is_delete;
		private $is_change;
		private $is_rename;
		private $new_name;
		private $placeholders;

		public function __construct( string $name, array $attributes = [], array $extras = [] ) {
			$this->name      = $name;
			$default_extras  = array(
				'is_update' => false,
				'is_delete' => false,
				'is_change' => false,
				'is_rename' => false,
				'new_name'  => ''
			);
			$extras          = array_merge( $default_extras, $extras );
			$this->is_update = $extras['is_update'];
			$this->is_rename = $extras['is_rename'];
			$this->is_delete = $extras['is_delete'];
			$this->is_change = $extras['is_change'];
			$this->new_name  = $extras['new_name'];

			array_push( $attributes, 'NOT NULL' );
			$this->attributes = $attributes;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function nullable(): KMColumn {
			if ( ( $key = array_search( 'NOT NULL', $this->attributes ) ) !== false ) {
				unset( $this->attributes[ $key ] );
				$this->attributes = array_values( $this->attributes );
			}
			array_push( $this->attributes, 'NULL' );

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function unsigned(): KMColumn {
			if ( ( $key = array_search( 'SIGNED', $this->attributes ) ) !== false ) {
				unset( $this->attributes[ $key ] );
				$this->attributes = array_values( $this->attributes );
			}
			array_splice( $this->attributes, 1, 0, 'UNSIGNED' );

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function primary(): KMColumn {
			array_push( $this->attributes, 'PRIMARY KEY' );

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function autoIncrement(): KMColumn {
			array_push( $this->attributes, 'AUTO_INCREMENT' );

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function default( $value ): KMColumn {
			if ( ! is_numeric( $value ) && strlen( trim( $value ) ) == 0 ) {
				return $this;
			}

			$this->attributes[] = 'DEFAULT';
			$this->attributes[] = "'$value'";

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function toString(): string {
			$attributes = implode( ' ', $this->attributes );

			if ( $this->is_delete ) {
				return ' DROP COLUMN `' . $this->name . '`';
			} else if ( $this->is_update ) {
				return ' ADD `' . $this->name . '` ' . $attributes;
			} else if ( $this->is_change ) {
				return ' CHANGE `' . $this->name . '` `' . $this->new_name . '` ' . $attributes;
			} else if ( $this->is_rename ) {
				return ' RENAME COLUMN `' . $this->name . '` TO `' . $this->new_name . '`';
			} else {
				return '`' . $this->name . '` ' . $attributes;
			}
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function getName(): string {
			return $this->name;
		}
	}
}