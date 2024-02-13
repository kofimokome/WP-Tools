<?php

/**
 * @author kofimokome
 */

require_once 'KMEnv.php';
require_once 'KMRouteManager.php';
require_once 'KMMigrationManager.php';
require_once 'KMBlueprint.php';
require_once 'KMBuilder.php';
require_once 'KMMenuPage.php';
require_once 'KMSubMenuPage.php';
require_once 'KMSetting.php';
require_once 'KMColumn.php';
require_once 'KMModel.php';
require_once 'KMMigration.php';
require_once 'KMRoute.php';
require_once 'KMValidator.php';
require_once 'lib/plural/Plural.php';


if ( ! class_exists( 'WordPressTools' ) ) {
	class WordPressTools {
		public $env;
		public $route_manager;
		public $migration_manager;
		private $plugin_basename;
		private $context;
		private static $instances = [];


		public function __construct( string $context ) {
			$this->env                                                    = ( new KMEnv( $context ) )->getEnv();
			$this->route_manager                                          = new KMRouteManager( $context );
			$this->plugin_basename                                        = plugin_basename( $context );
			$this->context                                                = $context;
			$this->migration_manager                                      = new KMMigrationManager( $this->getPluginDir(), $context );
			self::$instances[ explode( '/', $this->plugin_basename )[0] ] = $this;

		}

		/**
		 * @author kofimokome
		 */
		public static function getInstance( string $context ): WordPressTools {
			$plugin_basename = plugin_basename( $context );
			$plugin          = explode( '/', $plugin_basename )[0];

			if ( ! isset( self::$instances[ $plugin ] ) ) {
				throw new Exception( 'WordPressTools instance not found' );
			}

			return self::$instances[ $plugin ];
		}

		/**
		 * @author kofimokome
		 */
		public function routes(): KMRoute {
			$route = new KMRoute( $this->route_manager );

			return $route;
		}

		/**
		 * @author kofimokome
		 */
		public function renderView( string $view, $echo = true ) {
			return $this->route_manager->renderView( $view, $echo );
		}

		/**
		 * @author kofimokome
		 */
		public function viewPath( string $view ) {
			return $this->route_manager->viewPath( $view );
		}

		/**
		 * @author kofimokome
		 */
		public function route( string $name, array $params = [] ): string {
			return $this->route_manager->route( $name, $params );
		}


		/**
		 * @throws Exception
		 * @author kofimokome
		 */
		public function getPluginDir() {

			$chars = explode( '/', $this->plugin_basename );
			if ( sizeof( $chars ) > 0 ) {
				$plugin_basename = $chars[0];

				return WP_PLUGIN_DIR . '/' . $plugin_basename;
			}

			throw new Exception( 'Could not get plugin directory' );
		}

		/**
		 * @author kofimokome
		 */
		public function getPluginURL(): string {
			return rtrim( plugin_dir_url( $this->context ), '/' );
		}

		public function env() {
			return ( new KMEnv( $this->context ) )->getEnv();
		}
	}

}
