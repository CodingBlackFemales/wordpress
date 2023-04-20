<?php
/**
 * LearnDash Shortcode Section for Registration form [ld_registration].
 *
 * @since 2.4.0
 * @package LearnDash\Settings\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_ld_registration' ) ) ) {
	/**
	 * Class LearnDash Shortcode Section for Registration form [ld_registration].
	 *
	 * @since 2.4.0
	 */
	class LearnDash_Shortcodes_Section_ld_registration extends LearnDash_Shortcodes_Section /* phpcs:ignore PEAR.NamingConventions.ValidClassName.Invalid */ {

		/**
		 * Public constructor for class.
		 *
		 * @since 2.4.0
		 *
		 * @param array $fields_args Field Args.
		 */
		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key   = 'ld_registration';
			$this->shortcodes_section_title = esc_html_x( 'Registration Form', 'placeholder: Topic', 'learndash' );
			$this->shortcodes_section_type  = 1;
			// translators: placeholders: course, group.
			$this->shortcodes_section_description = sprintf( wp_kses_post( _x( 'Registration form user is redirected to when purchasing a %1$s / %2$s.', 'placeholders: course, group.', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ), learndash_get_custom_label_lower( 'group' ) );

			parent::__construct();
		}

		/**
		 * Initialize the shortcode fields.
		 *
		 * @since 2.4.0
		 */
		public function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'width' => array(
					'id'        => $this->shortcodes_section_key . '_width',
					'name'      => 'width',
					'type'      => 'text',
					'label'     => __( 'Form Width', 'learndash' ),
					'help_text' => __( 'Sets the width of the registration form.', 'learndash' ),
					'value'     => '',
					'class'     => 'small-text',
				),
			);

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}
	}
}
