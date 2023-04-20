<?php
/**
 * LearnDash Shortcode Section for Course Content [course_content].
 *
 * @since 2.4.0
 * @package LearnDash\Settings\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_course_content' ) ) ) {
	/**
	 * Class LearnDash Shortcode Section for Course Content [course_content]
	 *
	 * @since 2.4.0
	 */
	class LearnDash_Shortcodes_Section_course_content extends LearnDash_Shortcodes_Section  /* phpcs:ignore PEAR.NamingConventions.ValidClassName.Invalid */ {

		/**
		 * Public constructor for class.
		 *
		 * @since 2.4.0
		 *
		 * @param array $fields_args Field Args.
		 */
		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key   = 'course_content';
			$this->shortcodes_section_title = sprintf(
				// translators: placeholder: Course.
				esc_html_x( '%s Content', 'placeholder: Course', 'learndash' ),
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
				'display_type' => array(
					'id'       => $this->shortcodes_section_key . '_display_type',
					'name'     => 'display_type',
					'type'     => 'select',
					'label'    => esc_html__( 'Display Type', 'learndash' ),
					'value'    => '',
					'options'  => array(
						'' => esc_html__( 'Select a Display Type', 'learndash' ),
						learndash_get_post_type_slug( 'course' ) => learndash_get_custom_label( 'course' ),
						learndash_get_post_type_slug( 'group' ) => learndash_get_custom_label( 'group' ),
					),
					'attrs'    => array(
						'data-shortcode-exclude' => '1',
					),
					'required' => 'required',
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
						// translators: placeholder: Course.
						esc_html_x( 'Enter single %s ID', 'placeholders: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'     => '',
					'class'     => 'small-text',
					'required'  => 'required',
				),
				'post_id'      => array(
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
						// translators: placeholder: Group.
						esc_html_x( 'Enter single %s ID', 'placeholders: Group', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'group' )
					),
					'value'     => '',
					'class'     => 'small-text',
					'required'  => 'required',
				),
				'num'          => array(
					'id'        => $this->shortcodes_section_key . '_num',
					'name'      => 'num',
					'type'      => 'number',
					'label'     => esc_html__( 'Items Per Page', 'learndash' ),
					'help_text' => esc_html__( 'Leave empty for default or 0 to show all items.', 'learndash' ),
					'value'     => '',
					'class'     => 'small-text',
					'attrs'     => array(
						'min'  => 0,
						'step' => 1,
					),
				),

			);

			if ( ( isset( $this->fields_args['post_type'] ) ) && ( in_array( $this->fields_args['post_type'], learndash_get_post_types( 'course' ), true ) ) ) {
				unset( $this->shortcodes_option_fields['display_type']['required'] );
				unset( $this->shortcodes_option_fields['course_id']['required'] );
			} elseif ( ( isset( $this->fields_args['post_type'] ) ) && ( learndash_get_post_type_slug( 'group' ) === $this->fields_args['post_type'] ) ) {
				unset( $this->shortcodes_option_fields['display_type']['required'] );
				unset( $this->shortcodes_option_fields['group_id']['required'] );
			}

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
					if ( jQuery( 'form#learndash_shortcodes_form_course_content select#course_content_display_type' ).length) {
						jQuery( 'form#learndash_shortcodes_form_course_content select#course_content_display_type').on( 'change', function() {
							var selected = jQuery(this).val();

							if ( selected == 'sfwd-courses' ) {
								jQuery( 'form#learndash_shortcodes_form_course_content #course_content_group_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_course_content input#course_content_group_id').val('');
								if ( jQuery( 'form#learndash_shortcodes_form_course_content #course_content_group_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_course_content input#course_content_group_id').attr('required', false);
								}

								jQuery( 'form#learndash_shortcodes_form_course_content #course_content_course_id_field').slideDown();
								if ( jQuery( 'form#learndash_shortcodes_form_course_content #course_content_course_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_course_content input#course_content_course_id').attr('required', 'required');
								}

								jQuery( 'form#learndash_shortcodes_form_course_content #course_content_post_id_field').slideDown();

								jQuery( 'form#learndash_shortcodes_form_course_content #course_content_num_field').slideDown();
							} else if ( selected == 'groups' ) {
								jQuery( 'form#learndash_shortcodes_form_course_content #course_content_course_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_course_content input#course_content_course_id').val('');
								if ( jQuery( 'form#learndash_shortcodes_form_course_content #course_content_course_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_course_content input#course_content_course_id').attr('required', false);
								}

								jQuery( 'form#learndash_shortcodes_form_course_content #course_content_post_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_course_content input#course_content_post_id').val('');

								jQuery( 'form#learndash_shortcodes_form_course_content #course_content_group_id_field').slideDown();
								if ( jQuery( 'form#learndash_shortcodes_form_course_content #course_content_group_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_course_content input#course_content_group_id').attr('required', 'required');
								}

								jQuery( 'form#learndash_shortcodes_form_course_content #course_content_num_field').slideDown();
							} else {
								jQuery( 'form#learndash_shortcodes_form_course_content #course_content_course_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_course_content input#course_content_course_id').val('');
								if ( jQuery( 'form#learndash_shortcodes_form_course_content #course_content_course_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_course_content input#course_content_course_id').attr('required', false);
								}

								jQuery( 'form#learndash_shortcodes_form_course_content #course_content_post_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_course_content input#course_content_post_id').val('');

								jQuery( 'form#learndash_shortcodes_form_course_content #course_content_group_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_course_content input#course_content_group_id').val('');
								if ( jQuery( 'form#learndash_shortcodes_form_course_content #course_content_group_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_course_content input#course_content_group_id').attr('required', false);
								}

								jQuery( 'form#learndash_shortcodes_form_course_content #course_content_num_field').hide();
								jQuery( 'form#learndash_shortcodes_form_course_content input#course_content_num').val('');
							}
						});
						jQuery( 'form#learndash_shortcodes_form_course_content select#course_content_display_type').change();
					}
				});
			</script>
			<?php
		}
	}
}
