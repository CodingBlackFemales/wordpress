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

use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\DB\DB;

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
			$this->settings_section_label = esc_html__( 'Access Settings', 'learndash' );

			$this->settings_section_description = sprintf(
				// translators: placeholder: course.
				esc_html_x( 'Controls additional requirements and restrictions that enrollees need to meet to access the %s', 'placeholder: course', 'learndash' ),
				learndash_get_custom_label_lower( 'course' )
			);

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );
			add_filter( 'rest_prepare_sfwd-courses', [ $this, 'include_legacy_fields_in_rest_response' ], 10 );

			add_action( 'learndash_metabox_updated_field', [ $this, 'process_course_access_update' ], 10, 4 );

			// Map internal settings field ID to legacy field ID.
			$this->settings_fields_map = array(
				// New fields.
				'requirements_for_enrollment'   => 'requirements_for_enrollment',
				'course_access_list_enabled'    => 'course_access_list_enabled',

				'course_start_date'             => 'course_start_date',
				'course_end_date'               => 'course_end_date',
				'course_seats_limit'            => 'course_seats_limit',

				// Legacy fields.

				'course_prerequisite_enabled'   => 'course_prerequisite_enabled',
				'course_prerequisite'           => 'course_prerequisite',
				'course_prerequisite_compare'   => 'course_prerequisite_compare',
				'course_points_enabled'         => 'course_points_enabled',
				'course_points_access'          => 'course_points_access',
				'expire_access'                 => 'expire_access',
				'expire_access_days'            => 'expire_access_days',
				'expire_access_delete_progress' => 'expire_access_delete_progress',
				'course_access_list'            => 'course_access_list',
			);

			parent::__construct();
		}

		/**
		 * Add script data to array.
		 *
		 * @since 3.0.0
		 * @deprecated 4.20.0
		 *
		 * @param array $script_data Script data array to be sent out to browser.
		 *
		 * @return array $script_data
		 */
		public function learndash_admin_settings_data( $script_data = array() ) {
			_deprecated_function( __METHOD__, '4.20.0' );

			$script_data['valid_recurring_paypal_day_range']   = esc_html__( 'Valid range is 1 to 90 when the Billing Cycle is set to days.', 'learndash' );
			$script_data['valid_recurring_paypal_week_range']  = esc_html__( 'Valid range is 1 to 52 when the Billing Cycle is set to weeks.', 'learndash' );
			$script_data['valid_recurring_paypal_month_range'] = esc_html__( 'Valid range is 1 to 24 when the Billing Cycle is set to months.', 'learndash' );
			$script_data['valid_recurring_paypal_year_range']  = esc_html__( 'Valid range is 1 to 5 when the Billing Cycle is set to years.', 'learndash' );

			return $script_data;
		}

		/**
		 * Filters REST response for sfwd-courses type to include legacy fields.
		 *
		 * @since 4.20.0
		 *
		 * @param WP_REST_Response $response Response object.
		 *
		 * @return WP_REST_Response
		 */
		public function include_legacy_fields_in_rest_response( $response ) {
			// Cast legacy fields REST API response value to match previous value data type. We need to do it because the field type is changed to hidden and the value is not cast automatically.

			if ( is_array( $response->data ) && isset( $response->data['prerequisite_enabled'] ) ) {
				$response->data['prerequisite_enabled'] = (bool) $response->data['prerequisite_enabled'];
			}

			if ( is_array( $response->data ) && isset( $response->data['points_enabled'] ) ) {
				$response->data['points_enabled'] = (bool) $response->data['points_enabled'];
			}

			return $response;
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 3.0.0
		 */
		public function load_settings_values() {
			parent::load_settings_values();
			if ( true === $this->settings_values_loaded ) {
				// Sets the new field value based on the legacy fields value.
				if ( empty( $this->setting_option_values['requirements_for_enrollment'] ) ) {
					if ( ! empty( $this->setting_option_values['course_prerequisite_enabled'] ) ) {
						$this->setting_option_values['requirements_for_enrollment'] = 'course_prerequisite_enabled';
					} elseif ( ! empty( $this->setting_option_values['course_points_enabled'] ) ) {
						$this->setting_option_values['requirements_for_enrollment'] = 'course_points_enabled';
					} else {
						$this->setting_option_values['requirements_for_enrollment'] = '';
					}
				}

				// Sets the legacy fields based on the new field value.
				$this->set_legacy_fields_values( $this->setting_option_values );

				if ( ! isset( $this->setting_option_values['course_prerequisite'] ) ) {
					$this->setting_option_values['course_prerequisite'] = [];
				}
				if ( ! isset( $this->setting_option_values['course_prerequisite_compare'] ) ) {
					$this->setting_option_values['course_prerequisite_compare'] = 'ANY';
				}

				if ( ! isset( $this->setting_option_values['course_points_access'] ) ) {
					$this->setting_option_values['course_points_access'] = '';
				}

				if ( ! isset( $this->setting_option_values['expire_access'] ) ) {
					$this->setting_option_values['expire_access'] = '';
				}

				if ( ! isset( $this->setting_option_values['expire_access_days'] ) ) {
					$this->setting_option_values['expire_access_days'] = '';
				}

				if ( ! isset( $this->setting_option_values['expire_access_delete_progress'] ) ) {
					$this->setting_option_values['expire_access_delete_progress'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_access_list_enabled'] ) ) {
					$this->setting_option_values['course_access_list_enabled'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_access_list'] ) ) {
					$this->setting_option_values['course_access_list'] = '';
				}
			}

			// Ensure all settings fields are present.
			foreach ( $this->settings_fields_map as $_internal => $_external ) {
				if ( ! isset( $this->setting_option_values[ $_internal ] ) ) {
					$this->setting_option_values[ $_internal ] = '';
				}
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

			$this->setting_option_fields = [
				'course_prerequisite_enabled' => [
					'name'    => 'course_prerequisite_enabled',
					'label'   => '',
					'type'    => 'hidden',
					'value'   => $this->setting_option_values['course_prerequisite_enabled'] ?? '',
					'default' => '',
					'options' => [],
				],
				'course_prerequisite_compare' => [
					'name'           => 'course_prerequisite_compare',
					'label'          => esc_html__( 'Compare Mode', 'learndash' ),
					'type'           => 'radio',
					'default'        => 'ANY',
					'value'          => $this->setting_option_values['course_prerequisite_compare'],
					'options'        => [
						'ANY' => [
							'label'       => esc_html__( 'Any Selected', 'learndash' ),
							'description' => sprintf(
								// Translators: placeholder: courses, course.
								esc_html_x( 'The student must complete any one of the selected %1$s in order to access this %2$s', 'placeholder: courses, course', 'learndash' ),
								learndash_get_custom_label_lower( 'courses' ),
								learndash_get_custom_label_lower( 'course' )
							),
						],
						'ALL' => [
							'label'       => esc_html__( 'All Selected', 'learndash' ),
							'description' => sprintf(
								// Translators: placeholder: course, course.
								esc_html_x( 'The student must complete all selected %1$s in order to access this %2$s', 'placeholder: courses, course', 'learndash' ),
								learndash_get_custom_label_lower( 'courses' ),
								learndash_get_custom_label_lower( 'course' )
							),
						],
					],
					'parent_setting' => 'course_prerequisite_enabled',
					'rest'           => [
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => [
							'schema' => [
								'field_key'   => 'prerequisite_compare',
								'description' => __( 'Prerequisite Compare Mode. "ALL" means all prerequisites must be completed. "ANY" means at least one prerequisite must be completed. Requires "requirements_for_enrollment" to be set to "course_prerequisite_enabled".', 'learndash' ),
								'default'     => 'ANY',
								'type'        => 'string',
								'enum'        => [
									'ANY',
									'ALL',
								],
							],
						],
					],
				],
				'course_prerequisite'         => [
					'name'           => 'course_prerequisite',
					'type'           => 'multiselect',
					'multiple'       => 'true',
					'default'        => [],
					'value'          => $this->setting_option_values['course_prerequisite'],
					'placeholder'    => $select_course_options_default,
					'value_type'     => 'intval',
					'label'          => sprintf(
						// Translators: placeholder: Courses.
						esc_html_x( '%s to Complete', 'placeholder: courses', 'learndash' ),
						learndash_get_custom_label( 'courses' )
					),
					'parent_setting' => 'course_prerequisite_enabled',
					'options'        => $select_course_options,
					'attrs'          => [
						'data-select2-query-data' => $select_course_prerequisite_query_data_json,
					],
					'rest'           => [
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => [
							'schema' => [
								'field_key'   => 'prerequisites',
								'description' => sprintf(
									// Translators: placeholder: course label.
									__( '%s prerequisites. Requires "requirements_for_enrollment" to be set to "course_prerequisite_enabled".', 'learndash' ),
									learndash_get_custom_label( 'course' )
								),
								'default'     => [],
								'type'        => 'array',
								'items'       => [
									'type' => 'integer',
								],
							],
						],
					],
				],
			];
			parent::load_settings_fields();
			$this->settings_sub_option_fields['course_prerequisite_enabled_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = [
				'course_points_enabled' => [
					'name'  => 'course_points_enabled',
					'label' => '',
					'type'  => 'hidden',
					'value' => $this->setting_option_values['course_points_enabled'] ?? '',
					'rest'  => [
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => [
							'schema' => [
								'field_key' => 'points_enabled',
								'type'      => 'boolean',
								'default'   => false,
							],
						],
					],
				],
				'course_points_access'  => [
					'name'           => 'course_points_access',
					'label'          => esc_html__( 'Required for Access', 'learndash' ),
					'type'           => 'number',
					'value'          => $this->setting_option_values['course_points_access'],
					'default'        => 0,
					'class'          => 'small-text',
					'input_label'    => esc_html__( 'point(s)', 'learndash' ),
					'input_error'    => esc_html__( 'Value should be zero or greater with up to 2 decimal places.', 'learndash' ),
					'parent_setting' => 'course_points_enabled',
					'attrs'          => [
						'step'        => 'any',
						'min'         => '0.00',
						'can_decimal' => 2,
						'can_empty'   => true,
					],
					'help_text'      => sprintf(
						// Translators: placeholder: course.
						esc_html_x( 'Number of points required in order to gain access to this %s.', 'placeholder: course.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'rest'           => [
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => [
							'schema' => [
								'default'     => 0.0,
								'description' => sprintf(
									// Translators: placeholder: %1$s: course label.
									esc_html_x( 'Number of %1$s points required to gain access to this %1$s.', 'placeholder: course.', 'learndash' ),
									learndash_get_custom_label_lower( 'course' )
								),
								'field_key'   => 'points_access',
								'type'        => 'number',
							],
						],
					],
				],
			];
			parent::load_settings_fields();
			$this->settings_sub_option_fields['course_points_enabled_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'requirements_for_enrollment'   => [
					'name'    => 'requirements_for_enrollment',
					'label'   => esc_html__( 'Requirements for Enrollment', 'learndash' ),
					'type'    => 'radio',
					'value'   => $this->setting_option_values['requirements_for_enrollment'] ?? '',
					'default' => '',
					'options' => [
						''                            => [
							'label'       => esc_html__( 'None', 'learndash' ),
							'description' => sprintf(
								// Translators: placeholder: course.
								esc_html_x( 'Students will have access to %s content without prerequisite restrictions.', 'placeholder: course', 'learndash' ),
								learndash_get_custom_label_lower( 'course' )
							),
						],
						'course_prerequisite_enabled' => [
							'label'               => sprintf(
								// Translators: placeholder: Courses.
								esc_html_x( 'Prerequisite %s', 'placeholder: Courses', 'learndash' ),
								learndash_get_custom_label( 'courses' )
							),
							'description'         => sprintf(
								// Translators: placeholder: Courses, course.
								esc_html_x( '%1$s that a student must complete before enrolling in this %2$s.', 'placeholder: Courses, course', 'learndash' ),
								learndash_get_custom_label( 'courses' ),
								learndash_get_custom_label_lower( 'course' )
							),
							'inline_fields'       => [
								'course_prerequisite_enabled' => $this->settings_sub_option_fields['course_prerequisite_enabled_fields'],
							],
							'inner_section_state' => ( 'course_prerequisite_enabled' === $this->setting_option_values['requirements_for_enrollment'] ) ? 'open' : 'closed',
						],
						'course_points_enabled'       => [
							'label'               => sprintf(
								// Translators: placeholder: Course.
								esc_html_x( '%s Points', 'placeholder: Course', 'learndash' ),
								learndash_get_custom_label( 'course' )
							),
							'description'         => sprintf(
								// Translators: placeholder: Course, course.
								esc_html_x( 'Number of %1$s Points required to gain access to this %2$s.', 'placeholder: Course, course', 'learndash' ),
								learndash_get_custom_label( 'course' ),
								learndash_get_custom_label_lower( 'course' )
							),
							'inline_fields'       => [
								'course_points_enabled' => $this->settings_sub_option_fields['course_points_enabled_fields'],
							],
							'inner_section_state' => ( 'course_points_enabled' === $this->setting_option_values['requirements_for_enrollment'] ) ? 'open' : 'closed',
						],
					],
					'rest'    => [
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => [
							'schema' => [
								'field_key'   => 'requirements_for_enrollment',
								'description' => sprintf(
									// Translators: placeholder: %1$s: courses plural label. %2$s: course singular label.
									__( 'Requirements for Enrollment. Empty string means no restrictions and students have access without prerequisite restrictions. "course_prerequisite_enabled" means prerequisite %1$s must be completed first. "course_points_enabled" means a specific number of %2$s points are required for access.', 'learndash' ),
									learndash_get_custom_label_lower( 'courses' ),
									learndash_get_custom_label_lower( 'course' )
								),
								'default'     => '',
								'type'        => 'string',
								'enum'        => [
									'',
									'course_prerequisite_enabled',
									'course_points_enabled',
								],
							],
						],
					],
				],

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
				'course_start_date'             => array(
					'name'      => 'course_start_date',
					'label'     => esc_html__( 'Start Date', 'learndash' ),
					'value'     => $this->setting_option_values['course_start_date'] ?? '',
					'type'      => 'date-entry',
					'class'     => 'learndash-datepicker-field',
					'help_text' => sprintf(
					// translators: placeholder: course, courses.
						esc_html_x( 'Set date when %1$s content will become available to enrolled students. Start Date does not affect open %2$s. When hour and minute are not defined time will default to midnight.', 'placeholder: course, courses', 'learndash' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'courses' )
					),
					'rest'      => [
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => [
							'schema' => [
								'default'     => '0',
								'description' => esc_html__( "Start Date in RFC3339 format. IMPORTANT: LLMs must ALWAYS ask for the user's timezone if not explicitly provided - do not assume UTC. If the user does not specify a timezone, stop and ask them before proceeding. The user's timezone must be included with the date for accuracy (e.g., '2025-12-01T04:30:00-05:00' for EST). Setting this to '0' will clear the date.", 'learndash' ),
								'example'     => '2025-01-15T14:30:00Z',
								'type'        => 'string',
							],
						],
					],
				),
				'course_end_date'               => array(
					'name'      => 'course_end_date',
					'label'     => esc_html__( 'End Date', 'learndash' ),
					'value'     => $this->setting_option_values['course_end_date'] ?? '',
					'type'      => 'date-entry',
					'class'     => 'learndash-datepicker-field',
					'help_text' => sprintf(
					// translators: placeholder: course, courses.
						esc_html_x( 'End date when %1$s content will become available to enrolled students. Start Date does not affect open %2$s. When hour and minute are not defined time will default to midnight.', 'placeholder: course, courses', 'learndash' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'courses' )
					),
					'rest'      => [
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => [
							'schema' => [
								'default'     => '0',
								'description' => esc_html__( "End Date in RFC3339 format. IMPORTANT: LLMs must ALWAYS ask for the user's timezone if not explicitly provided - do not assume UTC. If the user does not specify a timezone, stop and ask them before proceeding. The user's timezone must be included with the date for accuracy (e.g., '2025-12-01T04:30:00-05:00' for EST). Setting this to '0' will clear the date.", 'learndash' ),
								'example'     => '2025-01-15T14:30:00Z',
								'type'        => 'string',
							],
						],
					],
				),
				'course_seats_limit'            => array(
					'name'      => 'course_seats_limit',
					'label'     => esc_html__( 'Student Limit', 'learndash' ),
					'value'     => $this->setting_option_values['course_seats_limit'] ?? '',
					'type'      => 'number',
					'class'     => 'small-text',
					'attrs'     => array(
						'step' => 1,
						'min'  => 0,
					),
					'help_text' => sprintf(
						// translators: placeholder: course, course, courses.
						esc_html_x(
							'Limits the number of students who can take your %1$s. When the limit is reached the %2$s can no longer be purchased or enrolled in. Admins can enroll students even if the limit is reached. It does not affect open %3$s.',
							'placeholder: course, course, courses',
							'learndash'
						),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'courses' )
					),
					'rest'      => [
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => [
							'schema' => [
								'default'     => 0,
								'type'        => 'integer',
								'description' => sprintf(
									// translators: placeholder: course, course, courses.
									esc_html_x(
										'Limits the number of students who can take your %1$s. When the limit is reached the %2$s can no longer be purchased or enrolled in. Admins can enroll students even if the limit is reached. It does not affect open %3$s. 0 means no limit.',
										'placeholder: course, course, courses',
										'learndash'
									),
									learndash_get_custom_label_lower( 'course' ),
									learndash_get_custom_label_lower( 'course' ),
									learndash_get_custom_label_lower( 'courses' )
								),
							],
						],
					],
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
				/*
				 * We maintain the legacy `course_prerequisite_enabled` and `course_points_enabled` setting keys since they're
				 * widely used in core and to maintain backward compatibility.
				 */
				$this->set_legacy_fields_values( $settings_values );

				/**
				 * Check the Course Materials set course_points_enabled/course_points/course_points_access. If 'course_points_enabled' setting is
				 * 'on' then make sure 'course_points' and 'course_points_access' are not empty.
				 */
				if ( 'on' === $settings_values['course_points_enabled'] ) {
					if (
						isset( $settings_values['course_points_access'] )
						&& empty( $settings_values['course_points_access'] )
					) {
						$settings_values['course_points_enabled'] = '';
					}
				}

				/**
				 * Check the Lessons Per Page set course_prerequisite_enabled/course_prerequisite. If 'course_prerequisite_enabled' setting is
				 * 'on' then make sure 'course_prerequisite' is not empty.
				 */
				if ( 'on' === $settings_values['course_prerequisite_enabled'] ) {
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
				 * Filters LearnDash settings save values.
				 *
				 * @param array  $settings_values      An array of setting save values.
				 * @param string $settings_section_key Used within the Settings API to uniquely identify this section.
				 */
				$settings_values = apply_filters( 'learndash_settings_save_values', $settings_values, $this->settings_metabox_key );
			}

			return $settings_values;
		}

		/**
		 * Update related settings after course access updating.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_Post $post      The WP_Post object.
		 * @param string  $key       The setting key.
		 * @param mixed   $new_value The new value.
		 * @param mixed   $old_value The old value.
		 *
		 * @return void
		 */
		public function process_course_access_update( WP_Post $post, string $key, $new_value, $old_value ): void {
			// bail if it's the same value.
			if ( $old_value === $new_value ) {
				return;
			}

			switch ( $key ) {
				case 'course_start_date':
					if ( empty( $new_value ) && empty( $old_value ) ) {
						return;
					}

					$new_date = ! empty( $new_value ) ? Cast::to_int( $new_value ) : time();

					DB::table( 'usermeta' )
						->where( 'meta_key', 'course_' . $post->ID . '_access_from' )
						->update( [ 'meta_value' => $new_date ] );

					break;
			}
		}

		// End of functions.

		/**
		 * Sets the legacy fields values according to the new field value.
		 *
		 * @since 4.20.0
		 *
		 * @param array<string, mixed> $setting_values The setting values.
		 *
		 * @return void
		 */
		private function set_legacy_fields_values( array &$setting_values ): void {
			if (
				isset( $setting_values['requirements_for_enrollment'] )
				&& ( $setting_values['requirements_for_enrollment'] === 'course_prerequisite_enabled' )
			) {
				$setting_values['course_prerequisite_enabled'] = 'on';
				$setting_values['course_points_enabled']       = '';
			} elseif (
				isset( $setting_values['requirements_for_enrollment'] )
				&& ( $setting_values['requirements_for_enrollment'] === 'course_points_enabled' )
			) {
				$setting_values['course_prerequisite_enabled'] = '';
				$setting_values['course_points_enabled']       = 'on';
			} else {
				$setting_values['course_prerequisite_enabled'] = '';
				$setting_values['course_points_enabled']       = '';
			}
		}
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'course' ),
		function ( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Course_Access_Settings'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Course_Access_Settings' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Course_Access_Settings'] = LearnDash_Settings_Metabox_Course_Access_Settings::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}
