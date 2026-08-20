<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\Sendinblue\Events;

use WPMailSMTP\Pro\Emails\Logs\Webhooks\Events\Failed as FailedBase;
use WPMailSMTP\Pro\Emails\Logs\Email;
/**
 * Class Failed.
 *
 * @since 3.3.0
 */
class Failed extends FailedBase {

	/**
	 * Get error message from event data.
	 *
	 * @since 3.3.0
	 *
	 * @param array $data Event data.
	 *
	 * @return string
	 */
	protected function get_error_message( $data ) {

		return isset( $data['reason'] ) ? $data['reason'] : parent::get_error_message( $data );
	}

	/**
	 * Get error code from event data.
	 *
	 * @since 4.8.0
	 *
	 * @param Email $email Email object.
	 * @param array $data  Event data.
	 *
	 * @return string
	 */
	protected function get_error_code( $email, $data ) {

		return ! empty( $data['event'] ) ? $data['event'] : parent::get_error_code( $email, $data );
	}
}
