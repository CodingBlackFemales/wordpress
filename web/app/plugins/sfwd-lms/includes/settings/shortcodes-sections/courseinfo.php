<?php
/**
 * LearnDash Shortcode Section for Courseinfo [courseinfo].
 *
 * @since 2.4.0
 * @package LearnDash\Settings\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_courseinfo' ) ) ) {
	/**
	 * Class LearnDash Shortcode Section for Courseinfo [courseinfo].
	 *
	 * @since 2.4.0
	 */
	class LearnDash_Shortcodes_Section_courseinfo extends LearnDash_Shortcodes_Section /* phpcs:ignore PEAR.NamingConventions.ValidClassName.Invalid */ {

		/**
		 * Public constructor for class.
		 *
		 * @since 2.4.0
		 *
		 * @param array $fields_args Field Args.
		 */
		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key = 'courseinfo';
			// translators: placeholder: Course.
			$this->shortcodes_section_title = sprintf( esc_html_x( '%s Info', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) );
			$this->shortcodes_section_type  = 1;

			// translators: placeholder: course, quizzes, course.
			$this->shortcodes_section_description = sprintf( wp_kses_post( _x( 'This shortcode displays %1$s related information on the certificate. <strong>Unless specified otherwise, all points, scores and percentages relate to the %2$s associated with the %3$s.</strong>', 'placeholder: course, quizzes, course', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ), learndash_get_custom_label_lower( 'quizzes' ), learndash_get_custom_label_lower( 'course' ) );

			parent::__construct();
		}

		/**
		 * Initialize the shortcode fields.
		 *
		 * @since 2.4.0
		 */
		public function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'show'           => array(
					'id'        => $this->shortcodes_section_key . '_show',
					'name'      => 'show',
					'type'      => 'select',
					'label'     => esc_html__( 'Show', 'learndash' ),
					'help_text' => sprintf(
						// translators: placeholders: quizzes, course, quizzes, course.
						wp_kses_post( _x( 'This parameter determines the information to be shown by the shortcode.<br />cumulative - average for all %1$s of the %2$s.<br />aggregate - sum for all %3$s of the %4$s.', 'placeholders: quizzes, course, quizzes, course', 'learndash' ) ),
						learndash_get_custom_label_lower( 'quizzes' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'quizzes' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'value'     => 'ID',
					'options'   => array(
						// translators: placeholder: Course.
						'course_title'            => sprintf( esc_html_x( '%s Title', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
						// translators: placeholder: Course.
						'course_url'              => sprintf( esc_html_x( '%s URL', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),

						// translators: placeholder: Course.
						'course_points'           => sprintf( esc_html_x( '%s Points', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),

						// translators: placeholder: Course.
						'course_price_type'       => sprintf( esc_html_x( '%s Price Type', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),

						// translators: placeholder: Course.
						'course_price'            => sprintf( esc_html_x( '%s Price', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),

						// translators: placeholder: Course.
						'course_users_count'      => sprintf( esc_html_x( '%s Enrolled Users Count', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),

						// translators: placeholder: Course.
						'user_course_points'      => sprintf( esc_html_x( 'Total User %s Points', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),

						// translators: placeholder: Course.
						'user_course_time'        => sprintf( esc_html_x( 'Total User %s Time', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),

						// translators: placeholder: Course.
						'completed_on'            => sprintf( esc_html_x( '%s Completed On (date)', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),

						// translators: placeholder: Course.
						'enrolled_on'             => sprintf( esc_html_x( 'Enrolled On (date)', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),

						// translators: placeholder: Quizzes.
						'cumulative_score'        => sprintf( esc_html_x( 'Cumulative %s Score', 'placeholder: Quizzes', 'learndash' ), LearnDash_Custom_Label::get_label( 'quizzes' ) ),

						// translators: placeholder: Quizzes.
						'cumulative_points'       => sprintf( esc_html_x( 'Cumulative %s Points', 'placeholder: Quizzes', 'learndash' ), LearnDash_Custom_Label::get_label( 'quizzes' ) ),

						// translators: placeholder: Quizzes.
						'cumulative_total_points' => sprintf( esc_html_x( 'Possible Cumulative %s Total Points', 'placeholder: Quizzes', 'learndash' ), LearnDash_Custom_Label::get_label( 'quizzes' ) ),

						// translators: placeholder: Quizzes.
						'cumulative_percentage'   => sprintf( esc_html_x( 'Cumulative %s Percentage', 'placeholder: Quizzes', 'learndash' ), LearnDash_Custom_Label::get_label( 'quizzes' ) ),

						// translators: placeholder: Quizzes.
						'cumulative_timespent'    => sprintf( esc_html_x( 'Cumulative %s Time Spent', 'placeholder: Quizzes', 'learndash' ), LearnDash_Custom_Label::get_label( 'quizzes' ) ),

						// translators: placeholder: Quizzes.
						'aggregate_percentage'    => sprintf( esc_html_x( 'Aggregate %s Percentage', 'placeholder: Quizzes', 'learndash' ), LearnDash_Custom_Label::get_label( 'quizzes' ) ),

						// translators: placeholder: Quizzes.
						'aggregate_score'         => sprintf( esc_html_x( 'Aggregate %s Score', 'placeholder: Quizzes', 'learndash' ), LearnDash_Custom_Label::get_label( 'quizzes' ) ),

						// translators: placeholder: Quizzes.
						'aggregate_points'        => sprintf( esc_html_x( 'Aggregate %s Points', 'placeholder: Quizzes', 'learndash' ), LearnDash_Custom_Label::get_label( 'quizzes' ) ),

						// translators: placeholder: Quizzes.
						'aggregate_total_points'  => sprintf( esc_html_x( 'Possible %s Aggregate Total Points', 'placeholder: Quizzes', 'learndash' ), LearnDash_Custom_Label::get_label( 'quizzes' ) ),

						// translators: placeholder: Quizzes.
						'aggregate_timespent'     => sprintf( esc_html_x( 'Aggregate %s Time Spent', 'placeholder: Quizzes', 'learndash' ), LearnDash_Custom_Label::get_label( 'quizzes' ) ),
					),
				),
				'format'         => array(
					'id'          => $this->shortcodes_section_key . '_format',
					'name'        => 'format',
					'type'        => 'text',
					'label'       => esc_html__( 'Format', 'learndash' ),
					'help_text'   => wp_kses_post( __( 'This can be used to change the date format. Default: "F j, Y, g:i a" shows as <i>March 10, 2001, 5:16 pm</i>. See <a target="_blank" href="http://php.net/manual/en/function.date.php">the full list of available date formatting strings here.</a>', 'learndash' ) ),
					'value'       => '',
					'placeholder' => 'F j, Y, g:i a',
				),
				'seconds_format' => array(
					'id'        => $this->shortcodes_section_key . '_seconds_format',
					'name'      => 'seconds_format',
					'type'      => 'select',
					'label'     => esc_html__( 'Seconds Format', 'learndash' ),
					'help_text' => wp_kses_post( __( 'This can be used to change the format of seconds. Default: "time" shows a number of seconds as <i>XXmin YYsec</i>. ', 'learndash' ) ),
					'value'     => '',
					'options'   => array(
						''        => esc_html__( 'Time - 20min 49sec', 'learndash' ),
						'seconds' => esc_html__( 'Seconds - 1436', 'learndash' ),
					),
				),
			);

			$post_types   = learndash_get_post_types( 'course' );
			$post_types[] = learndash_get_post_type_slug( 'certificate' );
			if ( ( ! isset( $this->fields_args['typenow'] ) ) || ( ! in_array( $this->fields_args['typenow'], $post_types, true ) ) ) {
				$this->shortcodes_option_fields['course_id'] = array(
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
					if ( jQuery( 'form#learndash_shortcodes_form_courseinfo select#courseinfo_show' ).length) {
						jQuery( 'form#learndash_shortcodes_form_courseinfo select#courseinfo_show').on( 'change', function() {
							var selected = jQuery(this).val();

							if ( ['completed_on', 'enrolled_on'].includes( selected) ) {
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_format_field').slideDown();
							} else {
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_format_field').hide();
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_format_field input').val('');
							}

							if ( ['user_course_time' ].includes( selected ) ) {
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_seconds_format_field').show();
							} else {
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_seconds_format_field').hide();
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_seconds_format_field input').val('');
							}

							if ( ['course_points','user_course_points' ].includes( selected ) ) {
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_decimals_field').slideDown();
							} else {
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_decimals_field').hide();
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_decimals_field input').val('');
							}

							if ( ['user_course_points', 'user_course_time', 'completed_on', 'enrolled_on', 'cumulative_score', 'cumulative_points', 'cumulative_total_points', 'cumulative_percentage', 'cumulative_timespent', 'aggregate_percentage', 'aggregate_score', 'aggregate_points', 'aggregate_total_points', 'aggregate_timespent'].includes(selected) ) {
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_user_id_field').hide();
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_user_id_field input').val('');
							} else {
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_user_id_field').slideDown();
							}
						});
						jQuery( 'form#learndash_shortcodes_form_courseinfo select#courseinfo_show').change();
					}
				});
			</script>
			<?php
		}
	}
}
