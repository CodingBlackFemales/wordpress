<?php
/**
 * LearnDash Settings Section for Payments Emails Metabox - Initial Payment Failed.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Emails\Settings;

use LearnDash\Core\Template\Template;

/**
 * Class LearnDash Settings Section for Payment Emails Metabox - Initial Payment Failed.
 *
 * @since 4.25.3
 */
class Initial_Payment_Failed extends Base {
	/**
	 * Constructor.
	 *
	 * @since 4.25.3
	 */
	protected function __construct() {
		$this->default_subject = esc_html__( 'Trouble with your recent payment', 'learndash' );
		$this->default_message = Template::get_admin_template( 'modules/payments/emails/settings/initial-payment-failed-message' );

		parent::__construct( 'initial_payment_failed', esc_html__( 'Initial Payment Failed', 'learndash' ) );
	}
}
