<?php

namespace WPMailSMTP\Pro\Providers;

use WP_Error;
use WPMailSMTP\Admin\ConnectionSettings;
use WPMailSMTP\Admin\DebugEvents\DebugEvents;
use WPMailSMTP\Admin\SetupWizard;
use WPMailSMTP\Helpers\Helpers;
use WPMailSMTP\MailCatcherInterface;
use WPMailSMTP\Pro\Providers\AmazonSES\Auth as SESAuth;
use WPMailSMTP\Pro\Providers\AmazonSES\Options as SESOptions;
use WPMailSMTP\Pro\Providers\Gmail\Provider as GmailProvider;
use WPMailSMTP\Pro\Providers\Outlook\Auth as OutlookAuth;
use WPMailSMTP\Pro\Providers\Outlook\Provider as OutlookProvider;
use WPMailSMTP\WP;

/**
 * Class Providers to add Pro providers.
 *
 * @since 1.5.0
 */
class Providers {

	/**
	 * Providers constructor.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {}

	/**
	 * WordPress related hooks.
	 *
	 * @since 1.5.0
	 */
	public function init() {

		add_filter( 'wp_mail_smtp_providers_loader_get_providers', [ $this, 'inject_providers' ] );

		add_action( 'load-index.php', [ $this, 'process_auth_code' ] );

		add_action( 'wp_mail_smtp_admin_area_enqueue_assets', [ $this, 'enqueue_assets' ] );

		add_action( 'wp_ajax_wp_mail_smtp_pro_providers_ajax', [ $this, 'process_ajax' ] );

		add_action( 'wp_mail_smtp_mailcatcher_pre_send_before', [ $this, 'update_php_mailer_properties' ] );

		add_action( 'admin_notices', [ $this, 'display_notices' ], 5 );

		// Init mailers providers.
		( new GmailProvider() )->hooks();
		( new OutlookProvider() )->hooks();
	}

	/**
	 * Inject own Pro providers.
	 *
	 * @since 1.5.0
	 * @since 2.3.0 Added Zoho mailer.
	 *
	 * @param array $providers The default providers.
	 *
	 * @return array
	 */
	public function inject_providers( $providers ) {

		$providers['amazonses'] = 'WPMailSMTP\Pro\Providers\AmazonSES\\';
		$providers['outlook']   = 'WPMailSMTP\Pro\Providers\Outlook\\';
		$providers['zoho']      = 'WPMailSMTP\Pro\Providers\Zoho\\';

		return $providers;
	}

	/**
	 * Complete the auth process for the Provider.
	 * Currently used for Microsoft Outlook only.
	 *
	 * @since 1.5.0
	 */
	public function process_auth_code() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		// Bail if the auth request is not allowed.
		if ( ! $this->allow_auth_request() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$state = sanitize_key( $_GET['state'] );
		$state = str_replace( 'wp-mail-smtp-', '', $state );

		list( $nonce, $connection_id ) = array_pad( explode( '-', $state ), 2, false );

		if ( empty( $state ) || empty( $nonce ) || empty( $connection_id ) ) {
			wp_safe_redirect(
				add_query_arg( 'error', 'oauth_invalid_state', wp_mail_smtp()->get_admin()->get_admin_page_url() )
			);
			exit;
		}

		$connection = wp_mail_smtp()->get_connections_manager()->get_connection( $connection_id, false );

		if ( $connection === false ) {
			wp_safe_redirect(
				add_query_arg( 'error', 'oauth_invalid_connection', wp_mail_smtp()->get_admin()->get_admin_page_url() )
			);
			exit;
		}

		$auth = new OutlookAuth( $connection );

		$redirect_url         = ( new ConnectionSettings( $connection ) )->get_admin_page_url();
		$outlook_options      = $connection->get_options()->get_group( 'outlook' );
		$is_setup_wizard_auth = ! empty( $outlook_options['is_setup_wizard_auth'] );

		if ( $is_setup_wizard_auth ) {
			$auth->update_is_setup_wizard_auth( false );

			$redirect_url = SetupWizard::get_site_url() . '#/step/configure_mailer/outlook';
		}

		if ( ! wp_verify_nonce( $nonce, $auth->state_key ) ) {
			$url = add_query_arg( 'error', 'microsoft_invalid_nonce', $redirect_url );
		} elseif ( isset( $_GET['error'] ) && isset( $_GET['error_description'] ) ) {
			$error_code    = sanitize_text_field( wp_unslash( $_GET['error'] ) );
			$error_message = sanitize_text_field( wp_unslash( $_GET['error_description'] ) );

			$event_id = DebugEvents::add( 'Mailer: Outlook' . WP::EOL . Helpers::format_error_message( $error_message, $error_code ) );

			$url = add_query_arg(
				[
					'error'          => 'microsoft_unsuccessful_oauth',
					'debug_event_id' => $event_id,
				],
				$redirect_url
			);
		} elseif ( ! isset( $_GET['code'] ) ) {
			$url = add_query_arg( 'error', 'microsoft_no_code', $redirect_url );
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
			$code = preg_replace( '/[^a-zA-Z0-9_\-.]/', '', $_GET['code'] );

			$result = $auth->process_auth( $code );

			if ( $result instanceof WP_Error ) {
				$event_id = DebugEvents::add( 'Mailer: Outlook' . WP::EOL . $result->get_error_message() );

				$url = add_query_arg(
					[
						'error'          => 'microsoft_unsuccessful_oauth',
						'debug_event_id' => $event_id,
					],
					$redirect_url
				);
			} else {
				$url = add_query_arg( 'success', 'microsoft_site_linked', $redirect_url );

				// Switch off one-click setup.
				$connection->get_options()->set( [ 'outlook' => [ 'one_click_setup_enabled' => false ] ], false, false );
			}
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Whether we allow the auth request to be processed.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	private function allow_auth_request() {

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_global_options() ) ) {
			return false;
		}

		// Ajax is not supported.
		if ( WP::is_doing_ajax() ) {
			return false;
		}

		// We should be coming from somewhere.
		if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
			return false;
		}

