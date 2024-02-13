<?php
/**
 * @author kofimokome
 */

if ( ! class_exists( 'KMRouteManager' ) ) {

	class KMRouteManager {
		public $routes = [];
		public $allQueryVars = [];
		public $names;
		public $currentMiddleware = '';
		public $currentGroup = '';
		public $middlewares = [];
		private $context;

		public function __construct( string $context ) {
			$this->context = $context;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function registerMiddleware( string $middleware, Closure $callback ): void {
			$this->middlewares[ $middleware ] = $callback;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function middleware( string $middleware, Closure $callback ): void {
			$this->currentMiddleware = $middleware;
			$callback();
			$this->currentMiddleware = '';
		}


		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function registerRoutes(): void {
			foreach ( $this->routes as $route ) {
				$route->registerRoute();
			}
		}

		/**
		 * @throws Exception
		 * @since 1.0.0
		 * @author kofimokome
		 */
		public function defaultMiddleware( $view ): string {
			return $this->renderView( $view );
		}

		/**
		 * @author kofimokome
		 * Returns the path of a view in the views directory
		 */
		public function viewPath( $template = '' ) {
			$template = str_replace( '.', '/', $template );

			$env       = ( new KMEnv( $this->context ) )->getEnv();
			$views_dir = $env['VIEWS_DIR'];
			// remove trailing / from $views_dir if any
			$views_dir = rtrim( $views_dir, '/' );

			$plugin_dir = $this->getPluginDir();

			return $plugin_dir . $views_dir . '/' . $template . '.php';

		}

		/**
		 * @author kofimokome
		 * Display the content of a view in the views directory
		 */
		public function renderView( $template = '', $echo = true ) {
			$template = str_replace( '.', '/', $template );

			// Start output buffering.
			ob_start();
			ob_implicit_flush( 0 );
			try {
				$env       = ( new KMEnv( $this->context ) )->getEnv();
				$views_dir = $env['VIEWS_DIR'];
				// remove trailing / from $views_dir if any
				$views_dir = rtrim( $views_dir, '/' );

				$plugin_dir = $this->getPluginDir();

				include $plugin_dir . $views_dir . '/' . $template . '.php';

			} catch ( Exception $e ) {
				ob_end_clean();
				throw $e;
			}

			if ( $echo ) {
				echo ob_get_clean();
			} else {
				return ob_get_clean();
			}
		}

		/**
		 * @throws Exception
		 */
		public function getPluginDir() {
			$plugin_basename = plugin_basename( $this->context );

			$chars = explode( '/', $plugin_basename );

			if ( sizeof( $chars ) > 0 ) {
				$plugin_basename = $chars[0];

				return WP_PLUGIN_DIR . '/' . $plugin_basename;
			}
			throw new Exception( 'Could not get plugin directory' );
		}

		/**
		 * @author kofimokome
		 * Gets the link associated with a route
		 */
		public function route( string $name, array $params = [] ): string {
			if ( $route = $this->getRoute( $name ) ) {
				foreach ( $params as $key => $value ) {
					$route = str_replace( ':' . $key, $value, $route );
				}

				return site_url( $route );
			} else {
				return 'bro';
			}
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function getRoute( string $name ) {
			return $this->names[ $name ] ?? false;
		}
	}
}