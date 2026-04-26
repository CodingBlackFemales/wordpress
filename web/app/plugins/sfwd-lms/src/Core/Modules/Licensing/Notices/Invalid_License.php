<?php
/**
 * Invalid License notice.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Licensing\Notices;

use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotices;
use WP_Screen;

/**
 * Invalid License notice.
 *
 * @since 4.18.0
 */
class Invalid_License {
	/**
	 * Outputs a notice if the entered License Key is invalid.
	 *
	 * @since 4.18.0
	 * @since 4.22.1 Hooked to `admin_init` action hook.
	 *
	 * @return void
	 */
	public function display(): void {
		AdminNotices::show(
			'learndash-invalid-license',
			learndash_get_license_message()
		)
			->when(
				function () {
					if ( ! function_exists( 'get_current_screen' ) ) {
						return false;
					}

					$current_screen = get_current_screen();

					return $current_screen instanceof WP_Screen
						&& ! in_array(
							$current_screen->id,
							[
								'admin_page_learndash-setup',
								'admin_page_learndash_hub_licensing',
							],
							true
						)
						&& learndash_should_load_admin_assets()
						&& ! learndash_is_license_hub_valid();
				}
			)
			->dismissible()
			->asError();
	}
}
