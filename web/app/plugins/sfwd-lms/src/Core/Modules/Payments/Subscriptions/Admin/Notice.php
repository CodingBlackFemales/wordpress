<?php
/**
 * Subscription cancellation admin notice.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Subscriptions\Admin;

use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotices;

/**
 * Subscription cancellation admin notice class.
 *
 * @since 4.25.0
 */
class Notice {
	/**
	 * Notice ID for subscription cancellation.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	private const NOTICE_ID = 'learndash_subscription_cancellation';

	/**
	 * Registers the admin notice.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function register_admin_notice(): void {
		$transient_key = 'ld_subscription_canceled_user_' . get_current_user_id();

		$cancellation_result = get_transient( $transient_key );

		if ( false === $cancellation_result ) {
			return;
		}

		// Delete the transient to prevent showing the notice again.
		delete_transient( $transient_key );

		$notice = AdminNotices::show(
			self::NOTICE_ID,
			$this->get_notice_message( (bool) $cancellation_result )
		)
			->ifUserCan( LEARNDASH_ADMIN_CAPABILITY_CHECK )
			->autoParagraph();

		if ( $cancellation_result ) {
			$notice->asSuccess();
		} else {
			$notice->asError();
		}
	}

	/**
	 * Gets the notice message based on the cancellation result.
	 *
	 * @since 4.25.0
	 *
	 * @param bool $cancellation_result Whether the cancellation was successful.
	 *
	 * @return string The notice message.
	 */
	private function get_notice_message( bool $cancellation_result ): string {
		if ( $cancellation_result ) {
			return __( 'The subscription has been canceled successfully.', 'learndash' );
		}

		return __( 'The subscription could not be canceled. Please try again or contact support.', 'learndash' );
	}
}
