<?php
/**
 * LearnDash Shortcode Section for Course Navigation [ld_navigation].
 *
 * @since 4.0.0
 * @package LearnDash\Settings\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_ld_navigation' ) ) ) {
	/**
	 * Class LearnDash Shortcode Section for Navigation [ld_navigation]
	 *
	 * @since 2.4.0
	 */
	class LearnDash_Shortcodes_Section_ld_navigation extends LearnDash_Shortcodes_Section /* phpcs:ignore PEAR.NamingConventions.ValidClassName.Invalid */ {

		/**
		 * Public constructor for class.
		 *
		 * @since 2.4.0
		 *
		 * @param array $fields_args Field Args.
		 */
		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key   = 'ld_navigation';
			$this->shortcodes_section_title = sprintf(
				// translators: placeholder: Course.
				esc_html_x( '%s Navigation', 'placeholder: Course', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'course' )
			);
			$this->shortcodes_section_type        = 1;
			$this->shortcodes_section_description = sprintf(
				// translators: placeholders: Course, lesson, topics, quizzes.
				esc_html_x( 'This shortcode displays the %1$s Content table (%2$s, %3$s, and %4$s) when inserted on a page or post.', 'placeholders: Course, lesson, topics, quizzes', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'course' ),
				learndash_get_custom_label_lower( 'lessons' ),
				learndash_get_custom_label_lower( 'topics' ),
				learndash_get_custom_label_lower( 'quizzes' )
			);

			parent::__construct();
		}

		/**
		 * Initialize the shortcode fields.
		 *
		 * @since 2.4.0
		 */
		public function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'course_id' => array(
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
				'post_id'   => array(
					'id'        => $this->shortcodes_section_key . '_post_id',
					'name'      => 'post_id',
					'type'      => 'number',
					'label'     => esc_html__( 'Step ID', 'learndash' ),
					'help_text' => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Enter single Step ID. Leave blank if used within a %s.', 'placeholders: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'     => '',
					'class'     => 'small-text',
					'required'  => 'required',
				),
			);

			if ( ( isset( $this->fields_args['post_type'] ) ) && ( in_array( $this->fields_args['post_type'], learndash_get_post_types( 'course' ), true ) ) ) {
				unset( $this->shortcodes_option_fields['course_id']['required'] );
				unset( $this->shortcodes_option_fields['post_id']['required'] );
			}

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}
	}
}
