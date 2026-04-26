<?php
/**
 * Course enrollment metabox class file.
 *
 * @since 4.20.0
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Utilities\Cast;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Course enrollment metabox class.
 *
 * @since 4.20.0
 */
class LearnDash_Settings_Metabox_Course_Enrollment extends LearnDash_Settings_Metabox {
	/**
	 * Constructor.
	 *
	 * @since 4.20.0
	 */
	public function __construct() {
		$this->settings_screen_id = 'sfwd-courses';

		$this->settings_metabox_key = 'learndash-course-enrollment';

		$this->settings_section_label = sprintf(
			// Translators: placeholder: Course.
			esc_html_x( '%s Enrollment', 'placeholder: Course', 'learndash' ),
			learndash_get_custom_label( 'course' )
		);

		$this->settings_section_description = sprintf(
			// Translators: placeholder: course.
			esc_html_x( 'Controls how students gain access to the %s', 'placeholder: course', 'learndash' ),
			learndash_get_custom_label_lower( 'course' )
		);

		add_filter( 'learndash_admin_settings_data', [ $this, 'learndash_admin_settings_data' ], 30, 1 );
		add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );

		// Map internal settings field ID to legacy field ID.
		$this->settings_fields_map = array(
			// New fields.

			'course_trial_price'                           => 'course_trial_price',

			'course_trial_duration_t1'                     => 'course_trial_duration_t1',
			'course_trial_duration_p1'                     => 'course_trial_duration_p1',

			// Legacy fields.

			'course_price_type'                            => 'course_price_type',

			'course_price_type_paynow_price'               => 'course_price',
			'course_price_type_paynow_enrollment_url'      => 'course_price_type_paynow_enrollment_url',

			'course_price_type_subscribe_billing_cycle'    => 'course_price_billing_cycle',
			'course_price_type_subscribe_billing_recurring_times' => 'course_no_of_cycles',
			'course_price_type_subscribe_price'            => 'course_price',
			'course_price_type_subscribe_enrollment_url'   => 'course_price_type_subscribe_enrollment_url',

			'course_price_billing_t3'                      => 'course_price_billing_t3',
			'course_price_billing_p3'                      => 'course_price_billing_p3',

			'course_price_type_closed_custom_button_label' => 'custom_button_label',
			'course_price_type_closed_custom_button_url'   => 'custom_button_url',
			'course_price_type_closed_price'               => 'course_price',
		);

