<?php
/**
 * Disconnected notice for PayPal checkout integration.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Admin\Notices;

use LearnDash\Core\Modules\Payments\Gateways\Paypal\Admin\Notices\Contracts\Notice;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotices;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;
use LearnDash\Core\Utilities\Cast;

/**
 * Disconnected notice class.
 *
 * @since 4.25.0
 */
class Disconnected extends Notice {
	/**
	 * Notice ID for the disconnected notice.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $notice_id = 'paypal-checkout-disconnected';

	/**
	 * Registers the disconnected notice.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function register_admin_notice(): void {
		AdminNotices::show(
			$this->notice_id,
			esc_html__( 'PayPal Checkout account disconnected.', 'learndash' )
		)
			->ifUserCan( LEARNDASH_ADMIN_CAPABILITY_CHECK )
			->when(
				static function () {
					return Cast::to_bool( SuperGlobals::get_get_var( 'disconnected', 0 ) );
				}
			)
			->on( $this->settings_path )
			->autoParagraph()
			->asInfo();
	}
}
