<?php
/**
 * Abstract notice for PayPal checkout integration.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Admin\Notices\Contracts;

/**
 * Abstract notice class.
 *
 * @since 4.25.0
 */
abstract class Notice {
	/**
	 * Notice ID.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $notice_id = '';

	/**
	 * Settings path.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $settings_path = 'admin.php?page=learndash_lms_payments&section-payment=settings_paypal_checkout';

	/**
	 * Registers the admin notice.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	abstract public function register_admin_notice(): void;
}
