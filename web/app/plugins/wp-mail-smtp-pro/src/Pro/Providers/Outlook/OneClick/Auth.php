<?php

namespace WPMailSMTP\Pro\Providers\Outlook\OneClick;

use Exception;
use WPMailSMTP\Admin\Area;
use WPMailSMTP\Admin\ConnectionSettings;
use WPMailSMTP\Admin\DebugEvents\DebugEvents;
use WPMailSMTP\Admin\SetupWizard;
use WPMailSMTP\ConnectionInterface;
use WPMailSMTP\Options as PluginOptions;
use WPMailSMTP\Pro\Providers\Outlook\OneClick\Auth\Client;
use WPMailSMTP\Pro\Providers\Outlook\Options;
use WPMailSMTP\Providers\AuthAbstract;
use WPMailSMTP\WP;

/**
 * Class Auth to request access.
 *
 * @since 4.3.0
 */
class Auth extends AuthAbstract {

	/**
	 * Auth constructor.
	 *
	 * @since 4.3.0
	 *
	 * @param ConnectionInterface $connection The Connection object.
	 */
	public function __construct( $connection = null ) {

		parent::__construct( $connection );

		if ( $this->mailer_slug !== Options::SLUG ) {
			return;
		}

		$this->options = $this->connection_options->get_group( $this->mailer_slug );

		if ( empty( $this->options['one_click_setup_enabled'] ) ) {
			return;
		}

		$this->get_client();
	}

	/**
	 * Init and get the OAuth2 Client object.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $force If the client should be forcefully reinitialized.
	 *
	 * @return Client
	 */
	public function get_client( $force = false ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		// Doesn't load client twice + gives ability to overwrite.
		if ( ! empty( $this->client ) && ! $force ) {
			return $this->client;
		}

		$this->client = new Client( WP::get_site_url() );

		if ( ! $this->is_auth_required() && ! $this->is_reauth_required() ) {
			$credentials   = $this->options['one_click_setup_credentials'] ?? [];
			$expires       = $credentials['expires'] ?? '';
			$refresh_token = $credentials['refresh_token'] ?? '';

			// Update the old token if needed.
			if ( ! empty( $expires ) && $expires < time() && ! empty( $refresh_token ) ) {
				$this->refresh_access_token( $refresh_token );
			}
		}

		return $this->client;
	}

	/**
	 * Get access and refresh tokens.
	 *
	 * @since 4.3.0
	 *
	 * @param string $authorization_code Authorization code.
	 *
	 * @throws Exception When a request error occurs.
	 */
	private function get_tokens( $authorization_code ) {

		$tokens = $this->client->get_tokens( $authorization_code );
		$data   = [
			'one_click_setup_status'       => 'active',
			'one_click_setup_credentials'  => [
				'access_token'  => $tokens['access_token'],
				'refresh_token' => $tokens['refresh_token'],
				'expires'       => $tokens['expires'],
			],
			'one_click_setup_user_details' => [
				'email' => $tokens['user_email'],
				'name'  => $tokens['user_name'],
			],
		];

		$all = $this->connection_options->get_all();

		$all[ $this->mailer_slug ] = PluginOptions::array_merge_recursive( $all[ $this->mailer_slug ], $data );

		if ( ! empty( $tokens['user_email'] ) ) {
			$all['mail']['from_email'] = $tokens['user_email'];
		}

		$this->connection_options->set( $all, false, true );
	}

	/**
	 * Refresh expired access token.
	 *
	 * @since 4.3.0
	 *
	 * @param string $refresh_token Refresh token.
	 */
	private function refresh_access_token( $refresh_token ) {

		try {
			$new_access_token = $this->client->refresh_access_token( $refresh_token );

			$all = $this->connection_options->get_all();

			// To save in DB.
			$all[ $this->mailer_slug ]['one_click_setup_credentials']['access_token']  = $new_access_token['access_token'];
			$all[ $this->mailer_slug ]['one_click_setup_credentials']['refresh_token'] = $new_access_token['refresh_token'];
			$all[ $this->mailer_slug ]['one_click_setup_credentials']['expires']       = $new_access_token['expires'];

			// To save in currently retrieved options array.
			$this->options['one_click_setup_credentials']['access_token']  = $new_access_token['access_token'];
			$this->options['one_click_setup_credentials']['refresh_token'] = $new_access_token['refresh_token'];
			$this->options['one_click_setup_credentials']['expires']       = $new_access_token['expires'];

			$this->connection_options->set( $all, false, true );
		} catch ( Exception $e ) { // Catch any other general exception just in case.
			DebugEvents::add_throttled(
				'Mailer: Outlook' . WP::EOL .
				sprintf(
					/* translators: %1$s - exception message. */
					esc_html__( 'Failed to refresh access token. %1$s', 'wp-mail-smtp-pro' ),
					$e->getMessage()
				),
				'outlook_refresh_error_' . $this->connection->get_id()
			);

			if ( $e->getCode() === 401 ) {
				$this->set_auth_status( 'reauth' );
			}
		}
	}

