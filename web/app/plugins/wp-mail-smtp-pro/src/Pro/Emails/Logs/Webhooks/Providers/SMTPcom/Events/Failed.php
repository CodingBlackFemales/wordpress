<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\SMTPcom\Events;

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

		return isset( $data['resp_msg'] ) ? $data['resp_msg'] : parent::get_error_message( $data );
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

		if ( ! empty( $data['resp_code'] ) && ! empty( $data['event_label'] ) ) {
			return $data['resp_code'] . '_' . strtolower( $data['event_label'] );
		}

		return parent::get_error_code( $email, $data );
	}
}
