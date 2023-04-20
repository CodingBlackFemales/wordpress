<?php
/**
 * LearnDash Shortcode Section for Infobar [ld_infobar].
 *
 * @since 4.0.0
 * @package LearnDash\Settings\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_ld_infobar' ) ) ) {
	/**
	 * Class LearnDash Shortcode Section for Infobar [ld_infobar].
	 *
	 * @since 4.0.0
	 */
	class LearnDash_Shortcodes_Section_ld_infobar extends LearnDash_Shortcodes_Section /* phpcs:ignore PEAR.NamingConventions.ValidClassName.Invalid */ {

		/**
		 * Public constructor for class.
		 *
		 * @since 4.0.0
		 *
		 * @param array $fields_args Field Args.
		 */
		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key         = 'ld_infobar';
			$this->shortcodes_section_title       = esc_html__( 'Infobar', 'learndash' );
			$this->shortcodes_section_type        = 1;
			$this->shortcodes_section_description = esc_html__( 'Displays the infobar for the current post or of a specified post.', 'learndash' );

			add_action( 'learndash_shortcodes_section_header_before_title', array( $this, 'show_legacy_template_support_message' ), 10, 1 );

			parent::__construct();
		}

		/**
		 * Initialize the shortcode fields.
		 *
		 * @since 4.0.0
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
						// translators: placeholders: Course, Course.
						esc_html_x( 'Enter single %1$s ID. Leave blank for current %2$s.', 'placeholders: Course, Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' ),
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
					'help_text' => esc_html__( 'Enter a User ID. Leave blank for current user.', 'learndash' ),
					'value'     => '',
					'class'     => 'small-text',
				),
			);

			if ( ( isset( $this->fields_args['post_type'] ) ) && ( in_array( $this->fields_args['post_type'], learndash_get_post_types( 'course' ), true ) ) ) {
				unset( $this->shortcodes_option_fields['display_type']['required'] );
				unset( $this->shortcodes_option_fields['course_id']['required'] );
			}

			if ( ( isset( $this->fields_args['post_type'] ) ) && ( learndash_get_post_type_slug( 'group' ) === $this->fields_args['post_type'] ) ) {
				unset( $this->shortcodes_option_fields['display_type']['required'] );
				unset( $this->shortcodes_option_fields['group_id']['required'] );
			}

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}

		/**
		 * Show "Legacy" template not supported message.
		 *
		 * @since 4.0.0
		 * @param string $shortcodes_section_key Shortcodes section key.
		 */
		public function show_legacy_template_support_message( $shortcodes_section_key ) {
			if ( $this->shortcodes_section_key === $shortcodes_section_key ) {
				$message = learndash_get_legacy_not_supported_message();
				if ( ! empty( $message ) ) {
					?><p class="learndash-block-error-message"><?php echo wp_kses_post( $message ); ?></p>
					<?php
				}
			}
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
					if ( jQuery( 'form#learndash_shortcodes_form_ld_infobar select#ld_infobar_display_type' ).length) {
						jQuery( 'form#learndash_shortcodes_form_ld_infobar select#ld_infobar_display_type').on( 'change', function() {
							var selected = jQuery(this).val();

							if ( selected == 'sfwd-courses' ) {
								jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_group_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_infobar input#ld_infobar_group_id').val('');
								if ( jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_group_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_ld_infobar input#ld_infobar_group_id').attr('required', false);
								}

								jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_course_id_field').slideDown();
								if ( jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_course_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_ld_infobar input#ld_infobar_course_id').attr('required', 'required');
								}
								jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_post_id_field').slideDown();
								jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_user_id_field').slideDown();
							} else if ( selected == 'groups' ) {
								jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_course_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_infobar input#ld_infobar_course_id').val('');
								if ( jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_course_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_ld_infobar input#ld_infobar_course_id').attr('required', false);
								}
								jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_post_id_field').hide();

								jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_group_id_field').slideDown();
								if ( jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_group_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_ld_infobar input#ld_infobar_group_id').attr('required', 'required');
								}

								jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_user_id_field').slideDown();
							} else {
								jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_course_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_infobar input#ld_infobar_course_id').val('');
								if ( jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_course_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_ld_infobar input#ld_infobar_course_id').attr('required', false);
								}

								jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_group_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_infobar input#ld_infobar_group_id').val('');
								if ( jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_group_id_field').hasClass('learndash-settings-input-required') ) {
									jQuery( 'form#learndash_shortcodes_form_ld_infobar input#ld_infobar_group_id').attr('required', false);
								}

								jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_post_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_infobar #ld_infobar_user_id_field').hide();
							}
						});
						jQuery( 'form#learndash_shortcodes_form_ld_infobar select#ld_infobar_display_type').change();
					}
				});
			</script>
			<?php
		}

	}
}