	/**
	 * Get the url, that users will be redirected back to finish the OAuth process.
	 *
	 * @since 4.3.0
	 *
	 * @param ConnectionInterface $connection The Connection object.
	 *
	 * @return string
	 */
	public static function get_plugin_auth_url( $connection = null ) {

		if ( is_null( $connection ) ) {
			$connection = wp_mail_smtp()->get_connections_manager()->get_primary_connection();
		}

		/**
		 * Filters the plugin auth redirect url.
		 *
		 * @since 4.3.0
		 *
		 * @param string $auth_url The plugin auth redirect url.
		 */
		$auth_url = apply_filters(
			'wp_mail_smtp_pro_providers_outlook_one_click_auth_get_plugin_auth_url',
			add_query_arg(
				[
					'page' => Area::SLUG,
					'tab'  => 'auth',
				],
				admin_url( 'options-general.php' )
			)
		);

		$state = [
			wp_create_nonce( 'wp_mail_smtp_provider_client_state' ),
			$connection->get_id(),
		];

		return add_query_arg(
			[
				'state'           => implode( '-', $state ),
				'one_click_setup' => 1,
			],
			$auth_url
		);
	}

	/**
	 * Process authorization.
	 * Redirect user back to settings with an error message, if failed.
	 *
	 * @since 4.3.0
	 */
	public function process() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.CyclomaticComplexity.TooHigh

		$redirect_url         = ( new ConnectionSettings( $this->connection ) )->get_admin_page_url();
		$is_setup_wizard_auth = ! empty( $this->options['is_setup_wizard_auth'] );

		if ( $is_setup_wizard_auth ) {
			$this->update_is_setup_wizard_auth( false );

			$redirect_url = SetupWizard::get_site_url() . '#/step/configure_mailer/outlook';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! ( isset( $_GET['tab'] ) && $_GET['tab'] === 'auth' ) ) {
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$state = isset( $_GET['state'] ) ? sanitize_key( $_GET['state'] ) : false;

		if ( empty( $state ) ) {
			wp_safe_redirect(
				add_query_arg( 'error', 'oauth_invalid_state', $redirect_url )
			);
			exit;
		}

		[ $nonce ] = array_pad( explode( '-', $state ), 1, false );

		// Verify the nonce that should be returned in the state parameter.
		if ( ! wp_verify_nonce( $nonce, $this->state_key ) ) {
			wp_safe_redirect(
				add_query_arg(
					'error',
					'microsoft_invalid_nonce',
					$redirect_url
				)
			);
			exit;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$authorization_code = ! empty( $_GET['authorization_code'] ) ? sanitize_text_field( wp_unslash( $_GET['authorization_code'] ) ) : '';

		if ( empty( $authorization_code ) ) {
			$event_id = DebugEvents::add( esc_html__( 'Microsoft authorization error. Authorization code is empty.', 'wp-mail-smtp-pro' ) );

			wp_safe_redirect(
				add_query_arg(
					[
						'error'          => 'outlook_one_click_setup_unsuccessful_oauth',
						'debug_event_id' => $event_id,
					],
					$redirect_url
				)
			);
			exit;
		}

		// Get access and refresh tokens.
		try {
			$this->get_tokens( $authorization_code );

			wp_safe_redirect(
				add_query_arg(
					'success',
					'outlook_one_click_setup_site_linked',
					$redirect_url
				)
			);
			exit;
		} catch ( Exception $e ) { // Catch any other general exception just in case.
			$event_id = DebugEvents::add(
				'Mailer: Outlook' . WP::EOL .
				sprintf(
					/* translators: %1$s - exception message. */
					esc_html__( 'Failed to obtain access token. %1$s', 'wp-mail-smtp-pro' ),
					$e->getMessage()
				)
			);

			wp_safe_redirect(
				add_query_arg(
					[
						'error'          => 'outlook_one_click_setup_unsuccessful_oauth',
						'debug_event_id' => $event_id,
					],
					$redirect_url
				)
			);
			exit;
		}
	}

	/**
	 * Get the auth URL used to process authorization.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_auth_url() {

		$settings_url = rawurlencode( ( new ConnectionSettings( $this->connection ) )->get_admin_page_url() );

		return $this->get_client()->get_auth_url(
			[
				'return'              => self::get_plugin_auth_url( $this->connection ),
				'plugin_settings_url' => $settings_url,
			]
		);
	}

	/**
	 * Get user information (like email etc) that is associated with the current OAuth connection.
	 *
	 * @since 4.3.0
	 *
	 * @return array
	 */
	public function get_user_info() {

		return $this->connection_options->get( $this->mailer_slug, 'one_click_setup_user_details' );
	}

	/**
	 * Whether client credentials are saved.
	 *
	 * We don't have any settings for One-Click Setup, so it's always `true`.
	 *
	 * @since 4.3.0
	 *
	 * @return true
	 */
	public function is_clients_saved() {

		return true;
	}

	/**
	 * Whether auth is required.
	 *
	 * @since 4.3.0
	 *
	 * @return bool
	 */
	public function is_auth_required() {

		$credentials = $this->options['one_click_setup_credentials'] ?? [];

		return empty( $credentials['access_token'] ) || empty( $credentials['refresh_token'] );
	}

	/**
	 * Whether reauthorization is required.
	 *
	 * @since 4.3.0
	 *
	 * @return bool
	 */
	public function is_reauth_required() {

		if ( $this->is_auth_required() ) {
			return false;
		}

		if (
			isset( $this->options['one_click_setup_status'] ) &&
			$this->options['one_click_setup_status'] === 'reauth'
		) {
			return true;
		}

		return false;
	}

	/**
	 * Set auth status.
	 *
	 * @since 4.3.0
	 *
	 * @param string $status Status name (active or reauth).
	 */
	private function set_auth_status( $status ) {

		$all = $this->connection_options->get_all();

		// To save in DB.
		$all[ $this->mailer_slug ]['one_click_setup_status'] = $status;

		// To save in currently retrieved options array.
		$this->options['one_click_setup_status'] = $status;

		$this->connection_options->set( $all, false, true );
	}
}
