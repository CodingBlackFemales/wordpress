<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\Mandrill;

use WP_REST_Request;
use WPMailSMTP\Pro\Emails\Logs\Email;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\AbstractProcessor;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\Mandrill\Events\Failed;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\Events\Delivered;

/**
 * Class Processor.
 *
 * @since 4.6.0
 */
class Processor extends AbstractProcessor {

	/**
	 * Handle webhook incoming request.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_REST_Request $request Webhook request.
	 *
	 * @return bool
	 */
	public function handle( WP_REST_Request $request ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.CyclomaticComplexity.MaxExceeded

		$params = $request->get_params();

		if ( empty( $params['mandrill_events'] ) ) {
			return false;
		}

		$events = json_decode( $params['mandrill_events'], true );

		if ( empty( $events ) || ! is_array( $events ) ) {
			return false;
		}

		$processed = false;

		foreach ( $events as $event_data ) {
			if ( empty( $event_data['event'] ) || empty( $event_data['msg']['_id'] ) ) {
				continue;
			}

			$event_type = $event_data['event'];
			$message_id = $event_data['msg']['_id'];
			$email      = Email::get_by_message_id( $message_id );

			if ( empty( $email ) ) {
				continue;
			}

			$event = false;

			switch ( $event_type ) {
				case 'delivered':
					$event = new Delivered();
					break;

				case 'reject':
				case 'hard_bounce':
					$event = new Failed();
					break;
			}

			if ( empty( $event ) ) {
				continue;
			}

			/*
			 * If an email already has a failed status, do not handle the "reject" event to prevent duplicate alerts.
			 * Usually, the "reject" event is returned instantly in the email-sending API request, but if the email has
			 * attachments, Mandrill queues such emails and triggers the "reject" event asynchronously.
			 */
			if ( $event_type === 'reject' && $email->has_failed() ) {
				$processed = true;

				continue;
			}

			$event->handle( $email, $event_data );
			$processed = true;
		}

		return $processed;
	}
}
