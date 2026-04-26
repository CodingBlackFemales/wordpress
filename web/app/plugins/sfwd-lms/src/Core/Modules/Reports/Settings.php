<?php
/**
 * Settings for Reports Disabled functionality.
 *
 * @since 4.23.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Reports;

use StellarWP\Learndash\StellarWP\Arrays\Arr;

/**
 * Class Settings for reports disabled settings.
 *
 * @since 4.23.1
 */
class Settings {
	/**
	 * Returns the core reports settings.
	 *
	 * @since 4.23.1
	 *
	 * @return array{display_reports: bool} The core reports settings.
	 */
	public static function get(): array {
		$settings = get_option( 'learndash_reports', [] );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		return [
			'display_reports' => 'yes' === Arr::get( $settings, 'display_reports' ),
		];
	}
}
