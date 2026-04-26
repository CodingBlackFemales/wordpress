<?php
/**
 * Course completion awards metabox class file.
 *
 * @since 4.20.0
 *
 * @package LearnDash\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Course completion awards metabox class.
 *
 * @since 4.20.0
 */
class LearnDash_Settings_Metabox_Course_Completion_Awards extends LearnDash_Settings_Metabox {
	/**
	 * Constructor.
	 *
	 * @since 4.20.0
	 */
	public function __construct() {
		$this->settings_screen_id = 'sfwd-courses';

		$this->settings_metabox_key = 'learndash-course-completion-awards';

		$this->settings_section_label = esc_html__( 'Completion Awards', 'learndash' );

		$this->settings_section_description = sprintf(
			// Translators: placeholder: course.
			esc_html_x( 'Controls what students gain post %s completion', 'placeholder: course', 'learndash' ),
			learndash_get_custom_label_lower( 'course' )
		);

		add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );

		// Map internal settings field ID to legacy field ID.
		$this->settings_fields_map = [
			// Legacy fields.

			'certificate'   => 'certificate',
			'course_points' => 'course_points',
		];

		parent::__construct();
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
			if ( ! isset( $this->setting_option_values['certificate'] ) ) {
				$this->setting_option_values['certificate'] = '';
			}

			if ( ! isset( $this->setting_option_values['course_points'] ) ) {
				$this->setting_option_values['course_points'] = '';
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
	 * @since 4.20.0
	 *
	 * @return void
	 */
	public function load_settings_fields() {
		global $sfwd_lms;

		$select_cert_options         = array();
		$select_cert_query_data_json = '';

		if ( learndash_use_select2_lib() ) {
			$select_cert_options_default = array(
				'-1' => esc_html__( 'Search or select a certificate…', 'learndash' ),
			);

			if ( ! empty( $this->setting_option_values['certificate'] ) ) {
				$cert_post = get_post( absint( $this->setting_option_values['certificate'] ) );

				if ( $cert_post instanceof WP_Post ) {
					$select_cert_options[ $cert_post->ID ] = learndash_format_step_post_title_with_status_label( $cert_post );
				}
			}

			if ( learndash_use_select2_lib_ajax_fetch() ) {
				$select_cert_query_data_json = $this->build_settings_select2_lib_ajax_fetch_json(
					array(
						'query_args'       => array(
							'post_type' => learndash_get_post_type_slug( 'certificate' ),
						),
						'settings_element' => array(
							'settings_parent_class' => get_parent_class( __CLASS__ ),
							'settings_class'        => __CLASS__,
							'settings_field'        => 'certificate',
						),
					)
				);
			} else {
				$select_cert_options = $sfwd_lms->select_a_certificate();
			}
		} else {
			$select_cert_options_default = array(
				'' => esc_html__( 'Select Certificate', 'learndash' ),
			);
			$select_cert_options         = $sfwd_lms->select_a_certificate();

			if (
				is_array( $select_cert_options )
				&& ! empty( $select_cert_options )
			) {
				$select_cert_options = $select_cert_options_default + $select_cert_options;
			} else {
				$select_cert_options = $select_cert_options_default;
			}

			$select_cert_options_default = '';
		}

		$this->setting_option_fields = [
			'certificate'   => array(
				'name'        => 'certificate',
				'type'        => 'select',
				'label'       => sprintf(
					// Translators: placeholder: Course.
					esc_html_x( '%s Certificate', 'placeholder: Course', 'learndash' ),
					learndash_get_custom_label( 'course' )
				),
				'default'     => '',
				'value'       => $this->setting_option_values['certificate'],
				'options'     => $select_cert_options,
				'placeholder' => $select_cert_options_default,
				'attrs'       => array(
					'data-select2-query-data' => $select_cert_query_data_json,
				),
				'rest'        => array(
					'show_in_rest' => LearnDash_REST_API::enabled(),
					'rest_args'    => array(
						'schema' => array(
							'type'    => 'integer',
							'default' => 0,
						),
					),
				),
			),
			'course_points' => [
				'name'        => 'course_points',
				'label'       => sprintf(
					// Translators: placeholder: Course.
					esc_html_x( '%s Completion Points', 'placeholder: Course', 'learndash' ),
					learndash_get_custom_label( 'course' )
				),
				'type'        => 'number',
				'step'        => 'any',
				'min'         => '0',
				'value'       => $this->setting_option_values['course_points'],
				'default'     => '',
				'class'       => 'small-text',
				'input_label' => esc_html__( 'point(s)', 'learndash' ),
				'help_text'   => sprintf(
					// Translators: placeholder: course.
					esc_html_x( 'Number of points awarded for completing this %s.', 'placeholder: course.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'input_error' => esc_html__( 'Value should be zero or greater with up to 2 decimal places.', 'learndash' ),
				'attrs'       => [
					'step'        => 'any',
					'min'         => '0.00',
					'can_decimal' => 2,
					'can_empty'   => true,
				],
				'rest'        => [
					'show_in_rest' => LearnDash_REST_API::enabled(),
					'rest_args'    => [
						'schema' => [
							'field_key' => 'points_amount',
							'type'      => 'number',
							'default'   => 0.0,
						],
					],
				],
			],
		];

		/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
		$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_metabox_key );

		parent::load_settings_fields();
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
		if (
			$settings_screen_id === $this->settings_screen_id
			&& $settings_metabox_key === $this->settings_metabox_key
		) {
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
			! isset( $metaboxes['LearnDash_Settings_Metabox_Course_Completion_Awards'] )
			&& class_exists( 'LearnDash_Settings_Metabox_Course_Completion_Awards' )
		) {
			$metaboxes['LearnDash_Settings_Metabox_Course_Completion_Awards'] = LearnDash_Settings_Metabox_Course_Completion_Awards::add_metabox_instance();
		}

		return $metaboxes;
	},
	50,
	1
);
