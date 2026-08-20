<?php

namespace WPMailSMTP\Pro\Alerts\Providers\WhatsApp;

use WPMailSMTP\Options as PluginOptions;

/**
 * Class Provider.
 *
 * @since 4.5.0
 */
class Provider {

	/**
	 * Ajax action slug.
	 *
	 * @since 4.5.0
	 */
	const AJAX_ACTION = 'wp_mail_smtp_whatsapp_recheck_status';

	/**
	 * Register hooks.
	 *
	 * @since 4.5.0
	 *
	 * @return void
	 */
	public function hooks() {

		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'recheck_connection_status' ] );
		add_action( 'wp_mail_smtp_pro_alerts_admin_settings_before_save', [ $this, 'maybe_create_template_on_settings_save' ] );
	}

	/**
	 * Recheck WhatsApp connection status via AJAX.
	 *
	 * @since 4.5.0
	 */
	public function recheck_connection_status() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		check_ajax_referer( 'wp-mail-smtp-pro-admin', 'nonce' );

		// Check nonce and capabilities.
		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_global_options() ) ) {
			wp_send_json_error(
				[ 'message' => esc_html__( 'You do not have permission to perform this action.', 'wp-mail-smtp-pro' ) ]
			);
		}

		// Get connection data.
		$connection_id = isset( $_POST['connection_id'] ) ? sanitize_text_field( wp_unslash( $_POST['connection_id'] ) ) : '';
		$options       = PluginOptions::init();
		$connections   = $options->get( 'alert_whatsapp', 'connections' );

		if ( empty( $connections ) || empty( $connection_id ) ) {
			wp_send_json_error(
				[ 'message' => esc_html__( 'Connection not found.', 'wp-mail-smtp-pro' ) ]
			);
		}

		// Find the connection.
		$connection = null;

		foreach ( $connections as $conn ) {
			if ( hash_equals( hash( 'sha256', wp_json_encode( $conn ) ), $connection_id ) ) {
				$connection = $conn;

				break;
			}
		}

		if ( empty( $connection ) ) {
			wp_send_json_error(
				[ 'message' => esc_html__( 'Connection not found.', 'wp-mail-smtp-pro' ) ]
			);
		}

		// Generate HTML for the response.
		ob_start();

		$options_instance = new Options();

		$options_instance->display_template_status( $connection, true );
		$html = ob_get_clean();

		wp_send_json_success(
			[
				'html' => wp_kses_post( $html ),
			]
		);
	}

	/**
	 * Check if WhatsApp settings have changed and create template if needed.
	 *
	 * @since 4.5.0
	 *
	 * @param array $new_options New options that will be saved.
	 *
	 * @return array
	 */
	public function maybe_create_template_on_settings_save( $new_options ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		// Get current WhatsApp options to compare.
		$old_whatsapp_options = PluginOptions::init()->get_group( 'alert_whatsapp' );
		$new_whatsapp_options = isset( $new_options['alert_whatsapp'] ) ? $new_options['alert_whatsapp'] : [];

		// Check if WhatsApp options have changed or if they were previously empty.
		$options_changed = $this->connections_have_changed( $old_whatsapp_options, $new_whatsapp_options );
		$was_empty       = empty( $old_whatsapp_options ) || empty( $old_whatsapp_options['connections'] );

		// Only proceed if options changed or were previously empty.
		if ( ! $options_changed && ! $was_empty ) {
			return $new_options;
		}

		// Check if we have valid new WhatsApp alert options.
		if ( empty( $new_whatsapp_options['connections'] ) ) {
			return $new_options;
		}

		$handler = new Handler();

		// Process each connection.
		foreach ( $new_whatsapp_options['connections'] as $index => $connection ) {
			// Skip if missing required fields.
			if ( empty( $connection['access_token'] ) ||
				 empty( $connection['whatsapp_business_id'] ) ||
				 empty( $connection['phone_number_id'] ) ) {
				continue;
			}

			// Add template language if not already set (for new connections).
			if ( empty( $connection['template_language'] ) ) {
				$new_options['alert_whatsapp']['connections'][ $index ]['template_language'] = $handler->get_user_template_language();
			}

			// Ensure template exists for this connection.
			$handler->check_template_status( $new_options['alert_whatsapp']['connections'][ $index ], true );
		}

		return $new_options;
	}

	/**
	 * Check if WhatsApp connections have changed.
	 *
	 * @since 4.5.0
	 *
	 * @param array $old_options Old WhatsApp options.
	 * @param array $new_options New WhatsApp options.
	 *
	 * @return bool True if connections have changed, false otherwise.
	 */
	private function connections_have_changed( $old_options, $new_options ) {

		// Remove template_language from old options for comparison (since it's not in form data).
		if ( ! empty( $old_options['connections'] ) ) {
			foreach ( $old_options['connections'] as $index => $connection ) {
				unset( $old_options['connections'][ $index ]['template_language'] );
			}
		}

		// Compare the cleaned old options with the new options.
		return $old_options !== $new_options;
	}
}
