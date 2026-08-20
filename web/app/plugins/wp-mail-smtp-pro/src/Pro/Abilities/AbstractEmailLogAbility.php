<?php

namespace WPMailSMTP\Pro\Abilities;

use WP_Error;
use WPMailSMTP\Abilities\AbstractAbility;

/**
 * Base for the email-log and statistics abilities.
 *
 * @since 4.9.0
 */
abstract class AbstractEmailLogAbility extends AbstractAbility {

	/**
	 * Maps a public status string to the internal email-log status integers
	 * used by the query layer.
	 *
	 * @since 4.9.0
	 *
	 * @var array
	 */
	const STATUS_PUBLIC_TO_INTERNAL = [
		'unsent'    => [ 0 ], // Email::STATUS_UNSENT.
		'sent'      => [ 1 ], // Email::STATUS_SENT.
		'waiting'   => [ 2 ], // Email::STATUS_WAITING.
		'delivered' => [ 3 ], // Email::STATUS_DELIVERED.
		'blocked'   => [ 4 ], // Email::STATUS_BLOCKED.
	];

	/**
	 * Maps an internal email-log status integer to the single public status
	 * string returned in responses.
	 *
	 * @since 4.9.0
	 *
	 * @var array
	 */
	const STATUS_INTERNAL_TO_PUBLIC = [
		0 => 'unsent',    // Email::STATUS_UNSENT.
		1 => 'sent',      // Email::STATUS_SENT.
		2 => 'waiting',   // Email::STATUS_WAITING.
		3 => 'delivered', // Email::STATUS_DELIVERED.
		4 => 'blocked',   // Email::STATUS_BLOCKED.
	];

	/**
	 * Resolve the email-log response formatter.
	 *
	 * @since 4.9.0
	 *
	 * @return EmailLogResponseFormatter
	 */
	protected function email_log_formatter() {

		return new EmailLogResponseFormatter();
	}

	/**
	 * Permission gate: viewer must hold the email-log view capability.
	 *
	 * @since 4.9.0
	 *
	 * @return true|WP_Error
	 */
	public function check_permission() {

		if ( ! current_user_can( wp_mail_smtp()->get_admin()->get_logs_access_capability() ) ) {
			return $this->forbidden();
		}

		return true;
	}

	/**
	 * Guard that the Email Log storage exists before querying it.
	 *
	 * @since 4.9.0
	 *
	 * @return true|WP_Error
	 */
	protected function ensure_email_log_storage() {

		$logs = wp_mail_smtp()->get_pro()->get_logs();

		if ( ! $logs || ! $logs->is_valid_db() ) {
			return new WP_Error(
				'wp_mail_smtp_logs_unavailable',
				esc_html__( 'The Email Log storage is not available.', 'wp-mail-smtp-pro' ),
				[ 'status' => 500 ]
			);
		}

		return true;
	}

	/**
	 * Build EmailsCollection query params shared by the listing and stats paths.
	 *
	 * Applies the status, date-range, and recipient/search filters the query
	 * layer can express. Returns a WP_Error when the status value is invalid.
	 *
	 * @since 4.9.0
	 *
	 * @param array $args   Normalized input.
	 * @param int   $limit  Page size.
	 * @param int   $offset Row offset.
	 *
	 * @return array|WP_Error
	 */
	protected function build_collection_params( array $args, $limit, $offset ) {

		$params = [
			'per_page' => $limit,
			'offset'   => $offset,
		];

		$status = $this->resolve_status_param( $args );

		if ( is_wp_error( $status ) ) {
			return $status;
		}

		if ( $status !== null ) {
			$params['status'] = $status;
		}

		$date = $this->resolve_date_range( $args );

		if ( $date !== null ) {
			$params['date'] = $date;
		}

		$term = $this->resolve_search_term( $args );

		if ( $term !== '' ) {
			$params['search'] = [
				'place' => 'people',
				'term'  => $term,
			];
		}

		return $params;
	}

	/**
	 * Resolve the recipient/free-text search term, both of which target
	 * recipient email addresses.
	 *
	 * @since 4.9.0
	 *
	 * @param array $args Normalized input.
	 *
	 * @return string
	 */
	protected function resolve_search_term( array $args ) {

		if ( ! empty( $args['recipient'] ) ) {
			return sanitize_text_field( $args['recipient'] );
		}

		if ( ! empty( $args['search'] ) ) {
			return sanitize_text_field( $args['search'] );
		}

		return '';
	}

