<?php

namespace WPMailSMTP\Pro\Providers\Outlook;

use WPMailSMTP\ConnectionInterface;
use WPMailSMTP\Options;
use WPMailSMTP\Pro\Providers\Outlook\OneClick\Auth as AuthOneClick;
use WPMailSMTP\WP;

/**
 * Class Provider.
 *
 * @since 4.3.0
 */
class Provider {

	/**
	 * Register hooks.
	 *
	 * @since 4.3.0
	 */
	public function hooks() {

		// Process settings data before save on settings page.
		add_filter( 'wp_mail_smtp_admin_connection_settings_process_data', [ $this, 'process_settings_data' ], 10, 2 );

		// Set options defaults before they were saved.
		add_filter( 'wp_mail_smtp_options_postprocess_key_defaults', [ $this, 'options_defaults' ], 10, 3 );

		// Sanitize options before save.
		add_filter( 'wp_mail_smtp_options_set', [ $this, 'sanitize_options' ] );

		// Swap to One-Click Setup if it's enabled.
		add_filter( 'wp_mail_smtp_providers_loader_get_entity', [ $this, 'swap_to_one_click_setup' ], 10, 4 );

		// Display provider related admin notices.
		if ( WP::use_global_plugin_settings() ) {
			add_action( 'network_admin_notices', [ $this, 'display_notices' ] );
		} else {
			add_action( 'admin_notices', [ $this, 'display_notices' ] );
		}

		// AJAX callback for removing the oAuth authorization connection.
		add_action( 'wp_ajax_wp_mail_smtp_vue_remove_outlook_one_click_setup_oauth_connection', [ $this, 'remove_oauth_connection' ] );

		/*
		 * Update "one_click_setup" option based on incoming authorization request.
		 *
		 * Most likely we will remove this after settings page design refresh, since we are going
		 * to update mailer settings before performing authorization (like in the Setup Wizard).
		 */
		add_filter(
			'wp_mail_smtp_admin_pages_auth_tab_process_auth_connection',
			function ( $connection ) {
				if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_global_options() ) ) {
					return $connection;
				}

				if (
					$connection instanceof ConnectionInterface &&
					$connection->get_mailer_slug() === 'outlook'
				) {
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$one_click_setup_enabled = isset( $_GET['one_click_setup'] );

					$connection->get_options()->set( [ 'outlook' => [ 'one_click_setup_enabled' => $one_click_setup_enabled ] ], false, false );
				}

				return $connection;
			},
			PHP_INT_MAX
		);
	}

	/**
	 * Process settings data before save on settings page.
	 *
	 * Mainly used to convert checkbox value to boolean.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data     Connection data.
	 * @param array $old_data Old connection data.
	 *
	 * @return array
	 */
	public function process_settings_data( $data, $old_data ) {

		$data['outlook']['one_click_setup_enabled'] = isset( $data['outlook']['one_click_setup_enabled'] );

		return $data;
	}

	/**
	 * Set options defaults before they were saved.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed  $value Option value.
	 * @param string $group Group key.
	 * @param string $key   Option key.
	 *
	 * @return mixed
	 */
	public function options_defaults( $value, $group, $key ) {

		if ( $group === 'outlook' && $key === 'one_click_setup_enabled' ) {
			$value = false;
		}

		return $value;
	}

	/**
	 * Sanitize options before save.
	 *
	 * @since 4.3.0
	 *
	 * @param array $options Currently processed options passed to a filter hook.
	 *
	 * @return array
	 */
	public function sanitize_options( $options ) {

		if ( ! isset( $options['outlook']['one_click_setup_enabled'] ) ) {
			// Disabled by default.
			$options['outlook']['one_click_setup_enabled'] = false;

			return $options;
		}

		$options['outlook']['one_click_setup_enabled'] = (bool) $options['outlook']['one_click_setup_enabled'];

		return $options;
	}

	/**
	 * Swap to One-Click Setup if it's enabled.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed  $entity   Entity object.
	 * @param string $provider Provider name.
	 * @param string $request  Entity name.
	 * @param array  $args     Entity arguments.
	 *
	 * @return mixed
	 */
	public function swap_to_one_click_setup( $entity, $provider, $request, $args ) {

		if ( $provider === 'outlook' ) {
			$connection              = null;
			$one_click_provider_path = 'WPMailSMTP\Pro\Providers\Outlook\OneClick\\';

			if ( $request === 'Mailer' && isset( $args[1] ) ) {
				$connection = $args[1];
			} elseif ( ( $request === 'Auth' || $request === 'Options' ) && isset( $args[0] ) ) {
				$connection = $args[0];
			}

			if ( is_null( $connection ) ) {
				$connection = wp_mail_smtp()->get_connections_manager()->get_primary_connection();
			}

			if (
				$connection instanceof ConnectionInterface &&
				class_exists( $one_click_provider_path . $request ) &&
				(
					$connection->get_options()->get( 'outlook', 'one_click_setup_enabled' ) === true ||
					$request === 'Options'
				)
			) {
				$class  = $one_click_provider_path . $request;
				$entity = new $class( ...$args );
			}
		}

		return $entity;
	}

	/**
	 * AJAX callback for removing the oAuth authorization connection.
	 *
	 * Currently used only for Setup Wizard and works only with primary connection.
	 *
	 * @since 4.3.0
	 */
	public function remove_oauth_connection() {

		check_ajax_referer( 'wpms-admin-nonce', 'nonce' );

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_global_options() ) ) {
			wp_send_json_error();
		}

		$options = Options::init();
		$old_opt = $options->get_all_raw();

		unset( $old_opt['outlook']['one_click_setup_credentials'] );
		unset( $old_opt['outlook']['one_click_setup_user_details'] );

		$options->set( $old_opt );

		wp_send_json_success();
	}

	/**
	 * Display notices.
	 *
	 * @since 3.11.0
	 */
	public function display_notices() {

		$options = Options::init();

		if (
			$options->get( 'mail', 'mailer' ) === 'outlook' &&
			$options->get( 'outlook', 'one_click_setup_enabled' ) === true
		) {
			$auth    = new AuthOneClick();
			$license = wp_mail_smtp()->get_pro()->get_license();

			if ( ! $auth->is_auth_required() && $license->is_expired() ) { // Already authorized and license expired.
				?>
				<div class="notice notice-error">
					<p>
						<b><?php esc_html_e( 'Action Required!', 'wp-mail-smtp-pro' ); ?></b><br>
						<?php
						printf(
							wp_kses( /* translators: %1$s - WPMailSMTP.com renew URL. */
								__( 'One-Click Setup for Microsoft Outlook requires an active license. Emails are currently not being sent. <a href="%1$s" target="_blank" rel="noopener noreferrer">Renew your license</a> and reconnect your authorization.', 'wp-mail-smtp-pro' ),
								[
									'a' => [
										'href'   => [],
										'target' => [],
										'rel'    => [],
									],
								]
							),
							esc_url(
								$license->get_renewal_link(
									[
										'medium'  => 'outlook-one-click-global-notice',
										'content' => 'Renew your license',
									]
								)
							)
						);
						?>
					</p>
				</div>
				<?php
			} elseif ( ! $auth->is_auth_required() && ! $license->is_valid() ) { // Already authorized and license is not valid.
				?>
				<div class="notice notice-error">
					<p>
						<b><?php esc_html_e( 'Action Required!', 'wp-mail-smtp-pro' ); ?></b><br>
						<?php
						printf(
							wp_kses( /* translators: %1$s - URL to plugin settings page. */
								__( 'One-Click Setup for Microsoft Outlook requires an active license. Emails are currently not being sent. <a href="%1$s">Verify your license</a> and reconnect your authorization.', 'wp-mail-smtp-pro' ),
								[
									'a' => [
										'href' => [],
									],
								]
							),
							esc_url( wp_mail_smtp()->get_admin()->get_admin_page_url() )
						);
						?>
					</p>
				</div>
				<?php
			} elseif ( $auth->is_reauth_required() ) { // Reauthorization required.
				?>
				<div class="notice notice-error">
					<p>
						<b><?php esc_html_e( 'Action Required!', 'wp-mail-smtp-pro' ); ?></b><br>
						<?php
						printf(
							wp_kses( /* translators: %1$s - URL to plugin settings page. */
								__( 'Your Microsoft account connection has expired. Please <a href="%1$s">reconnect</a> your authorization.', 'wp-mail-smtp-pro' ),
								[
									'a' => [
										'href' => [],
									],
								]
							),
							esc_url( wp_mail_smtp()->get_admin()->get_admin_page_url() . '#wp-mail-smtp-setting-row-outlook-one-click-setup-authorize' )
						);
						?>
					</p>
				</div>
				<?php
			}
		}
	}
}
