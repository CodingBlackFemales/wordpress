<?php
/**
 * LearnDash Shortcode Section for Groupinfo [groupinfo].
 *
 * @since 2.4.0
 * @package LearnDash\Settings\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_groupinfo' ) ) ) {
	/**
	 * Class LearnDash Shortcode Section for Groupinfo [groupinfo].
	 *
	 * @since 2.4.0
	 */
	class LearnDash_Shortcodes_Section_groupinfo extends LearnDash_Shortcodes_Section  /* phpcs:ignore PEAR.NamingConventions.ValidClassName.Invalid */ {

		/**
		 * Public constructor for class.
		 *
		 * @since 2.4.0
		 *
		 * @param array $fields_args Field Args.
		 */
		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key = 'groupinfo';
			// translators: placeholder: Group.
			$this->shortcodes_section_title = sprintf( esc_html_x( '%s Info', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) );
			$this->shortcodes_section_type  = 1;

			// translators: placeholder: group.
			$this->shortcodes_section_description = sprintf( wp_kses_post( _x( 'This shortcode displays %1$s related information.', 'placeholder: group', 'learndash' ) ), learndash_get_custom_label_lower( 'group' ) );

			parent::__construct();
		}

		/**
		 * Initialize the shortcode fields.
		 *
		 * @since 2.4.0
		 */
		public function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'show'   => array(
					'id'        => $this->shortcodes_section_key . '_show',
					'name'      => 'show',
					'type'      => 'select',
					'label'     => esc_html__( 'Show', 'learndash' ),
					'help_text' => esc_html__( 'This parameter determines the information to be shown by the shortcode.', 'learndash' ),
					'value'     => 'ID',
					'options'   => array(
						// translators: placeholder: Group.
						'group_title'         => sprintf( esc_html_x( '%s Title', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
						// translators: placeholder: Group.
						'group_url'           => sprintf( esc_html_x( '%s URL', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
						// translators: placeholder: Group.
						'group_price_type'    => sprintf( esc_html_x( '%s Price Type', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
						// translators: placeholder: Group.
						'group_price'         => sprintf( esc_html_x( '%s Price', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
						// translators: placeholder: Group.
						'group_users_count'   => sprintf( esc_html_x( '%s Enrolled Users Count', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
						// translators: placeholder: Group, Courses.
						'group_courses_count' => sprintf( esc_html_x( '%1$s %2$s Count', 'placeholder: Group, Courses', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ), LearnDash_Custom_Label::get_label( 'courses' ) ),

						// The following require User ID.
						// translators: placeholder: Group.
						'user_group_status'   => sprintf( esc_html_x( 'User %s Status', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),

						// translators: placeholder: Group.
						'enrolled_on'         => sprintf( esc_html_x( 'Enrolled On (date)', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
						// translators: placeholder: Group.
						'completed_on'        => sprintf( esc_html_x( '%s Completed On (date)', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
						// translators: placeholder: Group.
						'percent_completed'   => sprintf( esc_html_x( '%s Completed Percentage', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
					),
				),
				'format' => array(
					'id'          => $this->shortcodes_section_key . '_format',
					'name'        => 'format',
					'type'        => 'text',
					'label'       => esc_html__( 'Format', 'learndash' ),
					'help_text'   => wp_kses_post( __( 'This can be used to change the date format. Default: "F j, Y, g:i a" shows as <i>March 10, 2001, 5:16 pm</i>. See <a target="_blank" href="http://php.net/manual/en/function.date.php">the full list of available date formatting strings here.</a>', 'learndash' ) ),
					'value'       => '',
					'placeholder' => 'F j, Y, g:i a',
				),
			);

			$post_types   = array();
			$post_types[] = learndash_get_post_type_slug( 'group' );
			$post_types[] = learndash_get_post_type_slug( 'certificate' );
			if ( ( ! isset( $this->fields_args['typenow'] ) ) || ( ! in_array( $this->fields_args['typenow'], $post_types, true ) ) ) {
				$this->shortcodes_option_fields['group_id'] = array(
					'id'        => $this->shortcodes_section_key . '_group_id',
					'name'      => 'group_id',
					'type'      => 'number',
					'label'     => sprintf(
						// translators: placeholder: Group.
						esc_html_x( '%s ID', 'placeholder: Group', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'group' )
					),
					'help_text' => sprintf(
						// translators: placeholders: Group.
						esc_html_x( 'Enter single %s ID.', 'placeholders: Group', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'group' )
					),
					'value'     => '',
					'class'     => 'small-text',
					'required'  => 'required',
				);

				$this->shortcodes_option_fields['user_id'] = array(
					'id'        => $this->shortcodes_section_key . '_user_id',
					'name'      => 'user_id',
					'type'      => 'number',
					'label'     => esc_html__( 'User ID', 'learndash' ),
					'help_text' => esc_html__( 'Enter specific User ID. Leave blank for current User.', 'learndash' ),
					'value'     => '',
					'class'     => 'small-text',
				);
			}
			$this->shortcodes_option_fields['decimals'] = array(
				'id'        => $this->shortcodes_section_key . '_decimals',
				'name'      => 'decimals',
				'type'      => 'number',
				'label'     => esc_html__( 'Decimals', 'learndash' ),
				'help_text' => esc_html__( 'Number of decimal places to show. Default is 2.', 'learndash' ),
				'value'     => '',
				'class'     => 'small-text',
			);

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}

		/**
		 * Show Shortcode section footer extra
		 *
		 * @since 2.4.0
		 */
		public function show_shortcodes_section_footer_extra() {
			?>
			<script>
				jQuery( function() {
					if ( jQuery( 'form#learndash_shortcodes_form_groupinfo select#groupinfo_show' ).length) {
						jQuery( 'form#learndash_shortcodes_form_groupinfo select#groupinfo_show').on( 'change', function() {
							var selected = jQuery(this).val();
							if ( ['completed_on', 'enrolled_on'].includes( selected) ) {
								jQuery( 'form#learndash_shortcodes_form_groupinfo #groupinfo_format_field').slideDown();
							} else {
								jQuery( 'form#learndash_shortcodes_form_groupinfo #groupinfo_format_field').hide();
								jQuery( 'form#learndash_shortcodes_form_groupinfo #groupinfo_format_field input').val('');
							}

							if ( ['user_group_status', 'enrolled_on', 'completed_on', 'percent_completed'].includes( selected) ) {
								jQuery( 'form#learndash_shortcodes_form_groupinfo #groupinfo_user_id_field').slideDown();
							} else {
								jQuery( 'form#learndash_shortcodes_form_groupinfo #groupinfo_user_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_groupinfo #groupinfo_user_id_field input').val('');
							}

							if ( ['percent_completed'].includes( selected) ) {
								jQuery( 'form#learndash_shortcodes_form_groupinfo #groupinfo_decimals_field').slideDown();
							} else {
								jQuery( 'form#learndash_shortcodes_form_groupinfo #groupinfo_decimals_field').hide();
								jQuery( 'form#learndash_shortcodes_form_groupinfo #groupinfo_decimals_field input').val('');
							}
						});
						jQuery( 'form#learndash_shortcodes_form_groupinfo select#groupinfo_show').change();
					}
				});
			</script>
			<?php
		}
	}
}
