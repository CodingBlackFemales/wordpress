<?php
/**
 * LearnDash Settings Section for Payments Emails Metabox - Payment Failed Access Revoked.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Emails\Settings;

use LearnDash\Core\Template\Template;

/**
 * Class LearnDash Settings Section for Payment Emails Metabox - Payment Failed Access Revoked.
 *
 * @since 4.25.3
 */
class Payment_Failed_Access_Revoked extends Base {
	/**
	 * Constructor.
	 *
	 * @since 4.25.3
	 */
	protected function __construct() {
		$this->default_subject = esc_html__( 'Subscription access paused', 'learndash' );
		$this->default_message = Template::get_admin_template( 'modules/payments/emails/settings/payment-failed-access-revoked-message' );

		parent::__construct( 'payment_failed_access_revoked', esc_html__( 'Payment Failed - Access Revoked', 'learndash' ) );
	}
}
