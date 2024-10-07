<?php
namespace WPForms\Pro\Emails;

use WPForms\Pro\Reports\EntriesCount;
use WPForms\Emails\Summaries as BaseSummaries;

/**
 * Email Summaries.
 *
 * @since 1.8.8
 */
class Summaries extends BaseSummaries {

	/**
	 * Constructor for the class.
	 * Initializes the object and registers the Lite weekly entries count cron schedule.
	 *
	 * @since 1.8.8
	 */
	public function __construct() {

		parent::__construct();

		// Unregister it if scheduled.
		$this->maybe_unregister_entries_count_schedule();
	}

	/**
	 * Override Email Summaries cron callback.
	 *
	 * @since 1.8.8
	 */
	public function cron() {

		( new LicenseBanner() )->init();

		parent::cron();
	}

	/**
	 * Get form entries.
	 *
	 * @since 1.8.8
	 *
	 * @return array
	 */
	protected function get_entries(): array {

		return ( new EntriesCount() )->get_by( 'form_trends', 0, 7, 'previous sunday' );
	}

	/**
	 * Check if the weekly entries count cron schedule is registered and unregister it if scheduled.
	 * This function helps in managing the scheduled cron job for counting weekly entries.
	 *
	 * @since 1.8.8
	 */
	private function maybe_unregister_entries_count_schedule() {

		// Check if the cron job is scheduled.
		if ( false === wp_next_scheduled( 'wpforms_weekly_entries_count_cron' ) ) {
			// If not scheduled, return without performing any action.
			return;
		}

		// Clear the scheduled hook for the weekly entries count cron job.
		wp_clear_scheduled_hook( 'wpforms_weekly_entries_count_cron' );
	}
}
