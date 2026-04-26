<?php
/**
 * LearnDash Settings Section for Payments Emails Metabox - Final Attempt Coming Up.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Emails\Settings;

use LearnDash\Core\Template\Template;

/**
 * Class LearnDash Settings Section for Payment Emails Metabox - Final Attempt Coming Up.
 *
 * @since 4.25.3
 */
class Final_Attempt_Coming_Up extends Base {
	/**
	 * Constructor.
	 *
	 * @since 4.25.3
	 */
	protected function __construct() {
		$this->default_subject = esc_html__( 'Final payment attempt coming soon', 'learndash' );
		$this->default_message = Template::get_admin_template( 'modules/payments/emails/settings/final-attempt-coming-up-message' );

		parent::__construct( 'final_attempt_coming_up', esc_html__( 'Final Attempt Coming Up', 'learndash' ) );
	}
}
