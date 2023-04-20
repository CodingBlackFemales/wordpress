<?php
/**
 * LearnDash Settings Metabox for Course Access Settings.
 *
 * @since 3.0.0
 * @package LearnDash\Settings\Metaboxes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Course_Access_Settings' ) ) ) {
	/**
	 * Class LearnDash Settings Metabox for Course Access Settings.
	 *
	 * @since 3.0.0
	 */
	class LearnDash_Settings_Metabox_Course_Access_Settings extends LearnDash_Settings_Metabox {

		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-courses';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-course-access-settings';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Course.
				esc_html_x( '%s Access Settings', 'placeholder: Course', 'learndash' ),
				learndash_get_custom_label( 'course' )
			);

			$this->settings_section_description = sprintf(
				// translators: placeholder: course.
				esc_html_x( 'Controls how users will gain access to the %s', 'placeholder: course', 'learndash' ),
				learndash_get_custom_label_lower( 'course' )
			);

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );
			add_filter( 'learndash_admin_settings_data', array( $this, 'learndash_admin_settings_data' ), 30, 1 );

			// Map internal settings field ID to legacy field ID.
			$this->settings_fields_map = array(
				// New fields.
				'course_access_list_enabled'              => 'course_access_list_enabled',

				'course_trial_price'                      => 'course_trial_price',

				'course_trial_duration_t1'                => 'course_trial_duration_t1',
				'course_trial_duration_p1'                => 'course_trial_duration_p1',

				// Legacy fields.
				'course_price_type'                       => 'course_price_type',

				'course_price_type_paynow_price'          => 'course_price',
				'course_price_type_paynow_enrollment_url' => 'course_price_type_paynow_enrollment_url',

				'course_price_type_subscribe_billing_cycle' => 'course_price_billing_cycle',
				'course_price_type_subscribe_billing_recurring_times' => 'course_no_of_cycles',
				'course_price_type_subscribe_price'       => 'course_price',
				'course_price_type_subscribe_enrollment_url' => 'course_price_type_subscribe_enrollment_url',

				'course_price_billing_t3'                 => 'course_price_billing_t3',
				'course_price_billing_p3'                 => 'course_price_billing_p3',

				'course_price_type_closed_custom_button_label' => 'custom_button_label',
				'course_price_type_closed_custom_button_url' => 'custom_button_url',
				'course_price_type_closed_price'          => 'course_price',

				'course_prerequisite_enabled'             => 'course_prerequisite_enabled',
				'course_prerequisite'                     => 'course_prerequisite',
				'course_prerequisite_compare'             => 'course_prerequisite_compare',
				'course_points_enabled'                   => 'course_points_enabled',
				'course_points'                           => 'course_points',
				'course_points_access'                    => 'course_points_access',
				'expire_access'                           => 'expire_access',
				'expire_access_days'                      => 'expire_access_days',
				'expire_access_delete_progress'           => 'expire_access_delete_progress',
				'course_access_list'                      => 'course_access_list',
			);

			parent::__construct();
		}

		/**
		 * Add script data to array.
		 *
		 * @since 3.0.0
		 *
		 * @param array $script_data Script data array to be sent out to browser.
		 *
		 * @return array $script_data
		 */
		public function learndash_admin_settings_data( $script_data = array() ) {

			$script_data['valid_recurring_paypal_day_range']   = esc_html__( 'Valid range is 1 to 90 when the Billing Cycle is set to days.', 'learndash' );
			$script_data['valid_recurring_paypal_week_range']  = esc_html__( 'Valid range is 1 to 52 when the Billing Cycle is set to weeks.', 'learndash' );
			$script_data['valid_recurring_paypal_month_range'] = esc_html__( 'Valid range is 1 to 24 when the Billing Cycle is set to months.', 'learndash' );
			$script_data['valid_recurring_paypal_year_range']  = esc_html__( 'Valid range is 1 to 5 when the Billing Cycle is set to years.', 'learndash' );

			return $script_data;
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 3.0.0
		 */
		public function load_settings_values() {
			parent::load_settings_values();
			if ( true === $this->settings_values_loaded ) {

				if ( ! isset( $this->setting_option_values['course_points_enabled'] ) ) {
					$this->setting_option_values['course_points_enabled'] = '';
				}
				if ( ! isset( $this->setting_option_values['expire_access'] ) ) {
					$this->setting_option_values['expire_access'] = '';
				}
				if ( ! isset( $this->setting_option_values['expire_access_delete_progress'] ) ) {
					$this->setting_option_values['expire_access_delete_progress'] = '';
				}
				if ( ! isset( $this->setting_option_values['course_access_list_enabled'] ) ) {
					$this->setting_option_values['course_access_list_enabled'] = '';
				}

				if ( ( ! isset( $this->setting_option_values['course_price_type'] ) ) || ( empty( $this->setting_option_values['course_price_type'] ) ) ) {
					$this->setting_option_values['course_price_type'] = LEARNDASH_DEFAULT_COURSE_PRICE_TYPE;
				}

				if ( ! isset( $this->setting_option_values['course_price_type_paynow_price'] ) ) {
					$this->setting_option_values['course_price_type_paynow_price'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_price_type_paynow_enrollment_url'] ) ) {
					$this->setting_option_values['course_price_type_paynow_enrollment_url'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_price_type_subscribe_price'] ) ) {
					$this->setting_option_values['course_price_type_subscribe_price'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_price_type_subscribe_billing_recurring_times'] ) ) {
					$this->setting_option_values['course_price_type_subscribe_billing_recurring_times'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_trial_price'] ) ) {
					$this->setting_option_values['course_trial_price'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_price_type_subscribe_enrollment_url'] ) ) {
					$this->setting_option_values['course_price_type_subscribe_enrollment_url'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_price_type_closed_price'] ) ) {
					$this->setting_option_values['course_price_type_closed_price'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_price_type_closed_custom_button_url'] ) ) {
					$this->setting_option_values['course_price_type_closed_custom_button_url'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_prerequisite_enabled'] ) ) {
					$this->setting_option_values['course_prerequisite_enabled'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_prerequisite'] ) ) {
					$this->setting_option_values['course_prerequisite'] = array();
				}
				if ( ! isset( $this->setting_option_values['course_prerequisite_compare'] ) ) {
					$this->setting_option_values['course_prerequisite_compare'] = 'ANY';
				}

				if ( ! isset( $this->setting_option_values['course_points_access'] ) ) {
					$this->setting_option_values['course_points_access'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_points'] ) ) {
					$this->setting_option_values['course_points'] = '';
				}

				if ( ! isset( $this->setting_option_values['expire_access_days'] ) ) {
					$this->setting_option_values['expire_access_days'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_access_list'] ) ) {
					$this->setting_option_values['course_access_list'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_price_type_closed_custom_button_label'] ) ) {
					$this->setting_option_values['course_price_type_closed_custom_button_label'] = '';
				}
			}

			// Ensure all settings fields are present.
			foreach ( $this->settings_fields_map as $_internal => $_external ) {
				if ( ! isset( $this->setting_option_values[ $_internal ] ) ) {
					$this->setting_option_values[ $_internal ] = '';
				}
			}

			// Clear out the price type fields we are not using.
			if ( 'paynow' === $this->setting_option_values['course_price_type'] ) {
				$this->setting_option_values['course_price_type_subscribe_price']          = '';
				$this->setting_option_values['course_price_type_subscribe_billing_cycle']  = '';
				$this->setting_option_values['course_price_type_subscribe_enrollment_url'] = '';

				$this->setting_option_values['course_price_type_closed_price']               = '';
				$this->setting_option_values['course_price_type_closed_custom_button_label'] = '';
				$this->setting_option_values['course_price_type_closed_custom_button_url']   = '';
				$this->setting_option_values['course_trial_price']                           = '';
			} elseif ( 'subscribe' === $this->setting_option_values['course_price_type'] ) {
				$this->setting_option_values['course_price_type_paynow_price']          = '';
				$this->setting_option_values['course_price_type_paynow_enrollment_url'] = '';

				$this->setting_option_values['course_price_type_closed_price']               = '';
				$this->setting_option_values['course_price_type_closed_custom_button_label'] = '';
				$this->setting_option_values['course_price_type_closed_custom_button_url']   = '';
			} elseif ( 'closed' === $this->setting_option_values['course_price_type'] ) {
				$this->setting_option_values['course_price_type_subscribe_price']          = '';
				$this->setting_option_values['course_price_type_subscribe_billing_cycle']  = '';
				$this->setting_option_values['course_price_type_subscribe_enrollment_url'] = '';

				$this->setting_option_values['course_price_type_paynow_price']          = '';
				$this->setting_option_values['course_price_type_paynow_enrollment_url'] = '';
			} else {
				$this->setting_option_values['course_price_type_paynow_price']          = '';
				$this->setting_option_values['course_price_type_paynow_enrollment_url'] = '';

				$this->setting_option_values['course_price_type_subscribe_price']          = '';
				$this->setting_option_values['course_price_type_subscribe_billing_cycle']  = '';
				$this->setting_option_values['course_price_type_subscribe_enrollment_url'] = '';

				$this->setting_option_values['course_price_type_closed_price']               = '';
				$this->setting_option_values['course_price_type_closed_custom_button_label'] = '';
				$this->setting_option_values['course_price_type_closed_custom_button_url']   = '';
				$this->setting_option_values['course_trial_price']                           = '';
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 3.0.0
		 */
		public function load_settings_fields() {
			global $sfwd_lms;

			$this->settings_sub_option_fields = array();

			$select_course_options                      = array();
			$select_course_prerequisite_query_data_json = '';

			if ( learndash_use_select2_lib() ) {
				$select_course_options_default = sprintf(
					// translators: placeholder: course.
					esc_html_x( 'Search or select a %s', 'placeholder: course', 'learndash' ),
					learndash_get_custom_label( 'course' )
				);

				if ( ! empty( $this->setting_option_values['course_prerequisite'] ) ) {
					$course_query_args = array(
						'post_type'   => learndash_get_post_type_slug( 'course' ),
						'post_status' => 'any',
						'numberposts' => -1,
						'orderby'     => 'title',
						'order'       => 'ASC',
						'include'     => $this->setting_option_values['course_prerequisite'],
						'exclude'     => array( get_the_ID() ),
					);

					$course_posts = get_posts( $course_query_args );
					if ( ! empty( $course_posts ) ) {
						foreach ( $course_posts as $course_post ) {
							if ( ( $course_post ) && ( is_a( $course_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'course' ) === $course_post->post_type ) ) {
								$select_course_options[ $course_post->ID ] = get_the_title( $course_post->ID );
							}
						}
					}
				}

				if ( learndash_use_select2_lib_ajax_fetch() ) {
					$select_course_prerequisite_query_data_json = $this->build_settings_select2_lib_ajax_fetch_json(
						array(
							'query_args'       => array(
								'post_type'    => learndash_get_post_type_slug( 'course' ),
								'post__not_in' => array( get_the_ID() ),
							),
							'settings_element' => array(
								'settings_parent_class' => get_parent_class( __CLASS__ ),
								'settings_class'        => __CLASS__,
								'settings_field'        => 'course_prerequisite',
							),
						)
					);
				}
			} else {
				$select_course_options_default = array(
					'' => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Select %s', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
				);
				$select_course_options         = $sfwd_lms->select_a_course();
				if ( ( is_array( $select_course_options ) ) && ( ! empty( $select_course_options ) ) ) {
					$select_course_options = $select_course_options_default + $select_course_options;
				} else {
					$select_course_options = $select_course_options_default;
				}
				$select_course_options_default = '';
			}

			$this->setting_option_fields = array(
				'course_price_type_paynow_price'          => array(
					'name'    => 'course_price_type_paynow_price',
					'label'   => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Price', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'    => 'text',
					'class'   => '-medium',
					'value'   => $this->setting_option_values['course_price_type_paynow_price'],
					'default' => '',
					'rest'    => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'price_type_paynow_price',
								// translators: placeholder: Course.
								'description' => sprintf( esc_html_x( 'Pay Now %s Price', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
								'type'        => 'string',
								'default'     => '',
							),
						),
					),
				),
				'course_price_type_paynow_enrollment_url' => array(
					'name'      => 'course_price_type_paynow_enrollment_url',
					'label'     => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Enrollment URL', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'      => 'url',
					'class'     => 'full-text',
					'value'     => $this->setting_option_values['course_price_type_paynow_enrollment_url'],
					'help_text' => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Enter the URL of the page you want to redirect your enrollees after signing up for this specific %s', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'default'   => '',
					'rest'      => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'price_type_paynow_enrollment_url',
								// translators: placeholder: course.
								'description' => sprintf( esc_html_x( 'Pay Now %s Enrollment URL', 'placeholder: course', 'learndash' ), learndash_get_custom_label_lower( 'course' ) ),
								'type'        => 'string',
								'default'     => '',
							),
						),
					),
				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['course_price_type_paynow_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'course_price_type_subscribe_price' => array(
					'name'    => 'course_price_type_subscribe_price',
					'label'   => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Price', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'    => 'text',
					'class'   => '-medium',
					'value'   => $this->setting_option_values['course_price_type_subscribe_price'],
					'default' => '',
					'rest'    => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'price_type_subscribe_price',
								// translators: placeholder: Course.
								'description' => sprintf( esc_html_x( 'Subscribe %s Price', 'placeholder: Course', 'learndash' ), learndash_get_custom_label( 'course' ) ),
								'type'        => 'string',
								'default'     => '',
							),
						),
					),
				),
				'course_price_type_subscribe_billing_cycle' => array(
					'name'  => 'course_price_type_subscribe_billing_cycle',
					'label' => esc_html__( 'Billing Cycle', 'learndash' ),
					'type'  => 'custom',
					'html'  => learndash_billing_cycle_setting_field_html(
						0,
						learndash_get_post_type_slug( 'course' )
					),
				),
				'course_price_type_subscribe_billing_recurring_times' => array(
					'name'      => 'course_price_type_subscribe_billing_recurring_times',
					'label'     => esc_html__( 'Recurring Times', 'learndash' ),
					'type'      => 'text',
					'class'     => '-medium',
					'value'     => $this->setting_option_values['course_price_type_subscribe_billing_recurring_times'],
					'help_text' => esc_html__( 'How many times the billing cycle repeats. Leave empty for unlimited repeats.', 'learndash' ),
					'default'   => '',
				),
				'course_trial_price'                => array(
					'name'      => 'course_trial_price',
					'label'     => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Trial Price', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'      => 'text',
					'class'     => '-medium',
					'value'     => $this->setting_option_values['course_trial_price'],
					'help_text' => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Enter the price for the trial period for this %s', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'default'   => '',
					'rest'      => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'trial_price',
								// translators: placeholder: course.
								'description' => sprintf( esc_html_x( '%s Trial Price', 'placeholder: course', 'learndash' ), learndash_get_custom_label( 'course' ) ),
								'type'        => 'string',
								'default'     => '',
							),
						),
					),
				),
				'course_trial_duration'             => array(
					'name'      => 'course_trial_duration',
					'label'     => esc_html__( 'Trial Duration', 'learndash' ),
					'type'      => 'custom',
					'html'      => learndash_trial_duration_setting_field_html(
						0,
						learndash_get_post_type_slug( 'course' )
					),
					// translators: placeholder: course.
					'help_text' => sprintf( esc_html_x( 'The length of the trial period, after the trial is over, the normal %s price billing goes into effect.', 'placeholder: course', 'learndash' ), learndash_get_custom_label_lower( 'course' ) ),
					'default'   => '',
				),
				'course_price_type_subscribe_enrollment_url' => array(
					'name'      => 'course_price_type_subscribe_enrollment_url',
					'label'     => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Enrollment URL', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'      => 'url',
					'class'     => 'full-text',
					'value'     => $this->setting_option_values['course_price_type_subscribe_enrollment_url'],
					'help_text' => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Enter the URL of the page you want to redirect your enrollees after signing up for this specific %s', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'default'   => '',
					'rest'      => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'price_type_subscribe_enrollment_url',
								// translators: placeholder: course.
								'description' => sprintf( esc_html_x( 'Subscribe %s Enrollment URL', 'placeholder: course', 'learndash' ), learndash_get_custom_label_lower( 'course' ) ),
								'type'        => 'string',
								'default'     => '',
							),
						),
					),
				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['course_price_type_subscribe_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'course_price_type_closed_price' => array(
					'name'    => 'course_price_type_closed_price',
					'label'   => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Price', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'    => 'text',
					'class'   => '-medium',
					'value'   => $this->setting_option_values['course_price_type_closed_price'],
					'default' => '',
					'rest'    => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'price_type_closed_price',
								// translators: placeholder: Course.
								'description' => sprintf( esc_html_x( 'Closed %s Price', 'placeholder: Course', 'learndash' ), learndash_get_custom_label( 'course' ) ),
								'type'        => 'string',
								'default'     => '',
							),
						),
					),
				),
				'course_price_type_closed_custom_button_url' => array(
					'name'      => 'course_price_type_closed_custom_button_url',
					'label'     => esc_html__( 'Button URL', 'learndash' ),
					'type'      => 'url',
					'class'     => 'full-text',
					'value'     => $this->setting_option_values['course_price_type_closed_custom_button_url'],
					'help_text' => sprintf(
						// translators: placeholder: "Take this Course" button label.
						esc_html_x( 'Redirect the "%s" button to a specific URL.', 'placeholder: "Take this Course" button label', 'learndash' ),
						learndash_get_custom_label( 'button_take_this_course' )
					),
					'default'   => '',
					'rest'      => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'price_type_closed_custom_button_url',
								// translators: placeholder: Course.
								'description' => sprintf( esc_html_x( 'Closed %s Button URL', 'placeholder: Course', 'learndash' ), learndash_get_custom_label( 'course' ) ),
								'type'        => 'string',
								'default'     => '',
							),
						),
					),
				),
			);

			parent::load_settings_fields();
			$this->settings_sub_option_fields['course_price_type_closed_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'course_price_type'             => array(
					'name'    => 'course_price_type',
					'label'   => esc_html__( 'Access Mode', 'learndash' ),
					'type'    => 'radio',
					'value'   => $this->setting_option_values['course_price_type'],
					'default' => LEARNDASH_DEFAULT_COURSE_PRICE_TYPE,
					'options' => array(
						'open'      => array(
							'label'       => esc_html__( 'Open', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: course.
								esc_html_x( 'The %s is not protected. Any user can access its content without the need to be logged-in or enrolled.', 'placeholder: course', 'learndash' ),
								learndash_get_custom_label_lower( 'course' )
							),
						),
						'free'      => array(
							'label'       => esc_html__( 'Free', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: course.
								esc_html_x( 'The %s is protected. Registration and enrollment are required in order to access the content.', 'placeholder: course', 'learndash' ),
								learndash_get_custom_label_lower( 'course' )
							),
						),
						'paynow'    => array(
							'label'               => esc_html__( 'Buy now', 'learndash' ),
							'description'         => sprintf(
								// translators: placeholder: course, course.
								esc_html_x( 'The %1$s is protected via the LearnDash built-in PayPal and/or Stripe. Users need to purchase the %2$s (one-time fee) in order to gain access.', 'placeholder: course, course', 'learndash' ),
								learndash_get_custom_label_lower( 'course' ),
								learndash_get_custom_label_lower( 'course' )
							),
							'inline_fields'       => array(
								'course_price_type_paynow' => $this->settings_sub_option_fields['course_price_type_paynow_fields'],
							),
							'inner_section_state' => ( 'paynow' === $this->setting_option_values['course_price_type'] ) ? 'open' : 'closed',
						),
						'subscribe' => array(
							'label'               => esc_html__( 'Recurring', 'learndash' ),
							'description'         => sprintf(
								// translators: placeholder: course, course.
								esc_html_x( 'The %1$s is protected via the LearnDash built-in PayPal and/or Stripe. Users need to purchase the %2$s (recurring fee) in order to gain access.', 'placeholder: course, course', 'learndash' ),
								learndash_get_custom_label_lower( 'course' ),
								learndash_get_custom_label_lower( 'course' )
							),
							'inline_fields'       => array(
								'course_price_type_subscribe' => $this->settings_sub_option_fields['course_price_type_subscribe_fields'],
							),
							'inner_section_state' => ( 'subscribe' === $this->setting_option_values['course_price_type'] ) ? 'open' : 'closed',
						),
						'closed'    => array(
							'label'               => esc_html__( 'Closed', 'learndash' ),
							'description'         => sprintf(
								// translators: placeholder: course, group.
								esc_html_x( 'The %1$s can only be accessed through admin enrollment (manual), %2$s enrollment, or integration (shopping cart or membership) enrollment. No enrollment button will be displayed, unless a URL is set (optional).', 'placeholder: course', 'learndash' ),
								learndash_get_custom_label_lower( 'course' ),
								learndash_get_custom_label_lower( 'group' )
							),
							'inline_fields'       => array(
								'course_price_type_closed' => $this->settings_sub_option_fields['course_price_type_closed_fields'],
							),
							'inner_section_state' => ( 'closed' === $this->setting_option_values['course_price_type'] ) ? 'open' : 'closed',
						),
					),
					'rest'    => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'price_type',
								// translators: placeholder: Course.
								'description' => sprintf( esc_html_x( '%s Price Type', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
								'type'        => 'string',
								'default'     => 'open',
								'enum'        => array(
									'open',
									'closed',
									'free',
									'paynow',
									'subscribe',
								),
							),
						),
					),
				),
				'course_prerequisite_enabled'   => array(
					'name'                => 'course_prerequisite_enabled',
					'label'               => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Prerequisites', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'                => 'checkbox-switch',
					'value'               => $this->setting_option_values['course_prerequisite_enabled'],
					'default'             => '',
					'options'             => array(
						'on' => '',
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['course_prerequisite_enabled'] ) ? 'open' : 'closed',
					'rest'                => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'prerequisite_enabled',
								'type'      => 'boolean',
								'default'   => false,
							),
						),
					),
				),
				'course_prerequisite_compare'   => array(
					'name'           => 'course_prerequisite_compare',
					'label'          => esc_html__( 'Compare Mode', 'learndash' ),
					'type'           => 'radio',
					'default'        => 'ANY',
					'value'          => $this->setting_option_values['course_prerequisite_compare'],
					'options'        => array(
						'ANY' => array(
							'label'       => esc_html__( 'Any Selected', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: courses, course.
								esc_html_x( 'The user must complete any one of the selected %1$s in order to access this %2$s', 'placeholder: course, course', 'learndash' ),
								learndash_get_custom_label_lower( 'courses' ),
								learndash_get_custom_label_lower( 'course' )
							),
						),
						'ALL' => array(
							'label'       => esc_html__( 'All Selected', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: course, course.
								esc_html_x( 'The user must complete all selected %1$s in order to access this %2$s', 'placeholder: course, course', 'learndash' ),
								learndash_get_custom_label_lower( 'course' ),
								learndash_get_custom_label_lower( 'course' )
							),
						),
					),
					'parent_setting' => 'course_prerequisite_enabled',
					'rest'           => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'prerequisite_compare',
								'description' => 'Prerequisite Compare Mode.',
								'default'     => 'ANY',
								'type'        => 'string',
								'enum'        => array(
									'ANY',
									'ALL',
								),
							),
						),
					),
				),
				'course_prerequisite'           => array(
					'name'           => 'course_prerequisite',
					'type'           => 'multiselect',
					'multiple'       => 'true',
					'default'        => array(),
					'value'          => $this->setting_option_values['course_prerequisite'],
					'placeholder'    => $select_course_options_default,
					'value_type'     => 'intval',
					'label'          => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( '%s to Complete', 'placeholder: courses', 'learndash' ),
						learndash_get_custom_label( 'courses' )
					),
					'parent_setting' => 'course_prerequisite_enabled',
					'options'        => $select_course_options,
					'attrs'          => array(
						'data-select2-query-data' => $select_course_prerequisite_query_data_json,
					),
					'rest'           => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'prerequisites',
								'description' => 'Prerequisites.',
								'default'     => array(),
								'type'        => 'array',
								'items'       => array(
									'type' => 'integer',
								),
							),
						),
					),
				),
				'course_points_enabled'         => array(
					'name'                => 'course_points_enabled',
					'label'               => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Points', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'                => 'checkbox-switch',
					'value'               => $this->setting_option_values['course_points_enabled'],
					'options'             => array(
						'on' => '',
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['course_points_enabled'] ) ? 'open' : 'closed',
					'rest'                => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'points_enabled',
								'type'      => 'boolean',
								'default'   => false,
							),
						),
					),
				),
				'course_points_access'          => array(
					'name'           => 'course_points_access',
					'label'          => esc_html__( 'Required for Access', 'learndash' ),
					'type'           => 'number',
					'value'          => $this->setting_option_values['course_points_access'],
					'default'        => 0,
					'class'          => 'small-text',
					'input_label'    => esc_html__( 'point(s)', 'learndash' ),
					'input_error'    => esc_html__( 'Value should be zero or greater with up to 2 decimal places.', 'learndash' ),
					'parent_setting' => 'course_points_enabled',
					'attrs'          => array(
						'step'        => 'any',
						'min'         => '0.00',
						'can_decimal' => 2,
						'can_empty'   => true,
					),
					'help_text'      => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Number of points required in order to gain access to this %s.', 'placeholder: course.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'rest'           => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'points_access',
								'type'      => 'float',
								'default'   => 0.0,
							),
						),
					),
				),
				'course_points'                 => array(
					'name'           => 'course_points',
					'label'          => esc_html__( 'Awarded on Completion', 'learndash' ),
					'type'           => 'number',
					'step'           => 'any',
					'min'            => '0',
					'value'          => $this->setting_option_values['course_points'],
					'default'        => '',
					'class'          => 'small-text',
					'input_label'    => esc_html__( 'point(s)', 'learndash' ),
					'parent_setting' => 'course_points_enabled',
					'help_text'      => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Number of points awarded for completing this %s.', 'placeholder: course.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'input_error'    => esc_html__( 'Value should be zero or greater with up to 2 decimal places.', 'learndash' ),
					'attrs'          => array(
						'step'        => 'any',
						'min'         => '0.00',
						'can_decimal' => 2,
						'can_empty'   => true,
					),
					'rest'           => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'points_amount',
								'type'      => 'float',
								'default'   => 0.0,
							),
						),
					),
				),

				'expire_access'                 => array(
					'name'                => 'expire_access',
					'label'               => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Access Expiration', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'                => 'checkbox-switch',
					'options'             => array(
						'on' => '',
					),
					'value'               => $this->setting_option_values['expire_access'],
					'child_section_state' => ( 'on' === $this->setting_option_values['expire_access'] ) ? 'open' : 'closed',
					'rest'                => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'type'    => 'boolean',
								'default' => false,
							),
						),
					),
				),
				'expire_access_days'            => array(
					'name'           => 'expire_access_days',
					'label'          => esc_html__( 'Access Period', 'learndash' ),
					'type'           => 'number',
					'class'          => 'small-text',
					'value'          => $this->setting_option_values['expire_access_days'],
					'input_label'    => esc_html__( 'days', 'learndash' ),
					'parent_setting' => 'expire_access',
					'attrs'          => array(
						'step' => 1,
						'min'  => 0,
					),
					'help_text'      => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Set the number of days a user will have access to the %s from enrollment date.', 'placeholder: course.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'rest'           => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'type' => 'integer',
							),
						),
					),
				),
				'expire_access_delete_progress' => array(
					'name'           => 'expire_access_delete_progress',
					'label'          => esc_html__( 'Data Deletion', 'learndash' ),
					'type'           => 'checkbox-switch',
					'options'        => array(
						'on' => sprintf(
							// translators: placeholder: course.
							esc_html_x( 'All user %s data will be deleted upon access expiration', 'placeholder: course.', 'learndash' ),
							learndash_get_custom_label_lower( 'course' )
						),
						''   => '',
					),
					'value'          => $this->setting_option_values['expire_access_delete_progress'],
					'parent_setting' => 'expire_access',
					'help_text'      => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Delete the user\'s %1$s and %2$s data when the %3$s access expires.', 'placeholder: course, quiz, course.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'quiz' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'rest'           => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'type'    => 'boolean',
								'default' => false,
							),
						),
					),
				),
				'course_access_list_enabled'    => array(
					'name'                => 'course_access_list_enabled',
					'label'               => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Alter %s Access List', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'                => 'checkbox-switch',
					'options'             => array(
						'on' => sprintf(
							// translators: placeholder: Course.
							esc_html_x( 'You can change the LD-%s enrollees by user ID (Proceed with caution)', 'placeholder: Course', 'learndash' ),
							learndash_get_custom_label( 'course' )
						),
						''   => '',
					),
					'value'               => $this->setting_option_values['course_access_list_enabled'],
					'default'             => '',
					'child_section_state' => ( 'on' === $this->setting_option_values['course_access_list_enabled'] ) ? 'open' : 'closed',
					'help_text'           => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Displays a list of %s enrollees by user ID. Note that not all enrollees may be reflected. We do not recommend editing this field.', 'placeholder: course.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
				),
				'course_access_list'            => array(
					'name'           => 'course_access_list',
					'type'           => 'textarea',
					'value'          => $this->setting_option_values['course_access_list'],
					'default'        => '',
					'parent_setting' => 'course_access_list_enabled',
					'placeholder'    => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Add a comma-list of user IDs to grant access to this %s', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'attrs'          => array(
						'rows' => '2',
						'cols' => '57',
					),
				),
			);

			if ( false === learndash_use_legacy_course_access_list() ) {
				unset( $this->setting_option_fields['course_access_list_enabled'] );
				unset( $this->setting_option_fields['course_access_list'] );
			}

			if ( is_a( $this->_post, 'WP_Post' ) ) {
				$course_groups = learndash_get_course_groups( $this->_post->ID, true );
				if ( ( ! empty( $course_groups ) ) && ( 'closed' !== $this->setting_option_values['course_price_type'] ) ) {
					$alert_message = '';

					if ( 1 === count( $course_groups ) ) {
						$alert_message = sprintf(
							// translators: placeholders, course, groups, Group, course, groups.
							esc_html_x( 'This %1$s is a part of a %2$s. %3$s settings will override %4$s settings for any user enrolled in the %5$s.', 'placeholders, course, group, Group, course, group.', 'learndash' ),
							learndash_get_custom_label_lower( 'course' ),
							learndash_get_custom_label_lower( 'group' ),
							learndash_get_custom_label( 'group' ),
							learndash_get_custom_label_lower( 'course' ),
							learndash_get_custom_label_lower( 'group' )
						);
					} elseif ( 1 < count( $course_groups ) ) {
						$alert_message = sprintf(
							// translators: placeholders, course, groups, Group, course, groups.
							esc_html_x( 'This %1$s is a part of multiple %2$s. %3$s settings will override %4$s settings for any user enrolled in the %5$s.', 'placeholders, course, groups, Group, course, groups.', 'learndash' ),
							learndash_get_custom_label_lower( 'course' ),
							learndash_get_custom_label_lower( 'groups' ),
							learndash_get_custom_label( 'group' ),
							learndash_get_custom_label_lower( 'course' ),
							learndash_get_custom_label_lower( 'groups' )
						);
					}

					if ( ! empty( $alert_message ) ) {
						$this->setting_option_fields = array_merge(
							array(
								'course_price_type_group_alert' => array(
									'name'       => 'course_price_type_group_alert',
									'type'       => 'html',
									'input_full' => true,
									'value'      => wpautop( $alert_message ),
									'class'      => 'ld-settings-info-banner ld-settings-info-banner-alert',
								),
							),
							$this->setting_option_fields
						);
					}
				}
			}

			/**
			 * Filters learndash setting fields.
			 *
			 * @param array  $setting_option_fields Associative array of Setting field details like name,type,label,value.
			 * @param string $settings_section_key Used within the Settings API to uniquely identify this section.
			 */
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_metabox_key );

			parent::load_settings_fields();
		}

		/**
		 * Save Metabox Settings Field Map Post Values.
		 * This function maps the external Post keys to the
		 * internal field keys.
		 *
		 * @since 3.0.0
		 *
		 * @param array $post_values Array of post values.
		 */
		public function get_save_settings_fields_map_form_post_values( $post_values = array() ) {
			$settings_fields_map = $this->settings_fields_map;
			if ( ( isset( $post_values['course_price_type'] ) ) && ( ! empty( $post_values['course_price_type'] ) ) ) {
				if ( 'paynow' === $post_values['course_price_type'] ) {
					unset( $settings_fields_map['course_price_type_subscribe_price'] );
					unset( $settings_fields_map['course_price_type_subscribe_billing_cycle'] );
					unset( $settings_fields_map['course_price_type_subscribe_billing_recurring_times'] );
					unset( $settings_fields_map['course_price_type_subscribe_enrollment_url'] );

					unset( $settings_fields_map['course_price_type_closed_price'] );
					unset( $settings_fields_map['course_price_type_closed_custom_button_label'] );
					unset( $settings_fields_map['course_price_type_closed_custom_button_url'] );
					unset( $settings_fields_map['course_trial_price'] );
					unset( $settings_fields_map['course_trial_duration'] );
				} elseif ( 'subscribe' === $post_values['course_price_type'] ) {
					unset( $settings_fields_map['course_price_type_paynow_price'] );
					unset( $settings_fields_map['course_price_type_paynow_enrollment_url'] );

					unset( $settings_fields_map['course_price_type_closed_price'] );
					unset( $settings_fields_map['course_price_type_closed_custom_button_label'] );
					unset( $settings_fields_map['course_price_type_closed_custom_button_url'] );
				} elseif ( 'closed' === $post_values['course_price_type'] ) {
					unset( $settings_fields_map['course_price_type_subscribe_price'] );
					unset( $settings_fields_map['course_price_type_subscribe_billing_cycle'] );
					unset( $settings_fields_map['course_price_type_subscribe_billing_recurring_times'] );
					unset( $settings_fields_map['course_price_type_paynow_price'] );
					unset( $settings_fields_map['course_trial_price'] );
					unset( $settings_fields_map['course_trial_duration'] );
					unset( $settings_fields_map['course_price_type_subscribe_enrollment_url'] );

					unset( $settings_fields_map['course_price_type_paynow_enrollment_url'] );
				} else {
					unset( $settings_fields_map['course_price_type_paynow_price'] );
					unset( $settings_fields_map['course_price_type_paynow_enrollment_url'] );

					unset( $settings_fields_map['course_price_type_subscribe_price'] );
					unset( $settings_fields_map['course_price_type_subscribe_billing_cycle'] );
					unset( $settings_fields_map['course_price_type_subscribe_billing_recurring_times'] );
					unset( $settings_fields_map['course_paypal_trial_price'] );
					unset( $settings_fields_map['course_price_type_subscribe_enrollment_url'] );

					unset( $settings_fields_map['course_price_type_closed_price'] );
					unset( $settings_fields_map['course_price_type_closed_custom_button_label'] );
					unset( $settings_fields_map['course_price_type_closed_custom_button_url'] );
					unset( $settings_fields_map['course_trial_price'] );
					unset( $settings_fields_map['course_trial_duration'] );
				}
			}
			return $settings_fields_map;
		}

		/**
		 * Filter settings values for metabox before save to database.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $settings_values Array of settings values.
		 * @param string $settings_metabox_key Metabox key.
		 * @param string $settings_screen_id Screen ID.
		 *
		 * @return array $settings_values.
		 */
		public function filter_saved_fields( $settings_values = array(), $settings_metabox_key = '', $settings_screen_id = '' ) {
			if ( ( $settings_screen_id === $this->settings_screen_id ) && ( $settings_metabox_key === $this->settings_metabox_key ) ) {

				if ( ! isset( $settings_values['course_price_type'] ) ) {
					$settings_values['course_price_type'] = '';
				}

				if ( isset( $settings_values['course_price_billing_t3'] ) ) {
					$settings_values['course_price_billing_t3'] = '';
				}
				if ( ! isset( $settings_values['course_price_billing_p3'] ) ) {
					$settings_values['course_price_billing_p3'] = 0;
				}
				if ( ! isset( $settings_values['course_trial_price'] ) ) {
					$settings_values['course_trial_price'] = '';
				}
				if ( ! isset( $settings_values['course_trial_duration_t1'] ) ) {
					$settings_values['course_trial_duration_t1'] = '';
				}
				if ( ! isset( $settings_values['course_trial_duration_p1'] ) ) {
					$settings_values['course_trial_duration_p1'] = '';
				}

				if ( isset( $_POST['course_price_billing_t3'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$settings_values['course_price_billing_t3'] = strtoupper( esc_attr( $_POST['course_price_billing_t3'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$settings_values['course_price_billing_t3'] = learndash_billing_cycle_field_frequency_validate( $settings_values['course_price_billing_t3'] );
				}

				if ( isset( $_POST['course_price_billing_p3'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$settings_values['course_price_billing_p3'] = absint( $_POST['course_price_billing_p3'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$settings_values['course_price_billing_p3'] = learndash_billing_cycle_field_interval_validate( $settings_values['course_price_billing_p3'], $settings_values['course_price_billing_t3'] );
				}

				if ( isset( $_POST['course_trial_duration_t1'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$settings_values['course_trial_duration_t1'] = strtoupper( esc_attr( $_POST['course_trial_duration_t1'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$settings_values['course_trial_duration_t1'] = learndash_billing_cycle_field_frequency_validate( $settings_values['course_trial_duration_t1'] );
				}

				if ( isset( $_POST['course_trial_duration_p1'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$settings_values['course_trial_duration_p1'] = absint( $_POST['course_trial_duration_p1'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$settings_values['course_trial_duration_p1'] = learndash_billing_cycle_field_interval_validate( $settings_values['course_trial_duration_p1'], $settings_values['course_trial_duration_t1'] );
				}

				if ( 'paynow' === $settings_values['course_price_type'] ) {
					$settings_values['custom_button_url']        = '';
					$settings_values['course_price_billing_p3']  = '';
					$settings_values['course_price_billing_t3']  = '';
					$settings_values['course_trial_price']       = '';
					$settings_values['course_trial_duration_t1'] = '';
					$settings_values['course_trial_duration_p1'] = '';
				} elseif ( 'subscribe' === $settings_values['course_price_type'] ) {
					$settings_values['custom_button_url'] = '';
				} elseif ( 'closed' === $settings_values['course_price_type'] ) {
					$settings_values['course_price_billing_p3']  = '';
					$settings_values['course_price_billing_t3']  = '';
					$settings_values['course_trial_price']       = '';
					$settings_values['course_trial_duration_t1'] = '';
					$settings_values['course_trial_duration_p1'] = '';
				} else {
					$settings_values['course_price']             = '';
					$settings_values['custom_button_url']        = '';
					$settings_values['course_price_billing_p3']  = '';
					$settings_values['course_price_billing_t3']  = '';
					$settings_values['course_trial_price']       = '';
					$settings_values['course_trial_duration_t1'] = '';
					$settings_values['course_trial_duration_p1'] = '';
				}

				/**
				 * Check the Course Materials set course_points_enabled/course_points/course_points_access. If 'course_points_enabled' setting is
				 * 'on' then make sure 'course_points' and 'course_points_access' are not empty.
				 */
				if ( ( isset( $settings_values['course_points_enabled'] ) ) && ( 'on' === $settings_values['course_points_enabled'] ) ) {
					if ( ( isset( $settings_values['course_points'] ) ) && ( empty( $settings_values['course_points'] ) ) && ( isset( $settings_values['course_points_access'] ) ) && ( empty( $settings_values['course_points_access'] ) ) ) {
						$settings_values['course_points_enabled'] = '';
					}
				}

				/**
				 * Check the Lessons Per Page set course_prerequisite_enabled/course_prerequisite. If 'course_prerequisite_enabled' setting is
				 * 'on' then make sure 'course_prerequisite' is not empty.
				 */
				if ( ( isset( $settings_values['course_prerequisite_enabled'] ) ) && ( 'on' === $settings_values['course_prerequisite_enabled'] ) ) {
					if ( ( isset( $settings_values['course_prerequisite'] ) ) && ( is_array( $settings_values['course_prerequisite'] ) ) && ( ! empty( $settings_values['course_prerequisite'] ) ) ) {
						$settings_values['course_prerequisite'] = array_diff( $settings_values['course_prerequisite'], array( 0 ) );
						if ( empty( $settings_values['course_prerequisite'] ) ) {
							$settings_values['course_prerequisite_enabled'] = '';
						}
					} else {
						$settings_values['course_prerequisite_enabled'] = '';
					}
				}

				/**
				 * Check the Lessons Per Page set expire_access/expire_access_days. If 'expire_access' setting is
				 * 'on' then make sure 'expire_access_days' is not empty.
				 */
				if ( ( isset( $settings_values['expire_access'] ) ) && ( 'on' === $settings_values['expire_access'] ) ) {
					if ( ( isset( $settings_values['expire_access_days'] ) ) && ( empty( $settings_values['expire_access_days'] ) ) ) {
						$settings_values['expire_access'] = '';
					}
				}

				/**
				 * Check the Lessons Per Page set expire_access/expire_access_days. If 'expire_access' setting is
				 * 'on' then make sure 'expire_access_days' is not empty.
				 */
				if ( ( isset( $settings_values['course_access_list_enabled'] ) ) && ( 'on' === $settings_values['course_access_list_enabled'] ) ) {
					if ( ( isset( $settings_values['course_access_list'] ) ) && ( empty( $settings_values['course_access_list'] ) ) ) {
						$settings_values['course_access_list_enabled'] = '';
					}
				}

				/**
				 * Check the URL submitted for any leading/trailing spaces and remove them
				 */
				if ( ( isset( $settings_values['custom_button_url'] ) ) && ! empty( $settings_values['custom_button_url'] ) ) {
					$settings_values['custom_button_url'] = trim( urldecode( $settings_values['custom_button_url'] ) );
				}

				/**
				 * Filters LearnDash settings save values.
				 *
				 * @param array  $settings_values      An array of setting save values.
				 * @param string $settings_section_key Used within the Settings API to uniquely identify this section.
				 */
				$settings_values = apply_filters( 'learndash_settings_save_values', $settings_values, $this->settings_metabox_key );
			}

			return $settings_values;
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'course' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Course_Access_Settings'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Course_Access_Settings' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Course_Access_Settings'] = LearnDash_Settings_Metabox_Course_Access_Settings::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}
