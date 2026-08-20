<?php

namespace WPMailSMTP\Pro\Abilities\EmailLogs;

use WP_Error; // phpcs:ignore WPForms.PHP.UseStatement.UnusedUseStatement
use WPMailSMTP\Pro\Abilities\AbstractEmailLogAbility;
use WPMailSMTP\Pro\Emails\Logs\EmailsCollection;

/**
 * Ability: list logged emails with their summary metadata (Pro).
 *
 * Filters by recipient search term, status, mailer, and date range, with
 * limit/offset pagination and configurable sorting.
 *
 * @since 4.9.0
 */
class ListEmailLogsAbility extends AbstractEmailLogAbility {

	/**
	 * Ability slug, without the namespace prefix.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_name() {

		return 'list-email-logs';
	}

	/**
	 * Human-readable label.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_label() {

		return esc_html__( 'List Email Logs', 'wp-mail-smtp-pro' );
	}

	/**
	 * Human-readable description.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_description() {

		return esc_html__( 'List logged emails with their summary metadata, filtered by recipient, status, mailer, and date range.', 'wp-mail-smtp-pro' );
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
				'limit'     => $this->limit_schema(),
				'offset'    => $this->offset_schema(),
				'status'    => $this->status_schema(),
				'mailer'    => $this->mailer_schema(),
				'recipient' => [
					'description' => esc_html__( 'Filter by a recipient email address.', 'wp-mail-smtp-pro' ),
					'type'        => 'string',
				],
				'search'    => [
					'description' => esc_html__( 'Search term matched against recipient email addresses.', 'wp-mail-smtp-pro' ),
					'type'        => 'string',
				],
				'date_from' => $this->date_schema( esc_html__( 'Only include emails sent on or after this date (Y-m-d).', 'wp-mail-smtp-pro' ) ),
				'date_to'   => $this->date_schema( esc_html__( 'Only include emails sent on or before this date (Y-m-d).', 'wp-mail-smtp-pro' ) ),
				'orderby'   => [
					'description' => esc_html__( 'Sort field.', 'wp-mail-smtp-pro' ),
					'type'        => 'string',
					'enum'        => [ 'date_sent', 'subject', 'status' ],
					'default'     => 'date_sent',
				],
				'order'     => [
					'description' => esc_html__( 'Sort direction.', 'wp-mail-smtp-pro' ),
					'type'        => 'string',
					'enum'        => [ 'asc', 'desc' ],
					'default'     => 'desc',
				],
			],
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
				'logs'   => [
					'type'  => 'array',
					'items' => $this->email_log_item_schema(),
				],
				'total'  => [ 'type' => 'integer' ],
				'limit'  => [ 'type' => 'integer' ],
				'offset' => [ 'type' => 'integer' ],
			],
		];
	}

	/**
	 * Execute: list email logs.
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
		$mailer = isset( $args['mailer'] ) ? sanitize_text_field( $args['mailer'] ) : '';

		$validated = $this->validate_mailer( $mailer );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$pagination = $this->get_pagination( $args );
		$limit      = $pagination['limit'];
		$offset     = $pagination['offset'];

		$params = $this->build_collection_params( $args, $limit, $offset );

		if ( is_wp_error( $params ) ) {
			return $params;
		}

		if ( $mailer !== '' ) {
			$params['mailer'] = $mailer;
		}

		$params['orderby'] = $this->resolve_orderby( $args['orderby'] ?? '' );
		$params['order']   = $this->resolve_order( $args['order'] ?? '' );

		$collection = new EmailsCollection( $params );
		$total      = $collection->get_count();
		$formatter  = $this->email_log_formatter();

		$logs_out = [];

		foreach ( $collection->get() as $email ) {
			$logs_out[] = $formatter->email_summary( $email );
		}

		return [
			'logs'   => $logs_out,
			'total'  => $total,
			'limit'  => $limit,
			'offset' => $offset,
		];
	}
}
