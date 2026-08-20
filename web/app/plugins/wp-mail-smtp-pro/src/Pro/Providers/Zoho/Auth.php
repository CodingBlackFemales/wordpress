<?php

namespace WPMailSMTP\Pro\Providers\Zoho;

use WP_Error;
use WPMailSMTP\Admin\ConnectionSettings;
use WPMailSMTP\Admin\DebugEvents\DebugEvents;
use WPMailSMTP\Admin\SetupWizard;
use WPMailSMTP\ConnectionInterface;
use WPMailSMTP\Vendor\League\OAuth2\Client\Token\AccessToken;
use WPMailSMTP\Pro\Providers\Zoho\Auth\Zoho;
use WPMailSMTP\Providers\AuthAbstract;
use WPMailSMTP\WP;

/**
 * Class Auth
 *
 * @since 2.3.0
 */
class Auth extends AuthAbstract {

	/**
	 * Auth constructor.
	 *
	 * @since 2.3.0
	 *
	 * @param ConnectionInterface $connection The Connection object.
	 */
	public function __construct( $connection = null ) {

		parent::__construct( $connection );

		if ( $this->mailer_slug !== Options::SLUG ) {
			return;
		}

		$this->options = $this->connection_options->get_group( $this->mailer_slug );

		$this->get_client();
	}

	/**
	 * Init and get the OAuth2 Client object.
	 *
	 * @since 2.3.0
	 *
	 * @param bool $force If the client should be forcefully reinitialized.
	 *
	 * @return Zoho
	 */
	public function get_client( $force = false ) {

		// Doesn't load client twice.
		if ( ! empty( $this->client ) && ! $force ) {
			return $this->client;
		}

		$this->include_vendor_lib();

		$this->client = new Zoho(
			[
				'domain'       => $this->options['domain'],
				'clientId'     => $this->options['client_id'],
				'clientSecret' => $this->options['client_secret'],
				'redirectUri'  => self::get_plugin_auth_url(),
				'state'        => $this->get_state(),
			]
		);

		// Do not process if we don't have both Client ID & Client password.
		if ( ! $this->is_clients_saved() ) {
			return $this->client;
		}

		if ( ! empty( $this->options['access_token'] ) ) {
			$access_token = new AccessToken( (array) $this->options['access_token'] );

			// Refresh expired token if needed.
			if ( $access_token->hasExpired() ) {
				$this->refresh_access_token( $access_token );
			}
		}

		return $this->client;
	}

	/**
	 * Try to get an access token using the authorization code grant.
	 *
	 * @since 4.9.0
	 *
	 * @param string $code The authorization code returned by Zoho.
	 *
	 * @return WP_Error|true
	 */
	private function obtain_access_token( $code ) {

		if ( empty( $code ) ) {
			return true;
		}

		try {
			$access_token = $this->client->getAccessToken(
				'authorization_code',
				[
					'code'  => $code,
					'scope' => implode( ',', $this->client->default_scopes ),
					'state' => $this->client->getState(),
				]
			);

			$this->update_access_token( $access_token->jsonSerialize() );
			$this->update_refresh_token( $access_token->getRefreshToken() );
			$this->update_user_details( $access_token );

			return true;
		} catch ( \Exception $e ) {
			return new WP_Error( 'zoho_auth_failed', $e->getMessage() );
		}
	}

	/**
	 * Refresh expired access token.
	 *
	 * @since 4.9.0
	 *
	 * @param AccessToken $access_token Expired access token.
	 */
	private function refresh_access_token( $access_token ) {

		try {
			$refresh_token    = ! empty( $this->options['refresh_token'] ) ? $this->options['refresh_token'] : '';
			$new_access_token = $this->client->getAccessToken(
				'refresh_token',
				[ 'refresh_token' => $refresh_token ]
			);

			$this->update_access_token( $new_access_token->jsonSerialize() );
		} catch ( \Exception $e ) {
			DebugEvents::add_throttled(
				'Mailer: Zoho Mail' . WP::EOL . $e->getMessage(),
				'zoho_refresh_error_' . $this->connection->get_id()
			);
		}
	}

	/**
	 * Get the auth URL used to proceed to Provider to request access to send emails.
	 *
	 * @since 2.3.0
	 *
	 * @return string
	 */
	public function get_auth_url() {

		$client = $this->get_client();

		if ( ! empty( $client ) && $client instanceof Zoho ) {
			return $client->getAuthorizationUrl();
		}

		return '#';
	}

