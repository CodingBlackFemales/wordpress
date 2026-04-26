<?php
/**
 * LearnDash Settings Section for Payments Emails Metabox - Second Attempt Failed.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Emails\Settings;

use LearnDash\Core\Template\Template;

/**
 * Class LearnDash Settings Section for Payment Emails Metabox - Second Attempt Failed.
 *
 * @since 4.25.3
 */
class Second_Attempt_Failed extends Base {
	/**
	 * Constructor.
	 *
	 * @since 4.25.3
	 */
	protected function __construct() {
		$this->default_subject = esc_html__( 'Still can\'t process your payment', 'learndash' );
		$this->default_message = Template::get_admin_template( 'modules/payments/emails/settings/second-attempt-failed-message' );

		parent::__construct( 'second_attempt_failed', esc_html__( 'Second Attempt Failed', 'learndash' ) );
	}
}
