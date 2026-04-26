<?php
/**
 * The view class for the Reports dashboard.
 *
 * @since 4.17.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Admin_Views\Dashboards;

use LearnDash\Core\Modules;

/**
 * The view class for the Reports dashboard.
 *
 * @since 4.17.0
 */
class Reports extends Dashboard {
	/**
	 * Constructor.
	 *
	 * @since 4.17.0
	 */
	public function __construct() {
		/**
		 * Filters whether the Reports dashboard is enabled. Default true.
		 *
		 * @since 4.17.0
		 *
		 * @param bool $is_enabled Whether the dashboard is enabled.
		 *
		 * @return bool
		 */
		$this->is_enabled = apply_filters( 'learndash_dashboard_reports_is_enabled', true );

		$mapper = new Modules\Reports\Dashboard\Mapper();

		parent::__construct( 'dashboards/reports', $mapper->map() );
	}
}
