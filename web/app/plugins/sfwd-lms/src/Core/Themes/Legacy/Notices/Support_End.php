<?php
/**
 * Legacy theme end of support notice.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\Legacy\Notices;

use LearnDash_Theme_Register;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotice;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotices;

/**
 * Legacy theme end of support notice class.
 *
 * @since 4.21.4
 */
class Support_End {
	/**
	 * The ID of the notice.
	 *
	 * @since 4.21.4
	 *
	 * @var string
	 */
	const NOTICE_LEGACY_THEME_NOT_SUPPORTED = 'learndash_legacy_theme_end_of_support';

	/**
	 * Outputs the admin notice regarding Legacy Theme loss of support.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function register_admin_notices(): void {
		AdminNotices::show(
			self::NOTICE_LEGACY_THEME_NOT_SUPPORTED,
			sprintf(
				// translators: %1$s - link opening tag, %2$s - link closing tag.
				__(
					'Support for the LearnDash Legacy Template ends June 15th 2025. Please update to LearnDash 3.0 as soon as possible. %1$sLearn More.%2$s',
					'learndash'
				),
				'<a href="https://go.learndash.com/legacy" target="_blank" rel="noopener noreferrer">',
				'</a>'
			)
		)
			->when(
				static function () {
					return LearnDash_Theme_Register::get_active_theme_key() === 'legacy' && learndash_should_load_admin_assets();
				}
			)
			->ifUserCan( LEARNDASH_ADMIN_CAPABILITY_CHECK )
			->dismissible()
			->autoParagraph()
			->asWarning();
	}

	/**
	 * If the Theme is being changed to Legacy, clear the notice dismissal flag.
	 *
	 * @since 4.21.4
	 *
	 * @param array<string,mixed>       $value                The new value of the setting.
	 * @param array<string,mixed>|false $old_value            The old value of the setting. False if the setting is not set.
	 * @param string                    $settings_section_key The key of the settings section.
	 * @param string                    $settings_screen_id   The ID of the settings screen.
	 *
	 * @return array<string,mixed> The value of the setting.
	 */
	public function clear_dismissal( $value, $old_value, string $settings_section_key, string $settings_screen_id ) {
		if (
			! is_array( $old_value )
			|| ! isset( $old_value['active_theme'] )
			|| ! isset( $value['active_theme'] )
			|| $old_value['active_theme'] === 'legacy'
			|| $value['active_theme'] !== 'legacy'
		) {
			return $value;
		}

		AdminNotices::resetNoticeForUser(
			self::NOTICE_LEGACY_THEME_NOT_SUPPORTED,
			get_current_user_id()
		);

		return $value;
	}
}
