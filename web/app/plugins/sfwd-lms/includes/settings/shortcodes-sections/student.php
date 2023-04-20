<?php
/**
 * LearnDash Shortcode Section for Student [student].
 *
 * @since 2.4.0
 * @package LearnDash\Settings\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_student' ) ) ) {
	/**
	 * Class LearnDash Shortcode Section for Student [student].
	 *
	 * @since 2.4.0
	 */
	class LearnDash_Shortcodes_Section_student extends LearnDash_Shortcodes_Section /* phpcs:ignore PEAR.NamingConventions.ValidClassName.Invalid */ {

		/**
		 * Public constructor for class.
		 *
		 * @since 2.4.0
		 *
		 * @param array $fields_args Field Args.
		 */
		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key   = 'student';
			$this->shortcodes_section_title = esc_html__( 'Student', 'learndash' );
			$this->shortcodes_section_type  = 2;
			// translators: placeholder: course.
			$this->shortcodes_section_description = sprintf( wp_kses_post( _x( 'This shortcode shows the content if the user is enrolled in the %s. The shortcode can be used on <strong>any</strong> page or widget area.', 'placeholder: course', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ) );

			parent::__construct();
		}

		/**
		 * Initialize the shortcode fields.
		 *
		 * @since 2.4.0
		 */
		public function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'message'      => array(
					'id'        => $this->shortcodes_section_key . '_message',
					'name'      => 'message',
					'type'      => 'textarea',
					'label'     => esc_html__( 'Message shown to user', 'learndash' ),
					'help_text' => esc_html__( 'Message shown to user', 'learndash' ),
					'value'     => '',
					'required'  => 'required',
				),
				'display_type' => array(
					'id'      => $this->shortcodes_section_key . '_display_type',
					'name'    => 'display_type',
					'type'    => 'select',
					'label'   => esc_html__( 'Display Type', 'learndash' ),
					'value'   => '',
					'options' => array(
						'' => esc_html__( 'Select a Display Type', 'learndash' ),
						learndash_get_post_type_slug( 'course' ) => learndash_get_custom_label( 'course' ),
						learndash_get_post_type_slug( 'group' ) => learndash_get_custom_label( 'group' ),
					),
					'attrs'   => array(
						'data-shortcode-exclude' => '1',
					),
				),
				'course_id'    => array(
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
				'group_id'     => array(
					'id'        => $this->shortcodes_section_key . '_group_id',
					'name'      => 'group_id',
					'type'      => 'number',
					'label'     => sprintf(
						// translators: placeholder: Group.
						esc_html_x( '%s ID', 'placeholder: Group', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'group' )
					),
					'help_text' => sprintf(
						// translators: placeholders: Group, Group.
						esc_html_x( 'Enter single %1$s ID. Leave blank for current %2$s.', 'placeholders: Group, Group', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'group' ),
						LearnDash_Custom_Label::get_label( 'group' )
					),
					'value'     => '',
					'class'     => 'small-text',
					'required'  => 'required',
				),
				'user_id'      => array(
					'id'        => $this->shortcodes_section_key . '_user_id',
					'name'      => 'user_id',
					'type'      => 'number',
					'label'     => esc_html__( 'User ID', 'learndash' ),
					'help_text' => esc_html__( 'Enter specific User ID. Leave blank for current User.', 'learndash' ),
					'value'     => '',
					'class'     => 'small-text',
				),
				'autop'        => array(
					'id'        => $this->shortcodes_section_key . 'autop',
					'name'      => 'autop',
					'type'      => 'select',
					'label'     => esc_html__( 'Auto Paragraph', 'learndash' ),
					'help_text' => esc_html__( 'Format shortcode content into proper paragraphs.', 'learndash' ),
					'value'     => 'true',
					'options'   => array(
						''      => esc_html__( 'Yes (default)', 'learndash' ),
						'false' => esc_html__( 'No', 'learndash' ),
					),
				),
			);

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}

		/**
		 * Show Shortcode section footer extra
		 *
		 * @since 4.0.0
		 */
		public function show_shortcodes_section_footer_extra() {
			?>
			<script>
				jQuery( function() {
					if ( jQuery( 'form#learndash_shortcodes_form_student select#student_display_type' ).length) {
						jQuery( 'form#learndash_shortcodes_form_student select#student_display_type').on( 'change', function() {
							var selected = jQuery(this).val();
							if ( selected == 'sfwd-courses' ) {
								jQuery( 'form#learndash_shortcodes_form_student #student_group_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_student input#student_group_id').val('');
								jQuery( 'form#learndash_shortcodes_form_student input#student_group_id').attr('required', false);

								jQuery( 'form#learndash_shortcodes_form_student #student_course_id_field').slideDown();
								jQuery( 'form#learndash_shortcodes_form_student input#student_course_id').attr('required', 'required');

							} else if ( selected == 'groups' ) {
								jQuery( 'form#learndash_shortcodes_form_student #student_course_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_student input#student_course_id').val('');
								jQuery( 'form#learndash_shortcodes_form_student input#student_course_id').attr('required', false);

								jQuery( 'form#learndash_shortcodes_form_student #student_group_id_field').slideDown();
								jQuery( 'form#learndash_shortcodes_form_student input#student_group_id').attr('required', 'required');

							} else {
								jQuery( 'form#learndash_shortcodes_form_student #student_course_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_student input#student_course_id').val('');
								jQuery( 'form#learndash_shortcodes_form_student input#student_course_id').attr('required', false);

								jQuery( 'form#learndash_shortcodes_form_student #student_group_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_student input#student_group_id').val('');
								jQuery( 'form#learndash_shortcodes_form_student input#student_group_id').attr('required', false);
							}
						});
						jQuery( 'form#learndash_shortcodes_form_student select#student_display_type').change();
					}
				});
			</script>
			<?php
		}
	}
}
