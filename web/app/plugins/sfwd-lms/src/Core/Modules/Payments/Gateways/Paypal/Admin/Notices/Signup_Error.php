<?php
/**
 * Signup error notice for PayPal checkout integration.
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
 * Signup error notice class.
 *
 * @since 4.25.0
 */
class Signup_Error extends Notice {
	/**
	 * Notice ID for the signup error notice.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $notice_id = 'paypal-checkout-signup-error';

	/**
	 * Registers the signup error notice.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function register_admin_notice(): void {
		AdminNotices::show(
			$this->notice_id,
			sprintf(
				// translators: %1$s - error message.
				esc_html__( 'There was an error connecting to your PayPal Checkout account: %1$s', 'learndash' ),
				esc_html( Cast::to_string( SuperGlobals::get_get_var( 'signup_error_message', '' ) ) )
			)
		)
			->ifUserCan( LEARNDASH_ADMIN_CAPABILITY_CHECK )
			->when(
				static function () {
					return Cast::to_bool( SuperGlobals::get_get_var( 'signup_error', 0 ) );
				}
			)
			->on( $this->settings_path )
			->autoParagraph()
			->asError();
	}
}
