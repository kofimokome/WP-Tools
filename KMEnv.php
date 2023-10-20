<?php
/**
 * @author kofimokome
 */

if ( ! class_exists( 'KMEnv' ) ) {

	class KMEnv {
		private $env = [];
		private $envFile = '.env';
		private $plugin_path;
		private $plugin_basename;

		public function setEnvFile( string $envFile ): void {
			$this->envFile = $envFile;
		}

		public function __construct( string $context ) {
			$this->plugin_path     = plugin_dir_path( $context );
			$this->plugin_basename = plugin_basename( $context );
		}

		/**
		 * @author kofimokome
		 * Get an array with the environment variables
		 */
		public function getEnv(): array {
			if ( sizeof( $this->env ) == 0 ) {
				$chars = explode( '/', $this->plugin_basename );
				if ( sizeof( $chars ) > 0 ) {
					$plugin_basename = $chars[0];

					if ( $this->envFile == '' ) {
						$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_basename . '/.env';
					} else {
						$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_basename . '/' . trim( $this->envFile );
					}
					// check if the file exists
					if ( ! file_exists( $plugin_path ) ) {
						throw new Exception( 'Env file not found' );
					}
					$envFile = file_get_contents( $plugin_path );
					$lines   = explode( "\n", $envFile );
					foreach ( $lines as $line ) {
						if ( strlen( $line ) == 0 || $line[0] == '#' ) {
							continue;
						}
						$line = explode( '=', $line );
						if ( count( $line ) == 2 ) {
							if ( is_numeric( $line[0] ) ) {
								continue;
							}
							$this->env[ strtoupper( trim( $line[0] ) ) ] = trim( str_replace( "'", '', $line[1] ) );
						}
					}
				}
			}

			return $this->env;
		}

	}
}