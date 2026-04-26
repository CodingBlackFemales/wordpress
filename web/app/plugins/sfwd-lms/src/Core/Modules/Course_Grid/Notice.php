<?php
/**
 * Course Grid module notices.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Course_Grid;

use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotices;

/**
 * Course Grid module notice class.
 *
 * @since 4.21.4
 */
class Notice {
	/**
	 * Notice for Course Grid merge.
	 *
	 * @since 4.21.4
	 *
	 * @var string
	 */
	private const NOTICE_COURSE_GRID_MERGE = 'learndash_course_grid_merge';

	/**
	 * Outputs the admin notice regarding Course Grid merge.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function register_admin_notices(): void {
		AdminNotices::show(
			self::NOTICE_COURSE_GRID_MERGE,
			sprintf(
				// translators: %1$s - strong opening tag, %2$s - strong closing tag, %3$s - paragraph opening tag, %4$s - paragraph closing tag, %5$s - paragraph opening tag, %6$s - paragraph closing tag.
				__(
					'%1$sCourse Grid is now part of LearnDash LMS.%2$s %3$sThe Course Grid features have been added directly into LearnDash — no separate plugin needed.%4$sIf the LearnDash LMS - Course Grid add-on was previously installed, it\'s no longer required and can be safely uninstalled.%5$s',
					'learndash'
				),
				'<strong>',
				'</strong>',
				'<p>',
				'<br>',
				'</p>',
			)
		)
			->ifUserCan( LEARNDASH_ADMIN_CAPABILITY_CHECK )
			->dismissible()
			->autoParagraph()
			->asInfo();
	}
}
