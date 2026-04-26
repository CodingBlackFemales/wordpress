<?php
/**
 * Connected notice for PayPal checkout integration.
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
 * Connected notice class.
 *
 * @since 4.25.0
 */
class Connected extends Notice {
	/**
	 * Notice ID for the connected notice.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $notice_id = 'paypal-checkout-connected';

	/**
	 * Registers the connected notice.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function register_admin_notice(): void {
		AdminNotices::show(
			$this->notice_id,
			esc_html__( 'PayPal Checkout account connected.', 'learndash' )
		)
			->ifUserCan( LEARNDASH_ADMIN_CAPABILITY_CHECK )
			->when(
				static function () {
					return Cast::to_bool( SuperGlobals::get_get_var( 'connected', 0 ) );
				}
			)
			->on( $this->settings_path )
			->autoParagraph()
			->asSuccess();
	}
}
