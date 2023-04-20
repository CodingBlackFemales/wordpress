<?php
/**
 * LearnDash Admin Shortcodes Section Class.
 *
 * @since 2.4.0
 * @package LearnDash\Settings\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LearnDash_Shortcodes_Section' ) ) {

	/**
	 * Class for LearnDash Admin Course Edit.
	 *
	 * @since 2.4.0
	 */
	class LearnDash_Shortcodes_Section {

		/**
		 * Shortcodes Section Key.
		 *
		 * @var string $shortcodes_section_key
		 */
		protected $shortcodes_section_key = '';

		/**
		 * Shortcodes Section Title.
		 *
		 * @var string $shortcodes_section_title
		 */
		protected $shortcodes_section_title = '';

		/**
		 * Shortcodes Section Type.
		 *
		 * @var integer $shortcodes_section_type
		 */
		protected $shortcodes_section_type = 1;

		/**
		 * Shortcodes Section Description.
		 *
		 * @var string $shortcodes_section_description
		 */
		protected $shortcodes_section_description = '';

		/**
		 * Shortcodes Section Fields.
		 *
		 * @var array $shortcodes_option_fields
		 */
		protected $shortcodes_option_fields = array();

		/**
		 * Shortcodes Section Values.
		 *
		 * @var array $shortcodes_option_values
		 */
		protected $shortcodes_option_values = array();

		/**
		 * This is derived from the $shortcodes_option_fields within the function init_shortcodes_section_fields();
		 *
		 * @var array $shortcodes_settings_fields
		 */
		protected $shortcodes_settings_fields = array();

		/**
		 * This is the HTML form field prefix used.
		 *
		 * @var array $shortcodes_option_fields
		 */
		protected $setting_field_prefix = '';

		/**
		 * Fields Args.
		 *
		 * @var array $fields_args
		 */
		protected $fields_args = array();

		/**
		 * Public constructor for class.
		 *
		 * @since 2.4.0
		 */
		public function __construct() {
			$this->init_shortcodes_section_fields();
		}

		/**
		 * Initialize the Shortcodes Fields.
		 *
		 * @since 2.4.0
		 */
		public function init_shortcodes_section_fields() {
			foreach ( $this->shortcodes_option_fields as $field_id => $setting_option_field ) {
				if ( ! isset( $setting_option_field['label_for'] ) ) {
					$setting_option_field['label_for'] = $setting_option_field['id'];
				}

				if ( ! isset( $setting_option_field['label_for'] ) ) {
					$setting_option_field['label_for'] = $setting_option_field['id'];
				}

				$setting_option_field['setting_option_key'] = $setting_option_field['id'];

				if ( ! isset( $setting_option_field['display_callback'] ) ) {
					$display_ref = LearnDash_Settings_Fields::get_field_instance( $setting_option_field['type'] );
					if ( ! $display_ref ) {
						$setting_option_field['display_callback'] = array( $this, 'field_element_create' );
					} else {
						$setting_option_field['display_callback'] = array( $display_ref, 'create_section_field' );
					}
				}

				$this->shortcodes_settings_fields[ $field_id ] = array(
					'id'       => $setting_option_field['id'],
					'title'    => $setting_option_field['label'],
					'callback' => $setting_option_field['display_callback'],
					'args'     => $setting_option_field,
				);
			}
		}

		/**
		 * Section Fields Create.
		 *
		 * @since 2.4.0
		 *
		 * @param array $field_args Field Args.
		 */
		public function field_element_create( $field_args = array() ) {
			$field_html = '';

			if ( ( isset( $field_args['display_func'] ) ) && ( ! empty( $field_args['display_func'] ) ) && ( is_callable( $field_args['display_func'] ) ) ) {
				call_user_func(
					$field_args['display_func'],
					$field_args,
					$this->setting_field_prefix
				);
			}
		}

		/**
		 * Show Section Fields.
		 *
		 * @since 2.4.0
		 */
		public function show_section_fields() {
			$this->show_shortcodes_section_header();
			echo LearnDash_Settings_Fields::show_section_fields( $this->shortcodes_settings_fields ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			$this->show_shortcodes_section_footer();
		}

		/**
		 * Show Section Header.
		 *
		 * @since 2.4.0
		 */
		public function show_shortcodes_section_header() {
			/**
			 * Action shortcodes section header before form.
			 *
			 * @since 4.0.0
			 *
			 * @param string $shortcodes_section_key Shortcodes section key.
			 */
			do_action( 'learndash_shortcodes_section_header_before_form', $this->shortcodes_section_key );

			?><form id="learndash_shortcodes_form_<?php echo esc_attr( $this->shortcodes_section_key ); ?>" class="learndash_shortcodes_form" shortcode_slug="<?php echo esc_attr( $this->shortcodes_section_key ); ?>" shortcode_type="<?php echo esc_attr( $this->shortcodes_section_type ); ?>">
				<?php
				/**
				 * Action shortcodes section header before title output.
				 *
				 * @since 4.0.0
				 *
				 * @param string $shortcodes_section_key Shortcodes section key.
				 */
				do_action( 'learndash_shortcodes_section_header_before_title', $this->shortcodes_section_key );
				?>
				<?php $this->show_shortcodes_section_title(); ?>
				<?php
				/**
				 * Action shortcodes section header before description output.
				 *
				 * @since 4.0.0
				 *
				 * @param string $shortcodes_section_key Shortcodes section key.
				 */
				do_action( 'learndash_shortcodes_section_header_before_description', $this->shortcodes_section_key );
				?>
				<?php $this->show_shortcodes_section_description(); ?>
				<?php
				/**
				 * Action shortcodes section before content output.
				 *
				 * @since 4.0.0
				 *
				 * @param string $shortcodes_section_key Shortcodes section key.
				 */
				do_action( 'learndash_shortcodes_section_before_content', $this->shortcodes_section_key );
				?>
				<div class="sfwd sfwd_options learndash_shortcodes_section" style="clear:left">
				<?php
		}

		/**
		 * Show Section Footer.
		 *
		 * @since 2.4.0
		 */
		public function show_shortcodes_section_footer() {
			?>
				</div>
				<?php
				/**
				 * Action shortcodes section after content output.
				 *
				 * @since 4.0.0
				 *
				 * @param string $shortcodes_section_key Shortcodes section key.
				 */
				do_action( 'learndash_shortcodes_section_after_content', $this->shortcodes_section_key );
				?>
				<?php $this->show_shortcodes_section_footer_extra(); ?>

				<?php
				/**
				 * Action shortcodes section before button output.
				 *
				 * @since 4.0.0
				 *
				 * @param string $shortcodes_section_key Shortcodes section key.
				 */
				do_action( 'learndash_shortcodes_section_before_button', $this->shortcodes_section_key );
				?>
				<p style="clear:left"><input type="submit" class="button-primary" value="<?php esc_html_e( 'Insert Shortcode', 'learndash' ); ?>"></p>
				<?php
				/**
				 * Action shortcodes section after button output.
				 *
				 * @since 4.0.0
				 *
				 * @param string $shortcodes_section_key Shortcodes section key.
				 */
				do_action( 'learndash_shortcodes_section_after_button', $this->shortcodes_section_key );
				?>
			</form>
			<?php
			/**
			 * Action shortcodes section header after form.
			 *
			 * @since 4.0.0
			 *
			 * @param string $shortcodes_section_key Shortcodes section key.
			 */
			do_action( 'learndash_shortcodes_section_header_after_form', $this->shortcodes_section_key );
		}

		/**
		 * Show Section Footer Extra.
		 *
		 * @since 2.4.0
		 */
		public function show_shortcodes_section_footer_extra() {
			// This is a hook called after the section closing </div> to allow adding JS/CSS.
		}

		/**
		 * Get Section Key.
		 *
		 * @since 2.4.0
		 */
		public function get_shortcodes_section_key() {
			return $this->shortcodes_section_key;
		}

		/**
		 * Get Section Title.
		 *
		 * @since 2.4.0
		 */
		public function get_shortcodes_section_title() {
			return $this->shortcodes_section_title;
		}

		/**
		 * Show Section Key.
		 *
		 * @since 2.4.0
		 */
		public function show_shortcodes_section_title() {
			if ( ! empty( $this->shortcodes_section_title ) ) {
				?>
				<h2><?php echo wp_kses_post( $this->shortcodes_section_title ); ?> [<?php echo esc_html( $this->shortcodes_section_key ); ?>]</h2>
				<?php
			}
		}

		/**
		 * Get Section Description.
		 *
		 * @since 2.4.0
		 */
		public function get_shortcodes_section_description() {
			return $this->shortcodes_section_description;
		}

		/**
		 * Show Section Description.
		 *
		 * @since 2.4.0
		 */
		public function show_shortcodes_section_description() {
			if ( ! empty( $this->shortcodes_section_description ) ) {
				echo wp_kses_post( wpautop( $this->shortcodes_section_description ) );
			}
		}

		/**
		 * Get Shortcode field by key;
		 *
		 * @since 3.1.4
		 *
		 * @param string $field_key Field key.
		 */
		public function get_shortcodes_section_field( $field_key = '' ) {
			if ( ( ! empty( $field_key ) ) && ( isset( $this->shortcodes_option_fields[ $field_key ] ) ) ) {
				return $this->shortcodes_option_fields[ $field_key ];
			}
		}
	}
}
