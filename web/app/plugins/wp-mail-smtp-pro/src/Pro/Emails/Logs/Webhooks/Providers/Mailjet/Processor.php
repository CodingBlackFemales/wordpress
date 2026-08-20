<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\Mailjet;

use WP_REST_Request;
use WPMailSMTP\Pro\Emails\Logs\Email;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\AbstractProcessor;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\Events\Delivered;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\Mailjet\Events\Failed;

/**
 * Class Processor.
 *
 * @since 4.2.0
 */
class Processor extends AbstractProcessor {

	/**
	 * Handle webhook incoming request.
	 *
	 * @since 4.2.0
	 *
	 * @param WP_REST_Request $request Webhook request.
	 *
	 * @return bool
	 */
	public function handle( WP_REST_Request $request ) {

		$event_type = strtolower( $request->get_param( 'event' ) );
		$message_id = $request->get_param( 'MessageID' );

		if ( empty( $event_type ) || empty( $message_id ) ) {
			return false;
		}

		$email = Email::get_by_message_id( $message_id );

		if ( empty( $email ) ) {
			return false;
		}

		$event_data = [
			'error'   => $request->get_param( 'error' ),
			'comment' => $request->get_param( 'comment' ),
		];
		$event      = false;

		if ( $event_type === 'sent' ) {
			$event = new Delivered();
		} elseif ( $event_type === 'bounce' || $event_type === 'blocked' ) {
			$event = new Failed();
		}

		if ( $event === false ) {
			return false;
		}

		$event->handle( $email, $event_data );

		return true;
	}
}
