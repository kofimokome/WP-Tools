<?php
/**
 * Created by PhpStorm.
 * User: kofi
 * Date: 6/5/19
 * Time: 12:41 PM
 * @version 1.0.2
 * @author kofi mokome
 */

if ( ! class_exists( 'KMSetting' ) ) {

	#[AllowDynamicProperties]
	class KMSetting {
		private $menu_slug;
		private $fields;
		private $section_id;
		private $sections;

		/**
		 * @param string $menu_slug The menu slug of the menu or sub menu page
		 *
		 * @since 1.0.0
		 */
		public function __construct( $menu_slug ) {
			$menu_slug       = sanitize_text_field( $menu_slug );
			$this->menu_slug = $menu_slug;
			$this->fields    = array();
			$this->sections  = array();
		}

		/**
		 * @since 1.0.0
		 */
		public function show_form() {
			settings_errors(); ?>
            <form method="post" action="options.php">
				<?php
				foreach ( $this->sections as $section ):
					settings_fields( $section[0] );
					do_settings_sections( $this->menu_slug );
				endforeach;
				submit_button();
				?>
            </form>

			<?php
			//echo $this->default_content;
		}

		/**
		 * @since 1.0.0
		 */
		public function save() {
			add_action( 'admin_init', array( $this, 'add_settings' ) );
		}

		/**
		 * @since 1.0.0
		 */
		public function add_settings() {

			foreach ( $this->sections as $section ) {
				add_settings_section(
					$section[0],
					$section[1],
					array( $this, 'default_section_callback' ),
					$this->menu_slug );
			}

			foreach ( $this->fields as $field ) {
				$id         = sanitize_text_field( $field['id'] );
				$label      = sanitize_text_field( $field['label'] );
				$section_id = sanitize_text_field( $field['section_id'] );

				add_settings_field(
					$id,
					$label,
					array( $this, 'default_field_callback' ),
					$this->menu_slug,
					$section_id,
					$field
				);
				register_setting( $section_id, $id );
			}
		}

		/**
		 * @since 1.0.0
		 */
		public function default_field_callback( array $data ) {
			$tip          = $data['tip'];
			$id           = sanitize_text_field( $data['id'] );
			$input_class  = sanitize_html_class( $data['input_class'] );
			$placeholder  = sanitize_text_field( $data['placeholder'] );
			$disabled     = sanitize_text_field( $data['disabled'] );
			$autocomplete = sanitize_text_field( $data['autocomplete'] );
			$min          = sanitize_text_field( $data['min'] );
			$max          = sanitize_text_field( $data['max'] );
			$read_only    = sanitize_text_field( $data['read_only'] );
			$value        = get_option( $id );
			$value        = apply_filters( 'km_setting_' . $id, $value );

			switch ( $data['type'] ) {
				case 'text':
					echo "<p><input type='text' name='" . esc_attr( $id ) . "' value='" . esc_html( $value ) . "' class='" . esc_attr( $input_class ) . "' placeholder='" . esc_attr( $placeholder ) . "'" . ( $read_only ? ' readonly' : '' ) . ( $disabled ? ' disabled' : '' ) . "></p>";
					echo "<strong>" . wp_kses_post( $tip ) . "</strong>";
					break;
				case 'number':
					echo "<p><input type='number' name='" . esc_attr( $id ) . "' value='" . esc_html( $value ) . "' min='" . esc_attr( $min ) . "' max='" . esc_attr( $max ) . "' class='" . esc_attr( $input_class ) . "'  placeholder='" . esc_attr( $placeholder ) . "'" . ( $read_only ? ' readonly' : '' ) . ( $disabled ? ' disabled' : '' ) . "></p>";
					echo "<strong>" . wp_kses_post( $tip ) . "</strong>";
					break;
				case 'textarea':
					echo "<p><textarea name='" . esc_attr( $id ) . "' id='" . esc_attr( $id ) . "' cols='80'
                  rows='8'
                  placeholder='" . esc_attr( $placeholder ) . "' class='" . esc_attr( $input_class ) . "' autocomplete='" . esc_attr( $autocomplete ) . "'" . ( $read_only ? 'readonly' : '' ) . ( $disabled ? ' disabled' : '' ) . ">" . esc_html( $value ) . "</textarea></p>";
					echo "<strong>" . wp_kses_post( $tip ) . "</strong>";
					break;
				case 'checkbox':
					$state = get_option( $id ) == 'on' ? 'checked' : '';
					echo "<p><input type='checkbox' name='" . esc_attr( $id ) . "' id='" . esc_attr( $id ) . "' " . $state . " class='" . esc_attr( $input_class ) . "'" . ( $read_only ? 'onclick="return false;"' : '' ) . ( $disabled ? ' disabled' : '' ) . "></p>";
					echo "<strong>" . wp_kses_post( $tip ) . "</strong>";
					break;
				case 'select':
					$selected_value = get_option( $id );
					echo "<p><select type='text' name='" . esc_attr( $id ) . "' id='" . esc_attr( $id ) . "' class='" . esc_attr( $input_class ) . "'" . ( $read_only ? 'readonly' : '' ) . ( $disabled ? ' disabled' : '' ) . ">";
					foreach ( $data['options'] as $key => $value ):?>
                        <option value='<?php echo esc_attr( $key ) ?>' <?php echo ( $key == $selected_value ) ? 'selected' : '' ?> ><?php echo esc_html( $value ) ?></option>
					<?php
					endforeach;
					echo "</select></p>";
					echo "<strong>" . wp_kses_post( $tip ) . "</strong>";
					break;
				default:
					echo "<< <span style='color: red;'>Please enter a valid field type</span> >>";
					break;
			}
		}

		/**
		 * @param array $data Contains parameters of the field
		 *
		 * @since 1.0.0
		 */
		public function add_field( $data ) {
			$default_data = array(
				'type'           => '',
				'id'             => '',
				'label'          => '',
				'tip'            => '',
				'min'            => '',
				'max'            => '',
				'disabled'       => false,
				'read_only'      => false,
				'input_class'    => '', // class for input element
				'class'          => '', // class for parent element
				'options'        => array( 'Select a value' => '' ),
				'default_option' => '',
				'autocomplete'   => 'on',
				'placeholder'    => ''
			);
			$data         = array_merge( $default_data, $data );
			// todo: compare two arrays
			$data['section_id'] = $this->section_id;
			array_push( $this->fields, $data );

		}

		/**
		 * @since 1.0.0
		 */
		public function add_section( $id, $title = '' ) {
			$title = sanitize_text_field( $title );
			$id    = sanitize_text_field( $id );
			array_push( $this->sections, array( $id, $title ) );
			$this->section_id = $id;
		}

		/**
		 * @since 1.0.0
		 */
		public function default_section_callback() {

		}
	}
}
