<?php
/**
 * Settings for LD30 Modern Variations.
 *
 * @since 4.22.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Modern;

use StellarWP\Learndash\StellarWP\Arrays\Arr;

/**
 * Class Settings for modern settings.
 *
 * @since 4.22.0
 */
class Settings {
	/**
	 * Returns the modern settings.
	 *
	 * @since 4.22.0
	 *
	 * @return array{course_enabled: bool,  group_enabled: bool}
	 */
	public function get(): array {
		/**
		 * We can't use the LearnDash_Settings_Section_General_Appearance::get_setting() here because it is
		 * not always initialized, and for purposes of this logic we need to see the option when it is not initialized.
		 *
		 * @var array{ course_enabled: string, group_enabled: string } $settings The modern page enabled settings.
		 */
		$settings = get_option( 'learndash_settings_appearance', [] );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		return [
			'course_enabled' => 'yes' === Arr::get( $settings, 'course_enabled' ),
			'group_enabled'  => 'yes' === Arr::get( $settings, 'group_enabled' ),
		];
	}
}
