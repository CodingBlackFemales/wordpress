<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\Mandrill\Events;

use WPMailSMTP\Pro\Emails\Logs\Webhooks\Events\Failed as FailedBase;
use WPMailSMTP\Pro\Emails\Logs\Email;

/**
 * Class Failed.
 *
 * @since 4.6.0
 */
class Failed extends FailedBase {

	/**
	 * Get error message from event data.
	 *
	 * @since 4.6.0
	 *
	 * @param array $data Event data.
	 *
	 * @return string
	 */
	protected function get_error_message( $data ) {

		$reason = '';

		if ( ! empty( $data['msg']['reject']['reason'] ) ) {
			$reason = $data['msg']['reject']['reason'];
		} elseif ( ! empty( $data['msg']['bounce_description'] ) ) {
			$reason = $data['msg']['bounce_description'];

			if ( ! empty( $data['msg']['diag'] ) ) {
				$reason .= ' - ' . $data['msg']['diag'];
			}
		}

		return $reason;
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

		if ( ! empty( $data['msg']['state'] ) ) {
			return $data['msg']['state'];
		}

		return parent::get_error_code( $email, $data );
	}
}
