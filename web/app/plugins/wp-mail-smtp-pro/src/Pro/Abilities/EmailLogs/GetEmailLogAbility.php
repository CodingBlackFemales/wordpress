<?php

namespace WPMailSMTP\Pro\Abilities\EmailLogs;

use WP_Error;
use WPMailSMTP\Pro\Abilities\AbstractEmailLogAbility;
use WPMailSMTP\Pro\Emails\Logs\Email;

/**
 * Ability: get the full details of a single logged email (Pro).
 *
 * @since 4.9.0
 */
class GetEmailLogAbility extends AbstractEmailLogAbility {

	/**
	 * Ability slug, without the namespace prefix.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_name() {

		return 'get-email-log';
	}

	/**
	 * Human-readable label.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_label() {

		return esc_html__( 'Get Email Log', 'wp-mail-smtp-pro' );
	}

	/**
	 * Human-readable description.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_description() {

		return esc_html__( 'Get the full details of a single logged email, including its content.', 'wp-mail-smtp-pro' );
	}

	/**
	 * Input schema.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_input_schema() {

		return [
			'type'       => 'object',
			'properties' => [
				'log_id' => [
					'description' => esc_html__( 'The ID of the logged email to retrieve.', 'wp-mail-smtp-pro' ),
					'type'        => 'integer',
					'minimum'     => 1,
				],
			],
			'required'   => [ 'log_id' ],
		];
	}

	/**
	 * Output schema.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_output_schema() {

		return [
			'type'       => 'object',
			'properties' => [
				'id'               => [ 'type' => 'integer' ],
				'subject'          => [ 'type' => 'string' ],
				'from'             => [ 'type' => 'string' ],
				'to'               => [ 'type' => 'array' ],
				'cc'               => [ 'type' => 'array' ],
				'bcc'              => [ 'type' => 'array' ],
				'reply_to'         => [ 'type' => 'array' ],
				'status'           => [
					'type' => 'string',
					'enum' => [ 'unsent', 'sent', 'waiting', 'delivered', 'blocked' ],
				],
				'date_sent'        => [ 'type' => [ 'string', 'null' ] ],
				'mailer'           => [ 'type' => 'string' ],
				'content_plain'    => [ 'type' => 'string' ],
				'content_html'     => [ 'type' => 'string' ],
				'headers'          => [ 'type' => 'array' ],
				'attachment_count' => [ 'type' => 'integer' ],
				'error_message'    => [ 'type' => [ 'string', 'null' ] ],
				'error_code'       => [ 'type' => [ 'string', 'null' ] ],
			],
		];
	}

	/**
	 * Execute: get a single email log.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed $input Input data.
	 *
	 * @return array|WP_Error
	 */
	public function execute( $input = null ) {

		$available = $this->ensure_email_log_storage();

		if ( is_wp_error( $available ) ) {
			return $available;
		}

		$args   = $this->normalize_input( $input );
		$log_id = absint( $args['log_id'] ?? 0 );

		if ( $log_id < 1 ) {
			return new WP_Error(
				'wp_mail_smtp_invalid_log_id',
				esc_html__( 'A valid log ID is required.', 'wp-mail-smtp-pro' ),
				[ 'status' => 400 ]
			);
		}

		$email = new Email( $log_id );

		if ( ! $email->is_valid() ) {
			return new WP_Error(
				'wp_mail_smtp_log_not_found',
				esc_html__( 'Email log not found.', 'wp-mail-smtp-pro' ),
				[ 'status' => 404 ]
			);
		}

		return $this->email_log_formatter()->email_detail( $email );
	}
}
