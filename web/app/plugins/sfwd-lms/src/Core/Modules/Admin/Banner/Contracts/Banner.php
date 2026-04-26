<?php
/**
 * Banner interface.
 *
 * @package LearnDash\Core
 *
 * @since 4.25.4
 */

namespace LearnDash\Core\Modules\Admin\Banner\Contracts;

use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotice;

/**
 * Interface that defines the contract for all admin banners.
 *
 * @since 4.25.4
 */
interface Banner {
	/**
	 * Gets the banner ID.
	 *
	 * @since 4.25.4
	 *
	 * @return string
	 */
	public function get_banner_id(): string;

	/**
	 * Registers the banner notice.
	 *
	 * @since 4.25.4
	 *
	 * @return AdminNotice
	 */
	public function register(): AdminNotice;
}
