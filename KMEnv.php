<?php
/**
 * @author kofimokome
 */

if ( ! class_exists( 'KMEnv' ) ) {

	class KMEnv {
		private static array $env = [];
		private static string $envFile = '.env';

		public static function setEnvFile( string $envFile ): void {
			self::$envFile = $envFile;
		}

		public static function getPluginPath(): string {

			$plugin_path     = plugin_dir_path( __FILE__ );
			$plugin_basename = plugin_basename( __FILE__ );
			preg_match( "/.+\/wp-content\/plugins\//", $plugin_path, $matches );
			if ( sizeof( $matches ) > 0 ) {
				$plugin_path = $matches[0];
				$chars       = explode( '/', $plugin_basename );
				if ( sizeof( $chars ) > 0 ) {
					$plugin_basename = $chars[0];

					return $plugin_path . $plugin_basename;
				}
			}
			throw new Exception( 'Migrations dir not found' );
		}

		public static function getEnv(): array {
			if ( sizeof( self::$env ) == 0 ) {
				$plugin_path     = plugin_dir_path( __FILE__ );
				$plugin_basename = plugin_basename( __FILE__ );
				preg_match( "/.+\/wp-content\/plugins\//", $plugin_path, $matches );
				if ( sizeof( $matches ) > 0 ) {
					$plugin_path = $matches[0];
					$chars       = explode( '/', $plugin_basename );
					if ( sizeof( $chars ) > 0 ) {
						$plugin_basename = $chars[0];

						if ( self::$envFile == '' ) {
							$plugin_path = $plugin_path . $plugin_basename . '/.env';
						} else {
							$plugin_path = $plugin_path . $plugin_basename . '/' . trim( self::$envFile );
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
								self::$env[ strtoupper( trim( $line[0] ) ) ] = trim( str_replace( "'", '', $line[1] ) );
							}
						}
					}
				}
			}

			return self::$env;
		}

	}
}