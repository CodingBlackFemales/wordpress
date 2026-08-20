<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\SMTP2GO;

use WPMailSMTP\Pro\Emails\Logs\Webhooks\AbstractProcessor;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\AbstractProvider;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\AbstractSubscriber;

/**
 * Class Provider.
 *
 * @since 4.1.0
 */
class Provider extends AbstractProvider {

	/**
	 * Get the webhook processor.
	 *
	 * @since 4.1.0
	 *
	 * @return AbstractProcessor
	 */
	public function get_processor() {

		if ( is_null( $this->processor ) ) {
			$this->processor = new Processor( $this );
		}

		return $this->processor;
	}

	/**
	 * Get the webhook subscription manager.
	 *
	 * @since 4.1.0
	 *
	 * @return AbstractSubscriber
	 */
	public function get_subscriber() {

		if ( is_null( $this->subscriber ) ) {
			$this->subscriber = new Subscriber( $this );
		}

		return $this->subscriber;
	}
}
