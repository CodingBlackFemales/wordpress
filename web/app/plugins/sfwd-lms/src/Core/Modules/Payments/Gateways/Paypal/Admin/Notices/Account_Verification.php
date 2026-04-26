<?php
/**
 * Account verification notice for PayPal checkout integration.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Admin\Notices;

use LearnDash\Core\Modules\Payments\Gateways\Paypal\Admin\Notices\Contracts\Notice;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Admin\Account_Status_Manager;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotices;

/**
 * Account verification notice class.
 *
 * @since 4.25.0
 */
class Account_Verification extends Notice {
	/**
	 * Notice ID for the account verification notice.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $notice_id = 'paypal-checkout-account-verification';

	/**
	 * Registers the account verification notice.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function register_admin_notice(): void {
		foreach ( $this->get_error_messages() as $key => $error_message ) {
			AdminNotices::show(
				$this->notice_id . '-' . $key,
				$error_message
			)
				->ifUserCan( LEARNDASH_ADMIN_CAPABILITY_CHECK )
				->when(
					static function () use ( $error_message ) {
						return ! empty( $error_message );
					}
				)
				->on( $this->settings_path )
				->autoParagraph()
				->asError();
		}
	}

	/**
	 * Returns the error messages.
	 *
	 * @since 4.25.0
	 *
	 * @return string[] The error messages.
	 */
	protected function get_error_messages(): array {
		$account_status = new Account_Status_Manager();

		if ( ! $account_status->verify_account_status() ) {
			return $account_status->get_error_messages();
		}

		return [];
	}
}
