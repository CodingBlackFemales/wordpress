<?php
/**
 * Reports Disabled Message.
 *
 * @since 4.23.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Reports\Disabled;

use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\App;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotices;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotice;

/**
 * Reports Disabled Message.
 *
 * @since 4.23.1
 */
class Notice {
	/**
	 * Shows a disabled message for the reports page.
	 *
	 * @param string $screen_id The screen ID.
	 * @param string $page_id   The page ID.
	 *
	 * @return void
	 */
	public function show_notice( $screen_id, $page_id ): void {
		if (
			$page_id !== Cast::to_string(
				App::getVar( 'learndash_settings_reports_page_id' )
			)
		) {
			return;
		}

		$notice = new AdminNotice(
			'learndash-reports-disabled',
			function () {
				return sprintf(
					/* translators: %1$s: Opening strong tag, %2$s: Closing strong tag, %3$s: Opening anchor tag, %4$s: Closing anchor tag */
					esc_html__( '%1$sYou have disabled Reports.%2$s Please %3$sre-enable this in your settings%4$s to view these reports.', 'learndash' ),
					'<strong>',
					'</strong>',
					'<a href="' . esc_url( admin_url( 'admin.php?page=learndash_lms_advanced&section-advanced=settings_reports' ) ) . '">',
					'</a>'
				);
			}
		);

		$notice
			->asSuccess()
			->autoParagraph()
			->inline();

		AdminNotices::render( $notice );
	}
}
