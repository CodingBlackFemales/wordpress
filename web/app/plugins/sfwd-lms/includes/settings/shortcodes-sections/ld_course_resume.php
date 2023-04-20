<?php
/**
 * LearnDash Shortcode Section for Course Resume [ld_course_resume].
 *
 * @since 3.1.4
 * @package LearnDash\Settings\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_ld_course_resume' ) ) ) {
	/**
	 * Class LearnDash Shortcode Section for Course Resume [ld_course_resume].
	 *
	 * @since 3.1.4
	 */
	class LearnDash_Shortcodes_Section_ld_course_resume extends LearnDash_Shortcodes_Section /* phpcs:ignore PEAR.NamingConventions.ValidClassName.Invalid */ {

		/**
		 * Public constructor for class.
		 *
		 * @since 3.1.4
		 *
		 * @param array $fields_args Field Args.
		 */
		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key   = 'ld_course_resume';
			$this->shortcodes_section_title = sprintf(
				// translators: placeholder: Course.
				esc_html_x( '%s Resume', 'placeholders: Course', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'course' )
			);

			$this->shortcodes_section_type        = 2;
			$this->shortcodes_section_description = sprintf(
				// translators: placeholder: Course.
				esc_html_x( 'Return to %s link/button.', 'placeholders: Course', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'course' )
			);

			parent::__construct();
		}

		/**
		 * Initialize the shortcode fields.
		 *
		 * @since 3.1.4
		 */
		public function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'course_id'  => array(
					'id'        => $this->shortcodes_section_key . '_course_id',
					'name'      => 'course_id',
					'type'      => 'number',
					'label'     => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s ID', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'help_text' => sprintf(
						// translators: placeholders: Course, Course.
						esc_html_x( 'Enter single %1$s ID. Leave blank for current %2$s.', 'placeholders: Course, Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'     => '',
					'class'     => 'small-text',
					'required'  => 'required',
				),
				'user_id'    => array(
					'id'        => $this->shortcodes_section_key . '_user_id',
					'name'      => 'user_id',
					'type'      => 'number',
					'label'     => esc_html__( 'User ID', 'learndash' ),
					'help_text' => esc_html__( 'Enter specific User ID. Leave blank for current User.', 'learndash' ),
					'value'     => '',
					'class'     => 'small-text',
				),
				'label'      => array(
					'id'          => $this->shortcodes_section_key . '_label',
					'name'        => 'label',
					'type'        => 'text',
					'label'       => esc_html__( 'Label', 'learndash' ),
					'help_text'   => esc_html__( 'Label for link shown to user', 'learndash' ),
					'placeholder' => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Resume %s', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'       => '',
				),
				'button'     => array(
					'id'        => $this->shortcodes_section_key . '_button',
					'name'      => 'button',
					'type'      => 'select',
					'label'     => esc_html__( 'Show as button', 'learndash' ),
					'help_text' => esc_html__( 'shows as button content.', 'learndash' ),
					'value'     => 'true',
					'options'   => array(
						''      => esc_html__( 'Yes (default)', 'learndash' ),
						'false' => esc_html__( 'No', 'learndash' ),
					),
				),
				'html_class' => array(
					'id'        => $this->shortcodes_section_key . '_html_class',
					'name'      => 'html_class',
					'type'      => 'text',
					'label'     => esc_html__( 'HTML Class', 'learndash' ),
					'help_text' => esc_html__( 'HTML class for link element', 'learndash' ),
					'value'     => '',
				),
			);

			if ( ( isset( $this->fields_args['post_type'] ) ) && ( in_array( $this->fields_args['post_type'], learndash_get_post_types( 'course' ), true ) ) ) {
				unset( $this->shortcodes_option_fields['course_id']['required'] );
			}

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}
	}
}
