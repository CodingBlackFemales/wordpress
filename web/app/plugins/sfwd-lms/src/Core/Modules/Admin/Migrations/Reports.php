<?php
/**
 * Core Reports Migration.
 *
 * @since 4.23.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Admin\Migrations;

use LearnDash\Core\Version_Tracker;
use LearnDash\Core\Modules\Reports\Settings\Reports_Section;

/**
 * Core Reports Migration.
 *
 * @since 4.23.1
 */
class Reports {
	/**
	 * Migrates the Core Reports default value to ensure it's set to 'yes'.
	 *
	 * This method is called both during version upgrades and new installations.
	 * For upgrades, it only runs once when upgrading to version 4.23.0+.
	 * For new installations, it sets the default value immediately.
	 *
	 * @since 4.23.1
	 *
	 * @return void
	 */
	public static function migrate_reports_default_value(): void {
		// Only run this migration once when upgrading to the version that introduces Core Reports.
		if ( Version_Tracker::has_upgraded( '4.23.0' ) ) {
			return;
		}

		$options = get_option( 'learndash_reports', [] );
		if ( ! is_array( $options ) ) {
			$options = [];
		}

		// If the option doesn't exist or the display_reports field is not set, set it to 'yes'.
		if ( ! isset( $options['display_reports'] ) ) {
			Reports_Section::add_section_instance();
			$report = Reports_Section::get_section_instance( Reports_Section::class );

			$options['display_reports'] = 'yes';

			// Remove the pre-update hook to prevent filters from interfering.
			remove_filter( 'pre_update_option_learndash_reports', [ $report, 'section_pre_update_option' ], 30 );
			update_option( 'learndash_reports', $options );
		}
	}
}