	/**
	 * Resolve and validate a public status filter to its internal status value.
	 *
	 * A single-int status is returned as a scalar; a multi-int status is
	 * returned as an array so the query layer can emit a `status IN (...)`
	 * filter and keep totals/pagination correct.
	 *
	 * @since 4.9.0
	 *
	 * @param array $args Normalized input.
	 *
	 * @return int|int[]|null|WP_Error Internal status value, null for no filter, or error.
	 */
	protected function resolve_status_param( array $args ) {

		if ( empty( $args['status'] ) ) {
			return null;
		}

		$public = sanitize_text_field( $args['status'] );

		if ( ! isset( self::STATUS_PUBLIC_TO_INTERNAL[ $public ] ) ) {
			return new WP_Error(
				'wp_mail_smtp_invalid_status',
				esc_html__( 'Unknown status value.', 'wp-mail-smtp-pro' ),
				[ 'status' => 400 ]
			);
		}

		$internals = self::STATUS_PUBLIC_TO_INTERNAL[ $public ];

		return count( $internals ) === 1 ? $internals[0] : $internals;
	}

	/**
	 * Build a [from, to] date range for the query layer from input.
	 *
	 * @since 4.9.0
	 *
	 * @param array $args Normalized input.
	 *
	 * @return array|null
	 */
	protected function resolve_date_range( array $args ) {

		$from = ! empty( $args['date_from'] ) ? sanitize_text_field( $args['date_from'] ) : '';
		$to   = ! empty( $args['date_to'] ) ? sanitize_text_field( $args['date_to'] ) : '';

		if ( $from === '' && $to === '' ) {
			return null;
		}

		return [
			$from !== '' ? $from : '1970-01-01',
			$to !== '' ? $to : gmdate( 'Y-m-d' ),
		];
	}

	/**
	 * Resolve a sort field to one the query layer supports.
	 *
	 * @since 4.9.0
	 *
	 * @param string $orderby Requested sort field.
	 *
	 * @return string
	 */
	protected function resolve_orderby( $orderby ) {

		return in_array( $orderby, [ 'date_sent', 'subject', 'status' ], true ) ? $orderby : 'date_sent';
	}

	/**
	 * Resolve a sort direction to ASC or DESC.
	 *
	 * @since 4.9.0
	 *
	 * @param string $order Requested direction.
	 *
	 * @return string
	 */
	protected function resolve_order( $order ) {

		return strtolower( (string) $order ) === 'asc' ? 'ASC' : 'DESC';
	}

	/**
	 * Whether a mailer slug is one of the known providers.
	 *
	 * @since 4.9.0
	 *
	 * @param string $slug Mailer slug.
	 *
	 * @return bool
	 */
	protected function is_known_mailer( $slug ) {

		return array_key_exists( $slug, $this->get_known_mailers() );
	}

	/**
	 * Validate an optional mailer slug filter.
	 *
	 * @since 4.9.0
	 *
	 * @param string $slug Mailer slug, or empty for no filter.
	 *
	 * @return true|WP_Error
	 */
	protected function validate_mailer( $slug ) {

		if ( $slug === '' || $this->is_known_mailer( $slug ) ) {
			return true;
		}

		return new WP_Error(
			'wp_mail_smtp_invalid_mailer',
			esc_html__( 'Unknown mailer slug.', 'wp-mail-smtp-pro' ),
			[ 'status' => 400 ]
		);
	}

	/**
	 * Get the known mailer slug => class map.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	protected function get_known_mailers() {

		return wp_mail_smtp()->get_providers()->get_providers();
	}

	/**
	 * Shared `status` input-schema fragment.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	protected function status_schema() {

		return [
			'description' => esc_html__( 'Filter by email status: unsent (failed to send), sent, waiting (delivery confirmation pending), delivered (confirmed), or blocked.', 'wp-mail-smtp-pro' ),
			'type'        => 'string',
			'enum'        => [ 'unsent', 'sent', 'waiting', 'delivered', 'blocked' ],
		];
	}

	/**
	 * Shared `mailer` input-schema fragment.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	protected function mailer_schema() {

		return [
			'description' => esc_html__( 'Filter by mailer slug (e.g. smtp, gmail, sendlayer).', 'wp-mail-smtp-pro' ),
			'type'        => 'string',
		];
	}

	/**
	 * Shared email-log item output-schema fragment.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	protected function email_log_item_schema() {

		return [
			'type'       => 'object',
			'properties' => [
				'id'              => [ 'type' => 'integer' ],
				'subject'         => [ 'type' => 'string' ],
				'from'            => [ 'type' => 'string' ],
				'to'              => [ 'type' => 'array' ],
				'status'          => [
					'type' => 'string',
					'enum' => [ 'unsent', 'sent', 'waiting', 'delivered', 'blocked' ],
				],
				'date_sent'       => [ 'type' => [ 'string', 'null' ] ],
				'mailer'          => [ 'type' => 'string' ],
				'has_attachments' => [ 'type' => 'boolean' ],
			],
		];
	}

	/**
	 * Compute a success/attempted rate as a percentage.
	 *
	 * @since 4.9.0
	 *
	 * @param int $success Successful (delivered + sent) count.
	 * @param int $unsent  Unsent count.
	 *
	 * @return float
	 */
	protected function success_rate( $success, $unsent ) {

		$attempted = $success + $unsent;

		return $attempted > 0 ? round( ( $success / $attempted ) * 100, 2 ) : 0.0;
	}
}
