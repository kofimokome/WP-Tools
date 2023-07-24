<?php
/**
 * @author kofimokome
 */

if ( ! class_exists( 'KMRoute' ) ) {

	class KMRoute {
		private static array $routes = [];
		private static array $allQueryVars = [];
		private static array $names;
		private static string $currentMiddleware = '';
		private static string $currentGroup = '';
		private static array $middlewares = [];
		private string $route;
		private string $middleware;
		private string $view;
		private array $queryVars;
		private array $regex;

		private function __construct( string $route, string $view, string $middleware = '' ) {
			if ( $route[0] == '/' ) {
				$route = substr( $route, 1 );
			}
			if ( self::$currentGroup == '' ) {
				$this->route     = $route;
				$this->queryVars = [];
			} else {
				if ( $route == '' ) {
					$this->route = self::$currentGroup;
				} else {
					$this->route = self::$currentGroup . '/' . $route;
				}
				$this->queryVars = [ self::$currentGroup ];
			}
			$this->middleware = $middleware == '' ? self::$currentMiddleware : $middleware;
			$this->view       = $view;
			$this->regex      = [];
		}

		public static function group( string $group, \Closure $callback ): void {
			if ( $group[0] == '/' ) {
				$group = substr( $group, 1 );
			}
			$oldGroup = self::$currentGroup;
			if ( $oldGroup == '' ) {
				self::$currentGroup = $group;
			} else {
				self::$currentGroup = $oldGroup . '/' . $group;
			}
			$callback();
			self::$currentGroup = $oldGroup;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function registerMiddleware( string $middleware, \Closure $callback ): void {
			self::$middlewares[ $middleware ] = $callback;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function middleware( string $middleware, \Closure $callback ): void {
			self::$currentMiddleware = $middleware;
			$callback();
			self::$currentMiddleware = '';
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function get( string $route, $view ): KMRoute {
			$route          = new KMRoute( $route, $view, self::$currentMiddleware );
			self::$routes[] = $route;

			return $route;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function registerRoutes(): void {
			foreach ( self::$routes as $route ) {
				$route->registerRoute();
			}
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		private function registerRoute(): void {
			$route = str_replace( ':', '', $this->route );
			// split route into parts after /
			$route_parts = explode( '/', $route );
			// remove empty parts
			$route_parts         = array_filter( $route_parts, function ( $a ) {
				return strlen( $a ) > 0;
			} );
			$capture_group_index = 1;
			$id                  = 0;
			$matches             = array_reduce( $route_parts, function ( $a, $b ) use ( &$id, &$capture_group_index ) {
				$regex  = array_keys( $this->regex );
				$return = '';
				if ( $id == 0 ) {
					if ( in_array( $b, $regex ) ) {
						$return = $b . '=$matches[' . $capture_group_index . ']';
						$capture_group_index ++;
					} else {
						$return = $b . '=' . $b;
					}
				} else {
					if ( in_array( $b, $regex ) ) {
						$return = $a . '&' . $b . '=$matches[' . $capture_group_index . ']';
						$capture_group_index ++;
					} else {
						$return = $a . '&' . $b . '=' . $b;
					}
				}
				$id ++;

				return $return;
			}, '' );
			foreach ( $this->regex as $key => $value ) {
				$pos = strpos( $this->route, ':' . $key );

				if ( ( $pos + strlen( ":" . $key ) ) == strlen( $this->route ) ) {
					$this->route = str_replace( ':' . $key, $value . '[/]?$', $this->route );
				} else {
					$this->route = str_replace( ':' . $key, $value, $this->route );
				}
				$this->queryVars[] = $key;
			}

			add_action( 'init', function () use ( $matches ) {
				add_rewrite_rule( $this->route, 'index.php?' . $matches, 'top' );
			}, 999 );

			add_action( 'template_include', function ( $template ) use ( $route_parts, $matches ) {
				$return_template = 0;
				if ( sizeof( $this->queryVars ) > 0 ) {
					$route_parts = $this->queryVars;
				}
				if ( sizeof( $route_parts ) > 0 ) {
					foreach ( $route_parts as $var ) {
						if ( $return_template == 0 ) {
							$return_template = ( get_query_var( $var ) == false || get_query_var( $var ) == '' );
						} else {
							$return_template = $return_template && ( ( get_query_var( $var ) == false || get_query_var( $var ) == '' ) );
						}
					}

				} else if ( get_query_var( $route_parts[0] ) == false || get_query_var( $route_parts[0] ) == '' ) {
					$return_template = true;
				}

				/**
				 * Sometimes registering two routes /app and /app/projects/new will result in a bug.
				 * Since the app query var will be available in both routes.
				 * This will make WordPress render the html for /app and /app/projects/new if you open /app/projects/new
				 */

				// 1. We get all the query vars that are registered
				global $wp_query;
				$wp_query_vars = $wp_query->query_vars;

				// 2. We get all the query vars that are registered by this route, which will be in the $route_parts array

				// 3. We get all the query vars that are not registered by this route, which will be in the $not_registered_vars array
				$not_registered_vars = array_filter( static::$allQueryVars, function ( $queryVar ) use ( $route_parts ) {
					return ! in_array( $queryVar, $route_parts );
				} );

				// 4. We check if the query vars that are not registered by this route are in the wp_query_vars
				$keys                        = array_keys( $wp_query_vars );
				$not_registered_vars_present = array_filter( $not_registered_vars, function ( $queryVar ) use ( $keys ) {
					return in_array( $queryVar, $keys );
				} );

				// 5. If any is present, we return the template
				if ( sizeof( $not_registered_vars_present ) > 0 ) {
					$return_template = true;
				}

				if ( $return_template ) {
					return $template;
				}

				// 6. Else we return the view
				if ( $this->middleware == '' ) {
					return $this->defaultMiddleware( $this->view );
				}

				return self::$middlewares[ $this->middleware ]( $this->view );
			}, 999 );

			add_filter( 'query_vars', function ( $query_vars ) use ( $route_parts ) {
				if ( sizeof( $this->queryVars ) > 0 ) {
					$route_parts = $this->queryVars;
				}
				foreach ( $route_parts as $route_part ) {
					$query_vars[] = $route_part;
				}

				static::$allQueryVars = array_merge( static::$allQueryVars, $route_parts );

				return $query_vars;
			} );
		}

		/**
		 * @throws Exception
		 * @since 1.0.0
		 * @author kofimokome
		 */
		public function defaultMiddleware( $view ): string {
			return self::renderView( $view );
		}

		public static function renderView( $template = '', $echo = true ) {

			$parent_module_folder = KMCF7MS_MODULE_DIR;
			$template             = str_replace( '.', '/', $template );

			// Start output buffering.
			ob_start();
			ob_implicit_flush( 0 );
			try {
				$env       = KMEnv::getEnv();
				$views_dir = $env['VIEWS_DIR'];
				// remove trailing / from $views_dir if any
				$views_dir = rtrim( $views_dir, '/' );

				$plugin_dir = self::getPluginDir();

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
		private static function getPluginDir(): bool|string {
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
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public static function getRoute( string $name ): string|bool {
			return self::$names[ $name ] ?? false;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function regex( array $regex ): void {
			$route_parts = explode( '/', $this->route );
//		$this->queryVars = [ $route_parts[0] ];
			foreach ( $regex as $key => $value ) {
				$this->queryVars[] = $key;
			}
			$this->regex = $regex;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function queryVars( array $vars ): KMRoute {

			$this->queryVars = $vars;

			return $this;
		}

		/**
		 * @author kofimokome
		 * @since 1.0.0
		 */
		public function name( string $name ): KMRoute {
			self::$names[ $name ] = $this->route;

			return $this;
		}
	}
}