		// We should have a required GET data.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['state'] ) ) {
			return false;
		}

		$state = sanitize_key( $_GET['state'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Check whether state contains "wp-mail-smtp-".
		if ( substr( $state, 0, 13 ) !== 'wp-mail-smtp-' ) {
			return false;
		}

		return true;
	}

	/**
	 * Inject Pro features specific assets: CSS & JS.
	 *
	 * @since 1.5.0
	 */
	public function enqueue_assets() {

		// CSS.
		wp_enqueue_style(
			'wp-mail-smtp-admin-pro-settings',
			wp_mail_smtp()->pro->assets_url . '/css/smtp-pro-settings.min.css',
			[ 'wp-mail-smtp-admin' ],
			WPMS_PLUGIN_VER,
			false
		);

		/*
		 * JavaScript.
		 */
		wp_enqueue_script(
			'wp-mail-smtp-admin-pro-settings',
			wp_mail_smtp()->pro->assets_url . '/js/smtp-pro-settings' . WP::asset_min() . '.js',
			[ 'jquery', 'wp-mail-smtp-admin-jconfirm' ],
			WPMS_PLUGIN_VER,
			false
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$connection_id = isset( $_GET['connection_id'] ) ? sanitize_key( $_GET['connection_id'] ) : 'primary';

		wp_localize_script(
			'wp-mail-smtp-admin-pro-settings',
			'wp_mail_smtp_pro',
			[
				'ses_text_sending'                     => esc_html__( 'Sending...', 'wp-mail-smtp-pro' ),
				'ses_text_loading'                     => esc_html__( 'Loading...', 'wp-mail-smtp-pro' ),
				'ses_text_sent'                        => esc_html__( 'Sent', 'wp-mail-smtp-pro' ),
				'ses_text_resend'                      => esc_html__( 'Resend', 'wp-mail-smtp-pro' ),
				'ses_text_email_delete'                => esc_html__( 'Are you sure you want to delete this email address? You will need to add and verify it again if you want to use it in the future.', 'wp-mail-smtp-pro' ),
				'ses_text_smth_wrong'                  => esc_html__( 'Something went wrong, please reload the page and try again.', 'wp-mail-smtp-pro' ),
				'ses_text_email_invalid'               => esc_html__( 'Please make sure that the email address is valid.', 'wp-mail-smtp-pro' ),
				'ok'                                   => esc_html__( 'OK', 'wp-mail-smtp-pro' ),
				'plugin_url'                           => esc_url( wp_mail_smtp()->plugin_url ),
				'icon'                                 => esc_html__( 'Icon', 'wp-mail-smtp-pro' ),
				'error_occurred'                       => esc_html__( 'An error occurred!', 'wp-mail-smtp-pro' ),
				'ses_text_resend_failed'               => esc_html__( 'Resend failed!', 'wp-mail-smtp-pro' ),
				'ses_text_cancel'                      => esc_html__( 'Cancel', 'wp-mail-smtp-pro' ),
				'ses_text_close'                       => esc_html__( 'Close', 'wp-mail-smtp-pro' ),
				'ses_text_yes'                         => esc_html__( 'Yes', 'wp-mail-smtp-pro' ),
				'ses_text_done'                        => esc_html__( 'Done', 'wp-mail-smtp-pro' ),
				'ses_text_domain_delete'               => esc_html__( 'Are you sure you want to delete this domain? You will need to add and verify it again if you want to use it in the future.', 'wp-mail-smtp-pro' ),
				'ses_text_dns_dkim_title'              => esc_html__( 'Add verified domain', 'wp-mail-smtp-pro' ),
				'ses_text_no_identities'               => esc_html__( 'The AWS SES identities could not load because of an error.', 'wp-mail-smtp-pro' ),
				'ses_add_identity_modal_content'       => SESOptions::prepare_add_new_identity_content(),
				'ses_add_identity_modal_title'         => SESOptions::prepare_add_new_identity_title(),
				'loader_white_small'                   => wp_mail_smtp()->prepare_loader( 'white', 'sm' ),
				'nonce'                                => wp_create_nonce( 'wp-mail-smtp-pro-admin' ),
				'text_heads_up_title'                  => esc_html__( 'Heads up!', 'wp-mail-smtp-pro' ),
				'text_yes'                             => esc_html__( 'Yes', 'wp-mail-smtp-pro' ),
				'text_yes_delete'                      => esc_html__( 'Yes, Delete', 'wp-mail-smtp-pro' ),
				'text_cancel'                          => esc_html__( 'Cancel', 'wp-mail-smtp-pro' ),
				'text_delete_connection'               => esc_html__( 'You\'re about to delete a connection. Are you sure you want to proceed?', 'wp-mail-smtp-pro' ),
				'text_delete_backup_connection'        => esc_html__( 'You\'re about to delete your Backup Connection. Are you sure you want to proceed?', 'wp-mail-smtp-pro' ),
				'text_delete_smart_routing_connection' => esc_html__( 'You\'re about to delete a connection that is used in Smart Routing. Are you sure you want to proceed? You will need to reconfigure your Smart Routing rules.', 'wp-mail-smtp-pro' ),
				'text_switch_witch_primary_connection' => esc_html__( 'Are you sure you want to use this additional connection as your new primary connection? Your existing primary connection will be moved to the additional connections list.', 'wp-mail-smtp-pro' ),
				'connection_id'                        => $connection_id,
				// WhatsApp alerts data.
				'whatsapp_text_checking'               => esc_html__( 'Rechecking...', 'wp-mail-smtp-pro' ),
				'whatsapp_text_check_error'            => esc_html__( 'Error rechecking status', 'wp-mail-smtp-pro' ),
				'whatsapp_text_recheck'                => esc_html__( 'Recheck Status', 'wp-mail-smtp-pro' ),
			]
		);
	}

	/**
	 * Process AJAX requests fired by a pro version of a plugin and related to providers.
	 * Currently, only AmazonSES has some AJAX.
	 * So we will hard-code this behavior for now.
	 *
	 * @since 1.5.0
	 * @since 2.4.0 Major changes (AWS SDK and domain verification changes).
	 */
	public function process_ajax() {

		$generic_error = esc_html__( 'Something went wrong. Please try again later.', 'wp-mail-smtp-pro' );

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_global_options() ) ) {
			wp_send_json_error( esc_html__( 'You don\'t have the permission to perform this action.', 'wp-mail-smtp-pro' ) );
		}

		// Verify nonce existence. Actual nonce verification happens below.
		if ( ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( $generic_error );
		}

		$mailer = isset( $_POST['mailer'] ) ? sanitize_key( $_POST['mailer'] ) : '';

		if ( $mailer !== 'amazonses' ) {
			wp_send_json_error( $generic_error );
		}

		$connection_id = isset( $_POST['connection_id'] ) ? sanitize_key( $_POST['connection_id'] ) : false;
		$task          = isset( $_POST['task'] ) ? sanitize_key( $_POST['task'] ) : '';

		$connection = wp_mail_smtp()->get_connections_manager()->get_connection( $connection_id, false );

		if ( $connection === false ) {
			wp_send_json_error( $generic_error );
		}

		switch ( $task ) {
			case 'load_ses_identities':
				if ( ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wp_mail_smtp_pro_amazonses_load_ses_identities' ) ) {
					wp_send_json_error( $generic_error );
				}

				$ses_options = wp_mail_smtp()->get_providers()->get_options( SESOptions::SLUG, $connection );

				wp_send_json_success( $ses_options->prepare_ses_identities_content() );

				break;

			case 'identity_registration':
				if ( ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wp_mail_smtp_pro_amazonses_register_identity' ) ) {
					wp_send_json_error( $generic_error );
				}

				$type  = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
				$value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';

				if ( $type === 'email' && ! is_email( $value ) ) {
					wp_send_json_error( esc_html__( 'Please provide a valid email address.', 'wp-mail-smtp-pro' ) );
				} elseif ( $type === 'domain' && empty( $value ) ) {
					wp_send_json_error( esc_html__( 'Please provide a domain name.', 'wp-mail-smtp-pro' ) );
				}

				$ses = new SESAuth( $connection );

				// Verify domain for easier conditional checking below.
				$domain_dkim_tokens = ( $type === 'domain' ) ? $ses->do_verify_domain_dkim( $value ) : '';

				if ( $type === 'email' && $ses->do_verify_email( $value ) === true ) {
					wp_send_json_success(
						sprintf(
							wp_kses( /* translators: %s - email address. */
								__( 'Please check inbox of <code>%s</code> address for a verification email.', 'wp-mail-smtp-pro' ),
								[ 'code' => [] ]
							),
							esc_html( $value )
						)
					);
				} elseif ( $type === 'domain' && ! empty( $domain_dkim_tokens ) ) {
					wp_send_json_success(
						SESOptions::prepare_domain_dkim_records_notice( $value, $domain_dkim_tokens, $connection )
					);
				} else {
					$error = $ses->get_last_error();

					wp_send_json_error(
						$error instanceof WP_Error ? esc_html( $error->get_error_message() ) : $generic_error
					);
				}

				break;

			case 'identity_delete':
				if ( ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wp_mail_smtp_pro_amazonses_identity_delete' ) ) {
					wp_send_json_error( $generic_error );
				}

				$type  = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
				$value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';

				if ( $type === 'email' && ! is_email( $value ) ) {
					wp_send_json_error( esc_html__( 'Please provide a valid email address.', 'wp-mail-smtp-pro' ) );
				} elseif ( $type === 'domain' && empty( $value ) ) {
					wp_send_json_error( esc_html__( 'Please provide a domain name.', 'wp-mail-smtp-pro' ) );
				}

				$ses = new SESAuth( $connection );

				if ( $ses->do_delete_identity( $value ) === true ) {
					wp_send_json_success(
						sprintf(
							wp_kses(
								( $type === 'email' ) ?
									/* translators: %s - actual email address. */
									__( 'Email address <code>%s</code> was successfully deleted.', 'wp-mail-smtp-pro' ) :
									/* translators: %s - actual domain name. */
									__( 'Domain <code>%s</code> was successfully deleted.', 'wp-mail-smtp-pro' ),
								[ 'code' => [] ]
							),
							esc_html( $value )
						)
					);
				} else {
					$error = $ses->get_last_error();

					wp_send_json_error(
						$error instanceof WP_Error ? esc_html( $error->get_error_message() ) : $generic_error
					);
				}

				break;

			case 'load_dns_records':
				if ( ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wp_mail_smtp_pro_amazonses_load_dns_records' ) ) {
					wp_send_json_error( $generic_error );
				}

				$domain = isset( $_POST['domain'] ) ? sanitize_text_field( wp_unslash( $_POST['domain'] ) ) : '';

				if ( empty( $domain ) ) {
					wp_send_json_error( esc_html__( 'Please provide a domain name.', 'wp-mail-smtp-pro' ) );
				}

				$ses = new SESAuth( $connection );

				$dkim_tokens = $ses->get_dkim_tokens( $domain );

				if ( is_wp_error( $dkim_tokens ) ) {
					wp_send_json_error( esc_html( $dkim_tokens->get_error_message() ) );
				}

				wp_send_json_success( SESOptions::prepare_domain_dkim_records_notice( $domain, $dkim_tokens, $connection ) );

				break;
		}
	}

	/**
	 * Update PHPMailer properties before email send.
	 *
	 * @since 3.5.0
	 *
	 * @param MailCatcherInterface $phpmailer The MailCatcher object.
	 */
	public function update_php_mailer_properties( $phpmailer ) {

		$mailer = wp_mail_smtp()->get_connections_manager()->get_mail_connection()->get_mailer_slug();

		/*
		 * Switch mailer to "sendmail" for Amazon SES. Since we send MIME message via Amazon API,
		 * we should avoid "mail" mailer limitations. Namely, long headers encoding.
		 */
		if ( $mailer === 'amazonses' ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$phpmailer->Mailer = 'sendmail';
		}
	}

	/**
	 * Display providers related notices.
	 *
	 * @since 3.10.0
	 */
	public function display_notices() {

		if ( ! wp_mail_smtp()->get_admin()->is_admin_page() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$message = isset( $_GET['message'] ) ? sanitize_key( $_GET['message'] ) : '';

		switch ( $message ) {
			case 'additional_connection_switched_with_primary':
				WP::add_admin_notice(
					esc_html__( 'The additional connection has been successfully switched to the primary connection.', 'wp-mail-smtp-pro' ),
					WP::ADMIN_NOTICE_SUCCESS
				);
		}
	}
}
