<?php
/**
 * Course Reviews module notices.
 *
 * @since 4.25.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Course_Reviews;

use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotices;

/**
 * Course Reviews module notice class.
 *
 * @since 4.25.1
 */
class Notice {
	/**
	 * Notice for Course Reviews merge.
	 *
	 * @since 4.25.1
	 *
	 * @var string
	 */
	private const NOTICE_COURSE_REVIEWS_MERGE = 'learndash_course_reviews_merge';

	/**
	 * Outputs the admin notice regarding Course Reviews merge.
	 *
	 * @since 4.25.1
	 *
	 * @return void
	 */
	public function register_admin_notices(): void {
		AdminNotices::show(
			self::NOTICE_COURSE_REVIEWS_MERGE,
			sprintf(
				// translators: %1$s - strong opening tag, %2$s - strong closing tag, %3$s - paragraph opening tag, %4$s - paragraph closing tag, %5$s - paragraph opening tag, %6$s - paragraph closing tag.
				__(
					'%1$sCourse Reviews is now part of LearnDash LMS.%2$s %3$sThe Course Reviews features have been added directly into LearnDash — no separate plugin needed.%4$sIf the LearnDash Course Reviews add-on was previously installed, it\'s no longer required and can be safely uninstalled.%5$s',
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
