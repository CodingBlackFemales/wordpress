<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\MailerSend\Events;

use WPMailSMTP\Pro\Emails\Logs\Webhooks\Events\Failed as FailedBase;
use WPMailSMTP\Pro\Emails\Logs\Email;

/**
 * Class Failed.
 *
 * @since 4.5.0
 */
class Failed extends FailedBase {

	/**
	 * Get error message from event data.
	 *
	 * @since 4.5.0
	 *
	 * @param array $data Event data.
	 *
	 * @return string
	 */
	protected function get_error_message( $data ) {

		$text = '';

		if ( ! empty( $data['morph'] ) && ! empty( $data['morph']['reason'] ) ) {
			$text = $data['morph']['reason'];
		}

		return ! empty( $text ) ? $text : parent::get_error_message( $data );
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

		if ( ! empty( $data['morph'] ) && ! empty( $data['morph']['type'] ) ) {
			return $data['morph']['type'];
		}

		return parent::get_error_code( $email, $data );
	}
}
