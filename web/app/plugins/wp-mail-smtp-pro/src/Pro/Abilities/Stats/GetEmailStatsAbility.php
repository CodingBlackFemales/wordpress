<?php

namespace WPMailSMTP\Pro\Abilities\Stats;

use WP_Error; // phpcs:ignore WPForms.PHP.UseStatement.UnusedUseStatement
use WPMailSMTP\Helpers\Helpers;
use WPMailSMTP\Pro\Abilities\AbstractEmailLogAbility;
use WPMailSMTP\Pro\Emails\Logs\DeliveryVerification\DeliveryVerification;
use WPMailSMTP\Pro\Emails\Logs\Reports\Report;

/**
 * Ability: aggregate email sending statistics for a period or date range (Pro).
 *
 * Mirrors the dashboard widget / Reports counts: delivered, sent, and unsent,
 * excluding waiting and blocked emails. Mailers that cannot verify delivery omit
 * the `delivered` field and fold any delivered count into `sent`. An optional
 * mailer slug scopes the same shape to a single mailer.
 *
 * @since 4.9.0
 */
class GetEmailStatsAbility extends AbstractEmailLogAbility {

	/**
	 * Ability slug, without the namespace prefix.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_name() {

		return 'get-email-stats';
	}

	/**
	 * Human-readable label.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_label() {

		return esc_html__( 'Get Email Stats', 'wp-mail-smtp-pro' );
	}

	/**
	 * Human-readable description.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_description() {

		return esc_html__( 'Get aggregate email sending statistics for a period or date range, optionally scoped to a single mailer.', 'wp-mail-smtp-pro' );
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
				'period'    => [
					'description' => esc_html__( 'Predefined reporting period. Ignored when a custom date range is supplied.', 'wp-mail-smtp-pro' ),
					'type'        => 'string',
					'enum'        => [ 'today', 'week', 'month', 'year', 'all' ],
					'default'     => 'week',
				],
				'date_from' => $this->date_schema( esc_html__( 'Custom range start date (Y-m-d). Overrides period.', 'wp-mail-smtp-pro' ) ),
				'date_to'   => $this->date_schema( esc_html__( 'Custom range end date (Y-m-d). Overrides period.', 'wp-mail-smtp-pro' ) ),
				'mailer'    => [
					'description' => esc_html__( 'Optionally scope the statistics to a single mailer slug (e.g. smtp, gmail, sendlayer).', 'wp-mail-smtp-pro' ),
					'type'        => 'string',
				],
			],
		];
	}

	/**
	 * Output schema.
	 *
	 * Same shape for overall and per-mailer modes. `delivered` is present only
	 * when the relevant mailer can verify delivery; otherwise its count is folded
	 * into `sent`.
	 *
	 * @since 4.9.0
	 *
	 * @return array
	 */
	public function get_output_schema() {

		return [
			'type'       => 'object',
			'properties' => [
				'delivered'    => [
					'description' => esc_html__( 'Count of confirmed-delivered emails. Present only for mailers that support delivery verification.', 'wp-mail-smtp-pro' ),
					'type'        => 'integer',
				],
				'sent'         => [ 'type' => 'integer' ],
				'unsent'       => [ 'type' => 'integer' ],
				'total'        => [ 'type' => 'integer' ],
				'success_rate' => [
					'description' => esc_html__( 'Percentage of attempted emails that succeeded, computed over delivered plus sent; unsent emails are excluded from the numerator. Waiting and blocked emails are excluded entirely.', 'wp-mail-smtp-pro' ),
					'type'        => 'number',
				],
				'message'      => [ 'type' => 'string' ],
			],
		];
	}

