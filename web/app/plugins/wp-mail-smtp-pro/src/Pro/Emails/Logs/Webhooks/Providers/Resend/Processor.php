<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\Resend;

use WP_REST_Request;
use WPMailSMTP\Pro\Emails\Logs\Email;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\AbstractProcessor;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\Events\Delivered;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\Resend\Events\Failed;

/**
 * Class Processor.
 *
 * @since 4.7.0
 */
class Processor extends AbstractProcessor {

	/**
	 * Validate webhook incoming request.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Webhook request.
	 *
	 * @return bool
	 */
	public function validate( WP_REST_Request $request ) {

		return true;
	}

	/**
	 * Handle webhook incoming request.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Webhook request.
	 *
	 * @return bool
	 */
	public function handle( WP_REST_Request $request ) {

		$event_type = $request->get_param( 'type' );
		$event_data = $request->get_param( 'data' );

		if ( empty( $event_type ) || empty( $event_data['email_id'] ) ) {
			return false;
		}

		$message_id = $event_data['email_id'];
		$email      = Email::get_by_message_id( $message_id );

		if ( empty( $email ) ) {
			return false;
		}

		$event = false;

		if ( $event_type === Subscriber::EVENT_DELIVERED ) {
			$event = new Delivered();
		} elseif ( in_array( $event_type, [ Subscriber::EVENT_FAILED, Subscriber::EVENT_BOUNCED ], true ) ) {
			$event = new Failed();
		}

		if ( $event === false ) {
			return false;
		}

		$event->handle( $email, $event_data );

		return true;
	}
}
