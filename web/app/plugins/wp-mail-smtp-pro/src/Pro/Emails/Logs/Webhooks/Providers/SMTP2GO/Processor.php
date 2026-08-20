<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\SMTP2GO;

use WP_REST_Request;
use WPMailSMTP\Pro\Emails\Logs\Email;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\AbstractProcessor;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\Events\Delivered;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\SMTP2GO\Events\Failed;

/**
 * Class Processor.
 *
 * @since 4.1.0
 */
class Processor extends AbstractProcessor {

	/**
	 * Handle webhook incoming request.
	 *
	 * @since 4.1.0
	 *
	 * @param WP_REST_Request $request Webhook request.
	 *
	 * @return bool
	 */
	public function handle( WP_REST_Request $request ) {

		$event_type = $request->get_param( 'event' );
		$message_id = $request->get_param( 'email_id' );

		if ( empty( $event_type ) || empty( $message_id ) ) {
			return false;
		}

		$email = Email::get_by_message_id( $message_id );

		if ( empty( $email ) ) {
			return false;
		}

		// Only consider the first "TO" address.
		$to        = $email->get_people( 'to' );
		$to        = is_array( $to ) ? $to[0] : $to;
		$recipient = $request->get_param( 'rcpt' );

		// Only consider events related to the first "TO" address.
		if ( $recipient !== $to ) {
			return false;
		}

		$event_data = [
			'message' => $request->has_param( 'message' ) ? $request->get_param( 'message' ) : '',
			'bounce'  => $request->has_param( 'bounce' ) ? $request->get_param( 'bounce' ) : '',
		];
		$event      = false;

		if ( $event_type === 'delivered' ) {
			$event = new Delivered();
		} elseif ( $event_type === 'bounce' || $event_type === 'reject' ) {
			$event = new Failed();
		}

		if ( $event === false ) {
			return false;
		}

		$event->handle( $email, $event_data );

		return true;
	}
}
