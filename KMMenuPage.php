<?php
/**
 * Created by PhpStorm.
 * User: kofi
 * Date: 6/5/19
 * Time: 11:59 AM
 * @version 1.0.4
 * @author kofi mokome
 */
if ( ! class_exists( 'KMMenuPage' ) ) {
	#[AllowDynamicProperties]
	class KMMenuPage {
		private $page_title;
		private $menu_title;
		private $capability;
		private $menu_slug;
		private $icon_url;
		private $position;
		private $function;
		private $sub_menu_pages;

		/**
		 * @param array $data
		 *
		 * @since 1.0.0
		 */
		public function __construct( $data ) {
			$default_data = array(
				'page_title' => '',
				'menu_title' => '',
				'capability' => '',
				'menu_slug'  => '',
				'icon_url'   => '',
				'position'   => null,
				'function'   => ''
			);
			$data         = array_merge( $default_data, $data );
			$page_title   = sanitize_text_field( $data['page_title'] );
			$menu_title   = wp_kses_post( $data['menu_title'] );
			$capability   = sanitize_text_field( $data['capability'] );
			$menu_slug    = sanitize_text_field( $data['menu_slug'] );
			$icon_url     = sanitize_text_field( $data['icon_url'] );
			$position     = sanitize_text_field( $data['position'] );

			$this->page_title = $page_title;
			$this->menu_title = $menu_title;
			$this->capability = $capability;
			$this->menu_slug  = $menu_slug;
			$this->icon_url   = $icon_url;
			$this->position   = $position;
			$this->function   = $data['function'] == '' ? array( $this, 'default_function' ) : $data['function'];

			$this->sub_menu_pages = array();
		}

		/**
		 * @since 1.0.0
		 */
		public function run() {
			add_action( 'admin_menu', array( $this, 'create_menu_page' ) );
		}

		/**
		 * @since 1.0.0
		 */
		public function create_menu_page() {
			add_menu_page(
				$this->page_title,
				$this->menu_title,
				$this->capability,
				$this->menu_slug,
				$this->function,
				$this->icon_url,
				intval( $this->position )

			);

			foreach ( $this->sub_menu_pages as $sub_menu_page ) {
				$sub_menu_page->run();
			}
		}

		/**
		 * @since 1.0.0
		 */
		public function default_function() {
			echo "";
		}

		/**
		 * @since 1.0.0
		 */
		public function get_menu_slug() {
			return $this->menu_slug;
		}

		/**
		 * @param KMSubMenuPage $sub_menu_page
		 *
		 * @since 1.0.0
		 */
		public function add_sub_menu_page( $sub_menu_page ) {
			$sub_menu_page->set_parent_slug( $this->menu_slug );
			array_push( $this->sub_menu_pages, $sub_menu_page );
		}

	}
}

