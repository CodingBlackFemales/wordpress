<?php
/**
 * LearnDash Shortcode Section for Certificate [ld_certificate].
 *
 * @since 2.4.0
 * @package LearnDash\Settings\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_ld_certificate' ) ) ) {
	/**
	 * Class LearnDash Shortcode Section for Certificate [ld_certificate].
	 *
	 * @since 2.4.0
	 */
	class LearnDash_Shortcodes_Section_ld_certificate extends LearnDash_Shortcodes_Section /* phpcs:ignore PEAR.NamingConventions.ValidClassName.Invalid */ {

		/**
		 * Public constructor for class.
		 *
		 * @since 2.4.0
		 *
		 * @param array $fields_args Field Args.
		 */
		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key         = 'ld_certificate';
			$this->shortcodes_section_title       = esc_html__( 'Certificate', 'learndash' );
			$this->shortcodes_section_type        = 2;
			$this->shortcodes_section_description = esc_html__( 'This shortcode shows a Certificate download link.', 'learndash' );

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
						learndash_get_post_type_slug( 'quiz' ) => learndash_get_custom_label( 'quiz' ),
					),
					'attrs'    => array(
						'data-shortcode-exclude' => '1',
					),
					'required' => 'required',
				),
				'quiz_id'      => array(
					'id'        => $this->shortcodes_section_key . '_quiz_id',
					'name'      => 'quiz_id',
					'type'      => 'number',
					'label'     => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( '%s ID', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'help_text' => sprintf(
						// translators: placeholders: Quiz, Quiz.
						esc_html_x( 'Enter single %1$s ID. Leave blank for current %2$s.', 'placeholders: Quiz, Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'value'     => '',
					'class'     => 'small-text',
					'required'  => 'required',
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
				'display_as'   => array(
					'id'      => $this->shortcodes_section_key . '_display_as',
					'name'    => 'display_as',
					'type'    => 'select',
					'label'   => esc_html__( 'Display as', 'learndash' ),
					'value'   => 'banner',
					'options' => array(
						'button' => esc_html__( 'Button', 'learndash' ),
						'banner' => sprintf(
							// translators: placeholders: Course, Group.
							esc_html_x( 'Banner (%1$s or %2$s only)', 'placeholders: Course, Group', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'course' ),
							LearnDash_Custom_Label::get_label( 'group' )
						),
					),
				),
				'label'        => array(
					'id'        => $this->shortcodes_section_key . '_label',
					'name'      => 'label',
					'type'      => 'text',
					'label'     => esc_html__( 'Label', 'learndash' ),
					'help_text' => esc_html__( 'Label for link shown to user', 'learndash' ),
					'value'     => '',
				),
				'class'        => array(
					'id'        => $this->shortcodes_section_key . '_class',
					'name'      => 'class',
					'type'      => 'text',
					'label'     => esc_html__( 'HTML Class', 'learndash' ),
					'help_text' => esc_html__( 'HTML class for link element', 'learndash' ),
					'value'     => '',
				),
				'context'      => array(
					'id'        => $this->shortcodes_section_key . '_context',
					'name'      => 'context',
					'type'      => 'text',
					'label'     => esc_html__( 'Context', 'learndash' ),
					'help_text' => esc_html__( 'User defined value to be passed into shortcode handler', 'learndash' ),
					'value'     => '',
				),
				'callback'     => array(
					'id'        => $this->shortcodes_section_key . '_callback',
					'name'      => 'callback',
					'type'      => 'text',
					'label'     => esc_html__( 'Callback', 'learndash' ),
					'help_text' => esc_html__( 'Custom callback function to be used instead of default output', 'learndash' ),
					'value'     => '',
				),
			);

			if ( ( isset( $this->fields_args['post_type'] ) ) && ( in_array( $this->fields_args['post_type'], learndash_get_post_types( 'course' ), true ) ) ) {
				unset( $this->shortcodes_option_fields['display_type']['required'] );
				unset( $this->shortcodes_option_fields['course_id']['required'] );
			} elseif ( ( isset( $this->fields_args['post_type'] ) ) && ( learndash_get_post_type_slug( 'group' ) === $this->fields_args['post_type'] ) ) {
				unset( $this->shortcodes_option_fields['display_type']['required'] );
				unset( $this->shortcodes_option_fields['group_id']['required'] );
			} elseif ( ( isset( $this->fields_args['post_type'] ) ) && ( learndash_get_post_type_slug( 'quiz' ) === $this->fields_args['post_type'] ) ) {
				unset( $this->shortcodes_option_fields['display_type']['required'] );
				unset( $this->shortcodes_option_fields['quiz_id']['required'] );
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
					if ( jQuery( 'form#learndash_shortcodes_form_ld_certificate select#ld_certificate_display_type' ).length) {
						jQuery( 'form#learndash_shortcodes_form_ld_certificate select#ld_certificate_display_type').on( 'change', function() {
							var selected = jQuery(this).val();

							if ( selected == 'sfwd-courses' ) {
								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_group_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_group_id').val('');
								if ( jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_group_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_group_id').attr('required', false);
								}

								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_quiz_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_quiz_id').val('');
								if ( jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_quiz_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_quiz_id').attr('required', false);
								}

								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_course_id_field span.learndash_required_field').show();
								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_course_id_field').slideDown();
								if ( jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_course_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_course_id').attr('required', 'required');
								}
							} else if ( selected == 'groups' ) {
								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_course_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_course_id').val('');
								if ( jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_course_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_course_id').attr('required', false);
								}

								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_quiz_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_quiz_id').val('');
								if ( jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_quiz_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_quiz_id').attr('required', false);
								}

								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_group_id_field').slideDown();
								if ( jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_group_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_group_id').attr('required', 'required');
								}
							} else if ( selected == 'sfwd-quiz' ) {
								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_group_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_group_id').val('');
								if ( jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_group_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_group_id').attr('required', false);
								}

								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_course_id_field').slideDown();
								// When the quiz cert is selected we explicitly set the course_id as not required.
								jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_course_id').attr('required', false);

								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_course_id_field span.learndash_required_field').hide();

								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_quiz_id_field').slideDown();
								if ( jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_quiz_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_quiz_id').attr('required', 'required');
								}
							} else {
								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_course_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_course_id').val('');
								if ( jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_course_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_course_id').attr('required', false);
								}

								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_group_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_group_id').val('');
								if ( jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_group_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_group_id').attr('required', false);
								}

								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_quiz_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_quiz_id').val('');
								if ( jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_quiz_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_ld_certificate input#ld_certificate_quiz_id').attr('required', false);
								}
							}
						});
						jQuery( 'form#learndash_shortcodes_form_ld_certificate select#ld_certificate_display_type').change();
					}

					if ( jQuery( 'form#learndash_shortcodes_form_ld_certificate select#ld_certificate_display_as' ).length) {
						jQuery( 'form#learndash_shortcodes_form_ld_certificate select#ld_certificate_display_as').on( 'change', function() {
							var selected = jQuery(this).val();

							if ( selected == 'banner' ) {
								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_label_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_class_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_context_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_callback_field').hide();
							} else {
								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_label_field').slideDown();
								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_class_field').slideDown();
								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_context_field').slideDown();
								jQuery( 'form#learndash_shortcodes_form_ld_certificate #ld_certificate_callback_field').slideDown();
							}
						});
						jQuery( 'form#learndash_shortcodes_form_ld_certificate select#ld_certificate_display_as').change();
					}
				});
			</script>
			<?php
		}

	}
}
