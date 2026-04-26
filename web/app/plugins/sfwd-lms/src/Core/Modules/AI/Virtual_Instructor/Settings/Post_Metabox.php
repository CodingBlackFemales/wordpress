<?php
/**
 * LearnDash Settings Metabox for individual Virtual Instructor settings.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Virtual_Instructor\Settings;

use LDLMS_Post_Types;
use LearnDash_Custom_Label;
use LearnDash_Settings_Metabox;
use WP_Post;

/**
 * Class LearnDash Settings Metabox for individual Virtual Instructor settings.
 *
 * @since 4.13.0
 *
 * @phpstan-type MultiselectFieldData array{
 *     options: array<string|int, string>,
 *     data_json: string,
 *     default_options: array<string|int, string>
 * }
 */
class Post_Metabox extends LearnDash_Settings_Metabox {
	/**
	 * Field names that are part of associated fields.
	 *
	 * @since 4.13.0
	 *
	 * @var array<string>
	 */
	private const ASSOCIATED_FIELDS = [ 'course_ids', 'group_ids' ];

	/**
	 * Default values.
	 *
	 * @since 4.13.0
	 *
	 * @var array{
	 *     avatar_id: int,
	 *     custom_instruction: string,
	 *     course_ids: array<int>,
	 *     apply_to_all_courses: string,
	 *     group_ids: array<int>,
	 *     apply_to_all_groups: string,
	 *     override_banned_words: string,
	 *     banned_words: string,
	 *     override_error_message: string,
	 *     error_message: string
	 * }
	 */
	private $default_values;

	/**
	 * Add meta box instance.
	 *
	 * @since 4.13.0
	 *
	 * @param array<string, LearnDash_Settings_Metabox> $metaboxes Array of metaboxes.
	 *
	 * @return array<string, LearnDash_Settings_Metabox>
	 */
	public static function add_meta_box_instance( array $metaboxes = [] ) {
		$metaboxes[ __CLASS__ ] = self::add_metabox_instance();

		return $metaboxes;
	}

	/**
	 * Constructor.
	 *
	 * @since 4.13.0
	 */
	public function __construct() {
		$this->settings_screen_id = learndash_get_post_type_slug( LDLMS_Post_Types::VIRTUAL_INSTRUCTOR );

		$this->settings_metabox_key = 'learndash_virtual_instructor_settings';

		$this->settings_section_label = esc_html__( 'Settings', 'learndash' );

		$this->settings_section_description = sprintf(
			// translators: placeholder: virtual instructor.
			esc_html_x( 'Individual %s settings. These settings will take precedence over global settings.', 'placeholder: virtual instructor', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'virtual_instructor' )
		);

		add_filter(
			'learndash_metabox_save_fields_' . $this->settings_metabox_key,
			[ $this, 'filter_saved_fields' ],
			30,
			3
		);

		$this->settings_fields_map = [
			'avatar_id'              => 'avatar_id',
			'custom_instruction'     => 'custom_instruction',
			'apply_to_all_courses'   => 'apply_to_all_courses',
			'course_ids'             => 'course_ids',
			'apply_to_all_groups'    => 'apply_to_all_groups',
			'group_ids'              => 'group_ids',
			'override_banned_words'  => 'override_banned_words',
			'banned_words'           => 'banned_words',
			'override_error_message' => 'override_error_message',
			'error_message'          => 'error_message',
		];

		$this->default_values = [
			'avatar_id'              => 0,
			'custom_instruction'     => '',
			'course_ids'             => [],
			'apply_to_all_courses'   => '',
			'group_ids'              => [],
			'apply_to_all_groups'    => '',
			'override_banned_words'  => '',
			'banned_words'           => '',
			'override_error_message' => '',
			'error_message'          => '',
		];

		parent::__construct();
	}

