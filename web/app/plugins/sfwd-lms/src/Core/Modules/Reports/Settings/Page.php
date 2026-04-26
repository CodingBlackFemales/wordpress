<?php
/**
 * Reports Settings Page.
 *
 * @since 4.17.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Reports\Settings;

use LearnDash\Core\Template\Admin_Views\Dashboards;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\App;

/**
 * Reports Settings Page.
 *
 * @since 4.17.0
 */
class Page {
	/**
	 * Creates a dashboard to show our widgets within.
	 *
	 * @since 4.17.0
	 *
	 * @param string $screen_id Current Screen ID.
	 * @param string $page_id   Current Page ID.
	 *
	 * @return void
	 */
	public function create_dashboard( $screen_id, $page_id ): void {
		if (
			$page_id !== Cast::to_string(
				App::getVar( 'learndash_settings_reports_page_id' )
			)
		) {
			return;
		}

		$template = new Dashboards\Reports();
		$template->show_html();
	}
}
