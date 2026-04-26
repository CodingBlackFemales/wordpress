<?php
/**
 * SSL requirement notice for PayPal checkout integration.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Admin\Notices;

use LearnDash\Core\Modules\Payments\Gateways\Paypal\Admin\Notices\Contracts\Notice;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotices;

/**
 * SSL requirement notice class.
 *
 * @since 4.25.0
 */
class Ssl_Requirement extends Notice {
	/**
	 * Notice ID for the SSL requirement.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $notice_id = 'paypal-checkout-ssl-required';

	/**
	 * Registers the SSL requirement notice.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function register_admin_notice(): void {
		AdminNotices::show(
			$this->notice_id,
			esc_html__( 'PayPal Checkout requires a valid SSL certificate and secure (HTTPS) connection. Please enable SSL to use PayPal Checkout.', 'learndash' )
		)
			->ifUserCan( LEARNDASH_ADMIN_CAPABILITY_CHECK )
			->when(
				static function () {
					return ! is_ssl();
				}
			)
			->on( $this->settings_path )
			->autoParagraph()
			->asError();
	}
}
