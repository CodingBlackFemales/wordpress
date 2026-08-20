<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\Mailjet\Events;

use WPMailSMTP\Pro\Emails\Logs\Webhooks\Events\Failed as FailedBase;
use WPMailSMTP\Pro\Emails\Logs\Email;
/**
 * Class Failed.
 *
 * @since 4.2.0
 */
class Failed extends FailedBase {

	/**
	 * Get error message from event data.
	 *
	 * @since 4.2.0
	 *
	 * @param array $data Event data.
	 *
	 * @return string
	 */
	protected function get_error_message( $data ) {

		if ( ! empty( $data['comment'] ) ) {
			return $data['comment'];
		}

		if ( ! empty( $data['error'] ) ) {
			return $data['error'];
		}

		return parent::get_error_message( $data );
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

		return ! empty( $data['error_related_to'] ) ? str_replace( [ ' ', '/' ], '_', $data['error_related_to'] ) : parent::get_error_code( $email, $data );
	}
}