	/**
	 * Initialize the metabox settings values.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function load_settings_values(): void {
		parent::load_settings_values();

		foreach ( self::ASSOCIATED_FIELDS as $field ) {
			if (
				isset( $this->setting_option_values[ $field ] )
				&& is_array( $this->setting_option_values[ $field ] )
			) {
				$this->setting_option_values[ $field ] = array_map(
					'intval',
					$this->setting_option_values[ $field ]
				);
			}
		}

		foreach ( $this->default_values as $option => $default_value ) {
			if ( ! isset( $this->setting_option_values[ $option ] ) ) {
				$this->setting_option_values[ $option ] = $default_value;
			}
		}

		// Ensure all settings fields are present.

		foreach ( $this->settings_fields_map as $internal => $external ) {
			if ( ! isset( $this->setting_option_values[ $internal ] ) ) {
				$this->setting_option_values[ $internal ] = '';
			}
		}
	}

	/**
	 * Initialize the metabox settings fields.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function load_settings_fields(): void {
		[
			'options'         => $select_course_options,
			'data_json'       => $select_course_query_data_json,
			'default_options' => $select_course_options_default
		] = $this->map_course_ids_field_data();

		[
			'options'         => $select_group_options,
			'data_json'       => $select_group_query_data_json,
			'default_options' => $select_group_options_default
		] = $this->map_group_ids_field_data();

		$this->setting_option_fields = [
			'avatar_id'              => [
				'name'              => 'avatar_id',
				'label'             => __( 'Avatar', 'learndash' ),
				'type'              => 'media-upload',
				'value'             => $this->setting_option_values['avatar_id'],
				'default'           => $this->setting_option_values['avatar_id'],
				'help_text'         => sprintf(
					// translators: placeholder: virtual instructor.
					esc_html_x( 'Upload an image to be used as the %s avatar.', 'placeholder: virtual instructor', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'virtual_instructor' )
				),
				'rest'              => [
					'show_in_rest' => false,
					'rest_args'    => [],
				],
				'validate_callback' => function ( $value ) {
						return intval( $value );
				},
			],
			'custom_instruction'     => [
				'name'        => 'custom_instruction',
				'label'       => __( 'Custom Instruction', 'learndash' ),
				'type'        => 'textarea',
				'value'       => $this->setting_option_values['custom_instruction'],
				'help_text'   => sprintf(
					// translators: placeholder: virtual instructor.
					esc_html_x( 'Enter a custom instruction for the %s.', 'placeholder: virtual instructor', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'virtual_instructor' )
				),
				'placeholder' => __( 'E.g. Don\'t give the students direct answer. Instead give them the clue and hints to find their own answer.', 'learndash' ),
				'rest'        => [
					'show_in_rest' => false,
					'rest_args'    => [],
				],
			],
			'apply_to_all_courses'   => [
				'name'                => 'apply_to_all_courses',
				'label'               => sprintf(
					// Translators: placeholder: courses.
					esc_html_x( 'Apply to all %s', 'placeholder: courses', 'learndash' ),
					learndash_get_custom_label_lower( 'courses' )
				),
				'type'                => 'checkbox-switch',
				'value'               => $this->setting_option_values['apply_to_all_courses'],
				'options'             => [ 'on' => '' ],
				'attrs'               => [
					'data-inverted' => true,
				],
				'child_section_state' => 'on' === $this->setting_option_values['apply_to_all_courses'] ? 'closed' : 'open',
				'rest'                => [
					'show_in_rest' => false,
					'rest_args'    => [],
				],
			],
			'course_ids'             => [
				'name'           => 'course_ids',
				'label'          => sprintf(
					// Translators: placeholder: Courses.
					esc_html_x( 'Associated %s', 'placeholder: Courses', 'learndash' ),
					learndash_get_custom_label( 'courses' )
				),
				'type'           => 'multiselect',
				'default'        => '',
				'value'          => $this->setting_option_values['course_ids'],
				'value_type'     => 'intval',
				'lazy_load'      => true,
				'options'        => $select_course_options,
				'placeholder'    => $select_course_options_default,
				'attrs'          => [
					'data-ld_selector_nonce'   => wp_create_nonce( learndash_get_post_type_slug( LDLMS_Post_Types::COURSE ) ),
					'data-ld_selector_default' => '1',
					'data-select2-query-data'  => $select_course_query_data_json,
				],
				'help_text'      => sprintf(
					// Translators: placeholder: courses, virtual instructor.
					esc_html_x( 'Select specific %1$s the %2$s will be used for.', 'placeholder: courses, virtual instructor', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'courses' ),
					LearnDash_Custom_Label::get_label( 'virtual_instructor' )
				),
				'parent_setting' => 'apply_to_all_courses',
				'rest'           => [
					'show_in_rest' => false,
					'rest_args'    => [],
				],
			],
			'apply_to_all_groups'    => [
				'name'                => 'apply_to_all_groups',
				'label'               => sprintf(
					// Translators: placeholder: groups.
					esc_html_x( 'Apply to all %s', 'placeholder: groups', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' )
				),
				'type'                => 'checkbox-switch',
				'value'               => $this->setting_option_values['apply_to_all_groups'],
				'options'             => [ 'on' => '' ],
				'attrs'               => [
					'data-inverted' => true,
				],
				'child_section_state' => 'on' === $this->setting_option_values['apply_to_all_groups'] ? 'closed' : 'open',
				'rest'                => [
					'show_in_rest' => false,
					'rest_args'    => [],
				],
			],
			'group_ids'              => [
				'name'           => 'group_ids',
				'label'          => sprintf(
					// Translators: placeholder: Groups.
					esc_html_x( 'Associated %s', 'placeholder: Groups', 'learndash' ),
					learndash_get_custom_label( 'groups' )
				),
				'type'           => 'multiselect',
				'value'          => $this->setting_option_values['group_ids'],
				'value_type'     => 'intval',
				'lazy_load'      => true,
				'options'        => $select_group_options,
				'placeholder'    => $select_group_options_default,
				'attrs'          => [
					'data-ld_selector_nonce'   => wp_create_nonce( learndash_get_post_type_slug( LDLMS_Post_Types::GROUP ) ),
					'data-ld_selector_default' => '1',
					'data-select2-query-data'  => $select_group_query_data_json,
				],
				'help_text'      => sprintf(
					// Translators: placeholder: groups, virtual instructor.
					esc_html_x( 'Select specific %1$s the %2$s will be used for.', 'placeholder: groups, virtual instructor', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'groups' ),
					LearnDash_Custom_Label::get_label( 'virtual_instructor' )
				),
				'parent_setting' => 'apply_to_all_groups',
				'rest'           => [
					'show_in_rest' => false,
					'rest_args'    => [],
				],
			],
			'override_banned_words'  => [
				'name'                => 'override_banned_words',
				'label'               => __( 'Override Banned Words', 'learndash' ),
				'type'                => 'checkbox-switch',
				'value'               => $this->setting_option_values['override_banned_words'],
				'options'             => [ 'on' => '' ],
				'attrs'               => [
					'data-inverted' => false,
				],
				'child_section_state' => 'on' === $this->setting_option_values['override_banned_words'] ? 'open' : 'closed',
				'rest'                => [
					'show_in_rest' => false,
					'rest_args'    => [],
				],
			],
			'banned_words'           => [
				'name'           => 'banned_words',
				'label'          => __( 'Banned Words', 'learndash' ),
				'type'           => 'textarea',
				'value'          => $this->setting_option_values['banned_words'],
				'help_text'      => sprintf(
					// translators: placeholder: virtual instructor.
					esc_html_x( 'Enter a comma separated list of banned words to be used in this %s.', 'placeholder: virtual instructor', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'virtual_instructor' )
				),
				'parent_setting' => 'override_banned_words',
				'rest'           => [
					'show_in_rest' => false,
					'rest_args'    => [],
				],
			],
			'override_error_message' => [
				'name'                => 'override_error_message',
				'label'               => __( 'Override Error Message', 'learndash' ),
				'type'                => 'checkbox-switch',
				'value'               => $this->setting_option_values['override_error_message'],
				'options'             => [ 'on' => '' ],
				'attrs'               => [
					'data-inverted' => false,
				],
				'child_section_state' => 'on' === $this->setting_option_values['override_error_message'] ? 'open' : 'closed',
				'rest'                => [
					'show_in_rest' => false,
					'rest_args'    => [],
				],
			],
			'error_message'          => [
				'name'           => 'error_message',
				'label'          => __( 'Error Message', 'learndash' ),
				'type'           => 'textarea',
				'value'          => $this->setting_option_values['error_message'],
				'help_text'      => sprintf(
					// translators: placeholder: virtual instructor.
					esc_html_x( 'Enter a custom message when a user uses a banned word in this %s.', 'placeholder: virtual instructor', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'virtual_instructor' )
				),
				'parent_setting' => 'override_error_message',
				'rest'           => [
					'show_in_rest' => false,
					'rest_args'    => [],
				],
			],
		];

		/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
		$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_metabox_key );

		parent::load_settings_fields();
	}

	/**
	 * Filter settings values for metabox before being saved to database.
	 *
	 * @since 4.13.0
	 *
	 * @param array<string, mixed> $settings_values      Array of settings values.
	 * @param string               $settings_metabox_key Metabox key.
	 * @param string               $settings_screen_id   Screen ID.
	 *
	 * @return array<string, mixed> Returned setting values.
	 */
	public function filter_saved_fields(
		array $settings_values = [],
		string $settings_metabox_key = '',
		string $settings_screen_id = ''
	): array {
		if (
			$settings_screen_id !== $this->settings_screen_id
			|| $settings_metabox_key !== $this->settings_metabox_key
		) {
			return $settings_values;
		}

		if ( ! $this->_post instanceof WP_Post ) {
			return $settings_values;
		}

		// Duplicate setting values in its own meta key to make it easier to work with model and database stuff.

		foreach ( $settings_values as $key => $value ) {
			if ( ! isset( $this->settings_fields_map[ $key ] ) ) {
				continue;
			}

			$internal_key = $this->settings_fields_map[ $key ];

			update_post_meta( $this->_post->ID, $internal_key, $value );
		}

		/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
		return apply_filters(
			'learndash_settings_save_values',
			$settings_values,
			$this->settings_metabox_key
		);
	}