		parent::__construct();
	}

	/**
	 * Add script data to array.
	 *
	 * @since 4.20.0
	 *
	 * @param array<string, string> $script_data Script data array to be sent out to browser.
	 *
	 * @return array<string, string> $script_data
	 */
	public function learndash_admin_settings_data( $script_data = [] ) {
		$script_data['valid_recurring_paypal_day_range']   = esc_html__( 'Valid range is 1 to 90 when the Billing Cycle is set to days.', 'learndash' );
		$script_data['valid_recurring_paypal_week_range']  = esc_html__( 'Valid range is 1 to 52 when the Billing Cycle is set to weeks.', 'learndash' );
		$script_data['valid_recurring_paypal_month_range'] = esc_html__( 'Valid range is 1 to 24 when the Billing Cycle is set to months.', 'learndash' );
		$script_data['valid_recurring_paypal_year_range']  = esc_html__( 'Valid range is 1 to 5 when the Billing Cycle is set to years.', 'learndash' );

		return $script_data;
	}

	/**
	 * Initialize the metabox settings values.
	 *
	 * @since 4.20.0
	 *
	 * @return void
	 */
	public function load_settings_values() {
		parent::load_settings_values();
		if ( true === $this->settings_values_loaded ) {
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
	 * @since 4.20.0
	 *
	 * @return void
	 */
	public function load_settings_fields() {
		$this->settings_sub_option_fields = [];

		$this->setting_option_fields = [
			'course_price_type_paynow_price'          => [
				'name'    => 'course_price_type_paynow_price',
				'label'   => sprintf(
					// Translators: placeholder: Course.
					esc_html_x( '%s Price', 'placeholder: Course', 'learndash' ),
					learndash_get_custom_label( 'course' )
				),
				'type'    => 'text',
				'class'   => '-medium',
				'value'   => $this->setting_option_values['course_price_type_paynow_price'],
				'default' => '',
				'rest'    => [
					'show_in_rest' => LearnDash_REST_API::enabled(),
					'rest_args'    => [
						'schema' => [
							'field_key'   => 'price_type_paynow_price',
							// Translators: placeholder: Course.
							'description' => sprintf( esc_html_x( 'Pay Now %s Price', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type'        => 'string',
							'default'     => '',
						],
					],
				],
			],
			'course_price_type_paynow_enrollment_url' => [
				'name'      => 'course_price_type_paynow_enrollment_url',
				'label'     => sprintf(
					// Translators: placeholder: Course.
					esc_html_x( '%s Enrollment URL', 'placeholder: Course', 'learndash' ),
					learndash_get_custom_label( 'course' )
				),
				'type'      => 'url',
				'class'     => 'full-text',
				'value'     => $this->setting_option_values['course_price_type_paynow_enrollment_url'],
				'help_text' => sprintf(
					// Translators: placeholder: course.
					esc_html_x( 'Enter the URL of the page you want to redirect your enrollees after signing up for this specific %s', 'placeholder: course', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'default'   => '',
				'rest'      => [
					'show_in_rest' => LearnDash_REST_API::enabled(),
					'rest_args'    => [
						'schema' => [
							'field_key'   => 'price_type_paynow_enrollment_url',
							// Translators: placeholder: course.
							'description' => sprintf( esc_html_x( 'Pay Now %s Enrollment URL', 'placeholder: course', 'learndash' ), learndash_get_custom_label_lower( 'course' ) ),
							'type'        => 'string',
							'default'     => '',
						],
					],
				],
			],
		];
		parent::load_settings_fields();
		$this->settings_sub_option_fields['course_price_type_paynow_fields'] = $this->setting_option_fields;

		$this->setting_option_fields = [
			'course_price_type_subscribe_price'          => [
				'name'    => 'course_price_type_subscribe_price',
				'label'   => sprintf(
					// Translators: placeholder: Course.
					esc_html_x( '%s Price', 'placeholder: Course', 'learndash' ),
					learndash_get_custom_label( 'course' )
				),
				'type'    => 'text',
				'class'   => '-medium',
				'value'   => $this->setting_option_values['course_price_type_subscribe_price'],
				'default' => '',
				'rest'    => [
					'show_in_rest' => LearnDash_REST_API::enabled(),
					'rest_args'    => [
						'schema' => [
							'field_key'   => 'price_type_subscribe_price',
							// Translators: placeholder: Course.
							'description' => sprintf( esc_html_x( 'Subscribe %s Price', 'placeholder: Course', 'learndash' ), learndash_get_custom_label( 'course' ) ),
							'type'        => 'string',
							'default'     => '',
						],
					],
				],
			],
			'course_price_type_subscribe_billing_cycle'  => [
				'name'  => 'course_price_type_subscribe_billing_cycle',
				'label' => esc_html__( 'Billing Cycle', 'learndash' ),
				'type'  => 'custom',
				'html'  => learndash_billing_cycle_setting_field_html(
					0,
					learndash_get_post_type_slug( 'course' )
				),
			],
			'course_price_type_subscribe_billing_recurring_times' => [
				'name'      => 'course_price_type_subscribe_billing_recurring_times',
				'label'     => esc_html__( 'Recurring Times', 'learndash' ),
				'type'      => 'text',
				'class'     => '-medium',
				'value'     => $this->setting_option_values['course_price_type_subscribe_billing_recurring_times'],
				'help_text' => esc_html__( 'How many times the billing cycle repeats. Leave empty for unlimited repeats.', 'learndash' ),
				'default'   => '',
			],
			'course_trial_price'                         => [
				'name'      => 'course_trial_price',
				'label'     => sprintf(
					// Translators: placeholder: Course.
					esc_html_x( '%s Trial Price', 'placeholder: Course', 'learndash' ),
					learndash_get_custom_label( 'course' )
				),
				'type'      => 'text',
				'class'     => '-medium',
				'value'     => $this->setting_option_values['course_trial_price'],
				'help_text' => sprintf(
					// Translators: placeholder: course.
					esc_html_x( 'Enter the price for the trial period for this %s', 'placeholder: course', 'learndash' ),
					learndash_get_custom_label( 'course' )
				),
				'default'   => '',
				'rest'      => [
					'show_in_rest' => LearnDash_REST_API::enabled(),
					'rest_args'    => [
						'schema' => [
							'field_key'   => 'trial_price',
							// Translators: placeholder: course.
							'description' => sprintf( esc_html_x( '%s Trial Price', 'placeholder: course', 'learndash' ), learndash_get_custom_label( 'course' ) ),
							'type'        => 'string',
							'default'     => '',
						],
					],
				],
			],
			'course_trial_duration'                      => [
				'name'      => 'course_trial_duration',
				'label'     => esc_html__( 'Trial Duration', 'learndash' ),
				'type'      => 'custom',
				'html'      => learndash_trial_duration_setting_field_html(
					0,
					learndash_get_post_type_slug( 'course' )
				),
				// Translators: placeholder: course.
				'help_text' => sprintf( esc_html_x( 'The length of the trial period, after the trial is over, the normal %s price billing goes into effect.', 'placeholder: course', 'learndash' ), learndash_get_custom_label_lower( 'course' ) ),
				'default'   => '',
			],
			'course_price_type_subscribe_enrollment_url' => [
				'name'      => 'course_price_type_subscribe_enrollment_url',
				'label'     => sprintf(
					// Translators: placeholder: Course.
					esc_html_x( '%s Enrollment URL', 'placeholder: Course', 'learndash' ),
					learndash_get_custom_label( 'course' )
				),
				'type'      => 'url',
				'class'     => 'full-text',
				'value'     => $this->setting_option_values['course_price_type_subscribe_enrollment_url'],
				'help_text' => sprintf(
					// Translators: placeholder: course.
					esc_html_x( 'Enter the URL of the page you want to redirect your enrollees after signing up for this specific %s', 'placeholder: course', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'default'   => '',
				'rest'      => [
					'show_in_rest' => LearnDash_REST_API::enabled(),
					'rest_args'    => [
						'schema' => [
							'field_key'   => 'price_type_subscribe_enrollment_url',
							// Translators: placeholder: course.
							'description' => sprintf( esc_html_x( 'Subscribe %s Enrollment URL', 'placeholder: course', 'learndash' ), learndash_get_custom_label_lower( 'course' ) ),
							'type'        => 'string',
							'default'     => '',
						],
					],
				],
			],
		];
		parent::load_settings_fields();
		$this->settings_sub_option_fields['course_price_type_subscribe_fields'] = $this->setting_option_fields;

		$this->setting_option_fields = [
			'course_price_type_closed_price'             => [
				'name'    => 'course_price_type_closed_price',
				'label'   => sprintf(
					// Translators: placeholder: Course.
					esc_html_x( '%s Price', 'placeholder: Course', 'learndash' ),
					learndash_get_custom_label( 'course' )
				),
				'type'    => 'text',
				'class'   => '-medium',
				'value'   => $this->setting_option_values['course_price_type_closed_price'],
				'default' => '',
				'rest'    => [
					'show_in_rest' => LearnDash_REST_API::enabled(),
					'rest_args'    => [
						'schema' => [
							'field_key'   => 'price_type_closed_price',
							// Translators: placeholder: Course.
							'description' => sprintf( esc_html_x( 'Closed %s Price', 'placeholder: Course', 'learndash' ), learndash_get_custom_label( 'course' ) ),
							'type'        => 'string',
							'default'     => '',
						],
					],
				],
			],
			'course_price_type_closed_custom_button_url' => [
				'name'      => 'course_price_type_closed_custom_button_url',
				'label'     => esc_html__( 'Button URL', 'learndash' ),
				'type'      => 'url',
				'class'     => 'full-text',
				'value'     => $this->setting_option_values['course_price_type_closed_custom_button_url'],
				'help_text' => sprintf(
					// Translators: placeholder: "Take this Course" button label.
					esc_html_x( 'Redirect the "%s" button to a specific URL.', 'placeholder: "Take this Course" button label', 'learndash' ),
					learndash_get_custom_label( 'button_take_this_course' )
				),
				'default'   => '',
				'rest'      => [
					'show_in_rest' => LearnDash_REST_API::enabled(),
					'rest_args'    => [
						'schema' => [
							'field_key'   => 'price_type_closed_custom_button_url',
							// Translators: placeholder: Course.
							'description' => sprintf( esc_html_x( 'Closed %s Button URL', 'placeholder: Course', 'learndash' ), learndash_get_custom_label( 'course' ) ),
							'type'        => 'string',
							'default'     => '',
						],
					],
				],
			],
		];

		parent::load_settings_fields();
		$this->settings_sub_option_fields['course_price_type_closed_fields'] = $this->setting_option_fields;

		$this->setting_option_fields = [
			'course_price_type' => [
				'name'    => 'course_price_type',
				'label'   => esc_html__( 'Enrollment Mode', 'learndash' ),
				'type'    => 'radio',
				'value'   => $this->setting_option_values['course_price_type'],
				'default' => LEARNDASH_DEFAULT_COURSE_PRICE_TYPE,
				'options' => [
					'open'      => [
						'label'       => esc_html__( 'Open', 'learndash' ),
						'description' => sprintf(
							// Translators: placeholder: course.
							esc_html_x( 'The %s is not protected. Any student can access its content without the need to be logged-in or enrolled.', 'placeholder: course', 'learndash' ),
							learndash_get_custom_label_lower( 'course' )
						),
					],
					'free'      => [
						'label'       => esc_html__( 'Free', 'learndash' ),
						'description' => sprintf(
							// Translators: placeholder: course.
							esc_html_x( 'The %s is protected. Registration and enrollment are required in order to access the content.', 'placeholder: course', 'learndash' ),
							learndash_get_custom_label_lower( 'course' )
						),
					],
					'paynow'    => [
						'label'               => esc_html__( 'Buy now', 'learndash' ),
						'description'         => sprintf(
							// Translators: placeholder: course, course.
							esc_html_x( 'The %1$s is protected via the LearnDash built-in PayPal and/or Stripe. Students need to purchase the %2$s (one-time fee) in order to gain access.', 'placeholder: course, course', 'learndash' ),
							learndash_get_custom_label_lower( 'course' ),
							learndash_get_custom_label_lower( 'course' )
						),
						'inline_fields'       => [
							'course_price_type_paynow' => $this->settings_sub_option_fields['course_price_type_paynow_fields'],
						],
						'inner_section_state' => ( 'paynow' === $this->setting_option_values['course_price_type'] ) ? 'open' : 'closed',
					],
					'subscribe' => [
						'label'               => esc_html__( 'Recurring', 'learndash' ),
						'description'         => sprintf(
							// Translators: placeholder: course, course.
							esc_html_x( 'The %1$s is protected via the LearnDash built-in PayPal and/or Stripe. Students need to purchase the %2$s (recurring fee) in order to gain access.', 'placeholder: course, course', 'learndash' ),
							learndash_get_custom_label_lower( 'course' ),
							learndash_get_custom_label_lower( 'course' )
						),
						'inline_fields'       => [
							'course_price_type_subscribe' => $this->settings_sub_option_fields['course_price_type_subscribe_fields'],
						],
						'inner_section_state' => ( 'subscribe' === $this->setting_option_values['course_price_type'] ) ? 'open' : 'closed',
					],
					'closed'    => [
						'label'               => esc_html__( 'Closed', 'learndash' ),
						'description'         => sprintf(
							// Translators: placeholder: course, group.
							esc_html_x( 'The %1$s can only be accessed through admin enrollment (manual), %2$s enrollment, or integration (shopping cart or membership) enrollment. No enrollment button will be displayed, unless a URL is set (optional).', 'placeholder: course', 'learndash' ),
							learndash_get_custom_label_lower( 'course' ),
							learndash_get_custom_label_lower( 'group' )
						),
						'inline_fields'       => [
							'course_price_type_closed' => $this->settings_sub_option_fields['course_price_type_closed_fields'],
						],
						'inner_section_state' => ( 'closed' === $this->setting_option_values['course_price_type'] ) ? 'open' : 'closed',
					],
				],
				'rest'    => [
					'show_in_rest' => LearnDash_REST_API::enabled(),
					'rest_args'    => [
						'schema' => [
							'field_key'   => 'price_type',
							// Translators: placeholder: Course.
							'description' => sprintf( esc_html_x( '%s Price Type', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type'        => 'string',
							'default'     => 'open',
							'enum'        => [
								'open',
								'closed',
								'free',
								'paynow',
								'subscribe',
							],
						],
					],
				],
			],
		];

		if ( $this->_post instanceof WP_Post ) {
			$course_groups = learndash_get_course_groups( $this->_post->ID );
			if ( ( ! empty( $course_groups ) ) && ( 'closed' !== $this->setting_option_values['course_price_type'] ) ) {
				if ( 1 === count( $course_groups ) ) {
					$alert_message = sprintf(
						// Translators: placeholders, course, groups, Group, course, groups.
						esc_html_x( 'This %1$s is a part of a %2$s. %3$s settings will override %4$s settings for any student enrolled in the %5$s.', 'placeholders, course, group, Group, course, group.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'group' ),
						learndash_get_custom_label( 'group' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'group' )
					);
				} else {
					$alert_message = sprintf(
						// Translators: placeholders, course, groups, Group, course, groups.
						esc_html_x( 'This %1$s is a part of multiple %2$s. %3$s settings will override %4$s settings for any student enrolled in the %5$s.', 'placeholders, course, groups, Group, course, groups.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'groups' ),
						learndash_get_custom_label( 'group' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'groups' )
					);
				}

				if ( ! empty( $alert_message ) ) {
					$this->setting_option_fields = array_merge(
						[
							'course_price_type_group_alert' => [
								'name'       => 'course_price_type_group_alert',
								'type'       => 'html',
								'input_full' => true,
								'value'      => wpautop( $alert_message ),
								'class'      => 'ld-settings-info-banner ld-settings-info-banner-alert',
							],
						],
						$this->setting_option_fields
					);
				}
			}
		}

		/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
		$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_metabox_key );

		parent::load_settings_fields();
	}

	/**
	 * Save Metabox Settings Field Map Post Values.
	 * This function maps the external Post keys to the
	 * internal field keys.
	 *
	 * @since 4.20.0
	 *
	 * @param array<string, mixed> $post_values Array of post values.
	 *
	 * @return array<string, mixed>
	 */
	public function get_save_settings_fields_map_form_post_values( $post_values = [] ) {
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
	 * @since 4.20.0
	 *
	 * @param array<string, mixed> $settings_values Array of settings values.
	 * @param string               $settings_metabox_key Metabox key.
	 * @param string               $settings_screen_id Screen ID.
	 *
	 * @return array<string, mixed> $settings_values.
	 */
	public function filter_saved_fields( $settings_values = [], $settings_metabox_key = '', $settings_screen_id = '' ) {
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
				$settings_values['course_price_billing_p3'] = learndash_billing_cycle_field_interval_validate( $settings_values['course_price_billing_p3'], Cast::to_string( $settings_values['course_price_billing_t3'] ) );
			}

			if ( isset( $_POST['course_trial_duration_t1'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$settings_values['course_trial_duration_t1'] = strtoupper( esc_attr( $_POST['course_trial_duration_t1'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$settings_values['course_trial_duration_t1'] = learndash_billing_cycle_field_frequency_validate( $settings_values['course_trial_duration_t1'] );
			}

			if ( isset( $_POST['course_trial_duration_p1'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$settings_values['course_trial_duration_p1'] = absint( $_POST['course_trial_duration_p1'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$settings_values['course_trial_duration_p1'] = learndash_billing_cycle_field_interval_validate( $settings_values['course_trial_duration_p1'], Cast::to_string( $settings_values['course_trial_duration_t1'] ) );
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
			 * Check the URL submitted for any leading/trailing spaces and remove them
			 */
			if (
				isset( $settings_values['custom_button_url'] )
				&& ! empty( $settings_values['custom_button_url'] )
			) {
				$settings_values['custom_button_url'] = trim( urldecode( Cast::to_string( $settings_values['custom_button_url'] ) ) );
			}

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$settings_values = apply_filters( 'learndash_settings_save_values', $settings_values, $this->settings_metabox_key );
		}

		return $settings_values;
	}
}

add_filter(
	'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'course' ),
	function ( $metaboxes = [] ) {
		if (
			! isset( $metaboxes['LearnDash_Settings_Metabox_Course_Enrollment'] )
			&& class_exists( 'LearnDash_Settings_Metabox_Course_Enrollment' )
		) {
			$metaboxes['LearnDash_Settings_Metabox_Course_Enrollment'] = LearnDash_Settings_Metabox_Course_Enrollment::add_metabox_instance();
		}

		return $metaboxes;
	},
	50,
	1
);