	/**
	 * Execute: aggregate email stats, overall or scoped to a single mailer.
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

		if ( $mailer !== '' ) {
			$validated = $this->validate_mailer( $mailer );

			if ( is_wp_error( $validated ) ) {
				return $validated;
			}
		}

		return $this->build_stats( $args, $mailer );
	}

	/**
	 * Build the stats response, overall or scoped to a single mailer.
	 *
	 * @since 4.9.0
	 *
	 * @param array  $args   Normalized input.
	 * @param string $mailer Mailer slug, or empty for overall stats.
	 *
	 * @return array
	 */
	protected function build_stats( array $args, $mailer ) {

		$range = $this->resolve_stats_range( $args );

		$report_params = [];

		if ( $range !== null ) {
			$report_params['date'] = $range;
		}

		if ( $mailer !== '' ) {
			$report_params['mailer'] = $mailer;
		}

		$report     = new Report( $report_params );
		$totals     = $report->get_stats_totals();
		$can_verify = $this->is_verification_capable( $mailer );

		$result = $this->shape_counts( $totals, $can_verify );

		$logs = wp_mail_smtp()->get_pro()->get_logs();

		if ( ! $logs->is_enabled() ) {
			$result['message'] = esc_html__( 'Detailed statistics require the Email Log to be enabled. Totals reflect only currently logged emails.', 'wp-mail-smtp-pro' );
		}

		return $result;
	}

	/**
	 * Shape a Reports counts row into the response counts.
	 *
	 * Non-verification mailers omit `delivered` and fold its count into `sent`.
	 *
	 * @since 4.9.0
	 *
	 * @param array $counts     Row with `delivered`, `sent`, `unsent` keys.
	 * @param bool  $can_verify Whether the relevant mailer verifies delivery.
	 *
	 * @return array
	 */
	protected function shape_counts( array $counts, $can_verify ) {

		$delivered = (int) ( $counts['delivered'] ?? 0 );
		$sent      = (int) ( $counts['sent'] ?? 0 );
		$unsent    = (int) ( $counts['unsent'] ?? 0 );

		if ( $can_verify ) {
			$shaped  = [
				'delivered' => $delivered,
				'sent'      => $sent,
				'unsent'    => $unsent,
			];
			$success = $delivered + $sent;
		} else {
			$shaped  = [
				'sent'   => $sent + $delivered,
				'unsent' => $unsent,
			];
			$success = $shaped['sent'];
		}

		$shaped['total']        = $success + $unsent;
		$shaped['success_rate'] = $this->success_rate( $success, $unsent );

		return $shaped;
	}

	/**
	 * Whether the relevant mailer can verify delivery.
	 *
	 * Overall mode keys off the active mailer; per-mailer mode keys off whether
	 * the slug has a delivery verifier.
	 *
	 * @since 4.9.0
	 *
	 * @param string $mailer Mailer slug, or empty for overall stats.
	 *
	 * @return bool
	 */
	protected function is_verification_capable( $mailer ) {

		if ( $mailer === '' ) {
			return ! Helpers::mailer_without_send_confirmation();
		}

		return array_key_exists( $mailer, DeliveryVerification::DELIVERY_VERIFIERS_PER_MAILER );
	}

	/**
	 * Resolve the stats date range from either a custom range or a period.
	 *
	 * @since 4.9.0
	 *
	 * @param array $args Normalized input.
	 *
	 * @return array|null A [Y-m-d, Y-m-d] range, or null for all-time.
	 */
	protected function resolve_stats_range( array $args ) {

		$custom = $this->resolve_date_range( $args );

		if ( $custom !== null ) {
			return $custom;
		}

		$period = $args['period'] ?? 'week';
		$today  = gmdate( 'Y-m-d' );

		$ranges = [
			'today' => [ $today, $today ],
			'week'  => [ gmdate( 'Y-m-d', strtotime( '-6 days' ) ), $today ],
			'month' => [ gmdate( 'Y-m-d', strtotime( '-1 month' ) ), $today ],
			'year'  => [ gmdate( 'Y-m-d', strtotime( '-1 year' ) ), $today ],
		];

		// 'all' (and any unrecognized period) means no date filter.
		return $ranges[ $period ] ?? null;
	}
}
