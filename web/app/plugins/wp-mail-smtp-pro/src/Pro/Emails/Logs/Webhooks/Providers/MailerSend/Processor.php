<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\MailerSend;

use WP_REST_Request;
use WPMailSMTP\Pro\Emails\Logs\Email;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\AbstractProcessor;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\Events\Delivered;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\MailerSend\Events\Failed;

/**
 * Class Processor.
 *
 * @since 4.5.0
 */
class Processor extends AbstractProcessor {

	/**
	 * Handle webhook incoming request.
	 *
	 * @since 4.5.0
	 *
	 * @param WP_REST_Request $request Webhook request.
	 *
	 * @return bool
	 */
	public function handle( WP_REST_Request $request ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$params = $request->get_params();

		$event_type = $request->get_param( 'type' );
		$data       = $request->get_param( 'data' );
		$message_id = '';

		if ( ! empty( $data['email']['message']['id'] ) ) {
			$message_id = $data['email']['message']['id'];
		}

		if ( empty( $event_type ) || empty( $message_id ) ) {
			return false;
		}

		$email = Email::get_by_message_id( $message_id );

		if ( empty( $email ) ) {
			return false;
		}

		$event = false;

		if ( $event_type === 'activity.delivered' ) {
			$event = new Delivered();
		} elseif ( $event_type === 'activity.hard_bounced' ) {
			$event = new Failed();
		}

		if ( empty( $event ) ) {
			return false;
		}

		$event->handle( $email, $params['data'] );

		return true;
	}
}
