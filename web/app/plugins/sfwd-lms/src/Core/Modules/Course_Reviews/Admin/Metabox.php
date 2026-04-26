<?php
/**
 * LearnDash Course Reviews Metabox.
 *
 * @since 4.25.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Course_Reviews\Admin;

use LearnDash\Core\Utilities\Cast;
use LearnDash_Settings_Metabox;

/**
 * Class LearnDash Course Reviews Metabox.
 *
 * @since 4.25.1
 */
class Metabox extends LearnDash_Settings_Metabox {
	/**
	 * Public constructor for class.
	 *
	 * @since 4.25.1
	 */
	public function __construct() {
		$this->settings_screen_id     = learndash_get_post_type_slug( 'course' );
		$this->settings_metabox_key   = 'learndash-course-reviews';
		$this->settings_section_label = sprintf(
			// translators: Singular name for Courses.
			__( '%s Reviews', 'learndash' ),
			learndash_get_custom_label( 'course' )
		);
		$this->metabox_context        = 'side';
		$this->metabox_priority       = 'default';

		$this->settings_fields_map = [
			'show_reviews' => 'show_reviews',
		];

		parent::__construct();
	}

	/**
	 * Load Settings Values.
	 *
	 * @since 4.25.1
	 *
	 * @return void
	 */
	public function load_settings_values() {
		parent::load_settings_values();

		if ( $this->settings_values_loaded ) {
			$post_id = Cast::to_int( get_the_ID() );

			$this->setting_option_values['show_reviews'] = learndash_course_reviews_is_review_enabled( $post_id )
				? 'yes'
				: ''; // Empty string is the unchecked value.
		}
	}

	/**
	 * Load Settings Fields.
	 *
	 * @since 4.25.1
	 *
	 * @return void
	 */
	public function load_settings_fields() {
		$this->setting_option_fields = [
			'show_reviews' => [
				'name'            => 'show_reviews',
				'label'           => sprintf(
					// Translators: Singular name for Courses.
					__( 'Allow Reviews for this %s?', 'learndash' ),
					learndash_get_custom_label( 'course' )
				),
				'type'            => 'checkbox-switch',
				'options'         => [
					'yes' => __( 'Enabled', 'learndash' ),
					'' => __( 'Disabled', 'learndash' ),
				],
				'default'         => 'yes',
				'value'           => $this->setting_option_values['show_reviews'],
			],
		];

		/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
		$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_metabox_key );

		parent::load_settings_fields();
	}

	/**
	 * Filters saved fields.
	 *
	 * @since 4.25.1
	 *
	 * @param array<string, mixed> $setting_values      The fields to save.
	 * @param string               $metabox_key The metabox key.
	 * @param string               $screen_id   The screen ID.
	 *
	 * @return array<string, mixed> The fields to save.
	 */
	public function filter_saved_fields(
		array $setting_values = [],
		string $metabox_key = '',
		string $screen_id = ''
	) {
		if (
			! isset( $setting_values['show_reviews'] )
			|| empty( $setting_values['show_reviews'] )
		) {
			$setting_values['show_reviews'] = '';
		}

		/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
		return apply_filters(
			'learndash_settings_save_values',
			$setting_values,
			$this->settings_metabox_key
		);
	}
}
