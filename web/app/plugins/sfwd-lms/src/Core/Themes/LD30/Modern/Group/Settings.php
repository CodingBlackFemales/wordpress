<?php
/**
 * LearnDash LD30 Modern Group Settings.
 *
 * @since 4.22.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Modern\Group;

/**
 * LearnDash LD30 Modern Group Settings.
 *
 * @since 4.22.0
 */
class Settings {
	/**
	 * Removes the custom course pagination settings from the group display content settings metabox.
	 *
	 * @since 4.22.0
	 *
	 * @param array<string,mixed> $settings_fields      The settings fields.
	 * @param string              $settings_metabox_key The settings metabox key.
	 *
	 * @return array<string,mixed>
	 */
	public function disable_custom_course_pagination( $settings_fields, string $settings_metabox_key ): array {
		if (
			wp_is_serving_rest_request()
			|| $settings_metabox_key !== 'learndash-group-display-content-settings'
		) {
			return $settings_fields;
		}

		unset( $settings_fields['group_courses_per_page_enabled'] );
		unset( $settings_fields['group_courses_per_page_custom'] );

		return $settings_fields;
	}
}