	/**
	 * Maps data for the courses field.
	 *
	 * @since 4.13.0
	 *
	 * @return MultiselectFieldData
	 */
	protected function map_course_ids_field_data(): array {
		global $sfwd_lms;

		return $this->map_multiselect_field_data(
			'course_ids',
			LDLMS_Post_Types::COURSE,
			$sfwd_lms->select_a_course(),
			sprintf(
				// Translators: placeholder: courses.
				esc_html_x( 'Search or select %s…', 'placeholder: courses', 'learndash' ),
				learndash_get_custom_label_lower( 'courses' )
			)
		);
	}

	/**
	 * Maps data for the courses field.
	 *
	 * @since 4.13.0
	 *
	 * @return MultiselectFieldData
	 */
	protected function map_group_ids_field_data(): array {
		global $sfwd_lms;

		return $this->map_multiselect_field_data(
			'group_ids',
			LDLMS_Post_Types::GROUP,
			$sfwd_lms->select_a_group(),
			sprintf(
				// Translators: placeholder: groups.
				esc_html_x( 'Search or select %s…', 'placeholder: groups', 'learndash' ),
				learndash_get_custom_label_lower( 'groups' )
			)
		);
	}

	/**
	 * Maps data for the multiselect field.
	 *
	 * @since 4.13.0
	 *
	 * @param string             $field                    Field key.
	 * @param string             $post_type_key            Post type key: course|group.
	 * @param array<int, string> $available_select_options Available items from the database.
	 * @param string             $default_label            Default select option label.
	 *
	 * @return MultiselectFieldData
	 */
	protected function map_multiselect_field_data(
		string $field,
		string $post_type_key,
		array $available_select_options,
		string $default_label
	): array {
		$selected_options = [];

		if ( ! empty( $this->setting_option_values[ $field ] ) ) {
			foreach ( $this->setting_option_values[ $field ] as $id ) {
				$post = get_post( $id );

				if ( is_null( $post ) ) {
					continue;
				}

				$selected_options[ $post->ID ] = get_the_title( $post->ID );
			}
		}

		if ( ! learndash_use_select2_lib() ) {
			return [
				'options'         => $selected_options + $available_select_options,
				'data_json'       => '',
				'default_options' => [],
			];
		}

		$select_default_options = [
			'-1' => $default_label,
		];

		if ( learndash_use_select2_lib_ajax_fetch() ) {
			$query_data_json = $this->build_settings_select2_lib_ajax_fetch_json(
				[
					'query_args'       => [
						'post_type' => learndash_get_post_type_slug( $post_type_key ),
					],
					'settings_element' => [
						'settings_parent_class' => get_parent_class( __CLASS__ ),
						'settings_class'        => __CLASS__,
						'settings_field'        => $post_type_key,
					],
				]
			);
		} else {
			$query_data_json = '';
		}

		$select_options = $select_default_options + $available_select_options;

		return [
			'options'         => $select_options,
			'data_json'       => $query_data_json,
			'default_options' => $select_default_options,
		];
	}
}