	/**
	 * Get and update user-related details (account ID, display name and email).
	 *
	 * @since 2.3.0
	 *
	 * @param \WPMailSMTP\Vendor\League\OAuth2\Client\Token\AccessTokenInterface $access_token The access token.
	 *
	 * @throws \Exception When plugin from email does not match any Zoho API from emails.
	 */
	protected function update_user_details( $access_token ) {

		if ( empty( $access_token ) ) {
			$access_token = new AccessToken( (array) $this->options['access_token'] );
		}

		// Default values.
		$user = [
			'email'        => '',
			'display_name' => '',
			'account_id'   => '',
		];

		$connection_options = $this->connection->get_options();
		$from_email         = $connection_options->get( 'mail', 'from_email' );

		try {
			$resource_owner = $this->get_client()->getResourceOwner( $access_token );
			$user           = $resource_owner->getAvailableSendEmailDetailsByEmail( $from_email );

			if ( empty( $user ) ) {
				$available_emails = $resource_owner->getAvailableSendEmailDetails();
				$user             = array_values( $available_emails )[0];
			}
		} catch ( \Exception $e ) {
			DebugEvents::add( 'Mailer: Zoho Mail' . WP::EOL . $e->getMessage() );
		}

		// To save in DB.
		$updated_settings = [
			'mail'             => [
				'from_email' => $user['email'],
			],
			$this->mailer_slug => [
				'user_details' => $user,
			],
		];

		// To save in currently retrieved options array.
		$this->options['user_details'] = $user;

		$connection_options->set( $updated_settings, false, false );
	}

	/**
	 * Get the auth code from the $_GET and save it.
	 * Redirect user back to settings with an error message, if failed.
	 *
	 * @since 2.3.0
	 */
	public function process() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

		$redirect_url         = ( new ConnectionSettings( $this->connection ) )->get_admin_page_url();
		$is_setup_wizard_auth = ! empty( $this->options['is_setup_wizard_auth'] );

		if ( $is_setup_wizard_auth ) {
			$this->update_is_setup_wizard_auth( false );

			$redirect_url = SetupWizard::get_site_url() . '#/step/configure_mailer/zoho';
		}

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

		list( $nonce ) = array_pad( explode( '-', $state ), 1, false );

		// Verify the nonce.
		if ( ! wp_verify_nonce( $nonce, $this->state_key ) ) {
			wp_safe_redirect(
				add_query_arg(
					'error',
					'zoho_invalid_nonce',
					$redirect_url
				)
			);
			exit;
		}

		// We can't process without saved client_id/secret.
		if ( ! $this->is_clients_saved() ) {
			wp_safe_redirect(
				add_query_arg(
					'error',
					'zoho_no_clients',
					$redirect_url
				)
			);
			exit;
		}

		$this->include_vendor_lib();

		$error = isset( $_GET['error'] ) ? sanitize_key( $_GET['error'] ) : '';

		// In case of any error: display a message to a user.
		if ( ! empty( $error ) ) {
			wp_safe_redirect(
				add_query_arg(
					'error',
					'zoho_' . $error,
					$redirect_url
				)
			);
			exit;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$code = isset( $_GET['code'] ) ? $_GET['code'] : '';

		if ( empty( $code ) ) {
			wp_safe_redirect(
				add_query_arg(
					'error',
					'zoho_no_code',
					$redirect_url
				)
			);
			exit;
		}

		// Ensure the SDK client is initialized.
		$this->get_client();

		// Exchange the auth code for an access token.
		$result = $this->obtain_access_token( $code );

		if ( $result instanceof WP_Error ) {
			$event_id = DebugEvents::add( 'Mailer: Zoho Mail' . WP::EOL . $result->get_error_message() );

			wp_safe_redirect(
				add_query_arg(
					[
						'error'          => 'zoho_unsuccessful_oauth',
						'debug_event_id' => $event_id,
					],
					$redirect_url
				)
			);
			exit;
		}

		wp_safe_redirect(
			add_query_arg(
				'success',
				'zoho_site_linked',
				$redirect_url
			)
		);
		exit;
	}

	/**
	 * {@inheritDoc}
	 *
	 * Modify the token expiration timestamp. By default the token expires in 1 hour.
	 * We are reducing the expiration by 11 minutes because of cache issues and server time mismatches.
	 *
	 * @since 2.3.0
	 *
	 * @param mixed $token The token data.
	 */
	protected function update_access_token( $token ) {

		if ( isset( $token['expires'] ) && is_int( $token['expires'] ) ) {
			$token['expires'] -= apply_filters( 'wp_mail_smtp_pro_update_access_token_expires_reduction', 11 * 60 );
		}

		parent::update_access_token( $token );
	}

}
