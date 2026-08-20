<?php

namespace WPMailSMTP\Pro\Alerts\Providers\WhatsApp;

use WPMailSMTP\Admin\DebugEvents\DebugEvents;
use WPMailSMTP\Options;
use WPMailSMTP\Pro\Alerts\Alert;
use WPMailSMTP\Pro\Alerts\Alerts;
use WPMailSMTP\Pro\Alerts\Handlers\HandlerInterface;
use WPMailSMTP\WP;

/**
 * Class Handler. WhatsApp alerts.
 *
 * @since 4.5.0
 */
class Handler implements HandlerInterface {

	/**
	 * WhatsApp Cloud API base URL.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	const API_URL = 'https://graph.facebook.com/v22.0/';

	/**
	 * WhatsApp message type for template messages.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	const MESSAGE_TYPE = 'template';

	/**
	 * WhatsApp template name for alerts.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	const TEMPLATE_NAME = 'wp_mail_smtp_alert';

	/**
	 * Transient name for caching template status.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	const TEMPLATE_STATUS_TRANSIENT = 'wp_mail_smtp_whatsapp_template_status';

	/**
	 * Whether current handler can handle provided alert.
	 *
	 * @since 4.5.0
	 *
	 * @param Alert $alert Alert object.
	 *
	 * @return bool
	 */
	public function can_handle( Alert $alert ) {

		return in_array(
			$alert->get_type(),
			[
				Alerts::FAILED_EMAIL,
				Alerts::FAILED_PRIMARY_EMAIL,
				Alerts::FAILED_BACKUP_EMAIL,
				Alerts::HARD_BOUNCED_EMAIL,
			],
			true
		);
	}

	/**
	 * Handle alert.
	 * Send alert notification via WhatsApp Cloud API.
	 *
	 * @since 4.5.0
	 *
	 * @param Alert $alert Alert object.
	 *
	 * @return bool
	 */
	public function handle( Alert $alert ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$connections = (array) Options::init()->get( 'alert_whatsapp', 'connections' );
		$connections = array_unique(
			array_filter(
				$connections,
				function ( $connection ) {
					return ! empty( $connection['access_token'] ) &&
								 ! empty( $connection['phone_number_id'] ) &&
								 ! empty( $connection['to_phone_number'] ) &&
								 ! empty( $connection['whatsapp_business_id'] );
				}
			),
			SORT_REGULAR
		);

		if ( empty( $connections ) ) {
			return false;
		}

		$result = false;
		$errors = [];

		foreach ( $connections as $connection ) {
			// Check template status before sending - don't send if template is not approved.
			$template_status = $this->check_template_status( $connection );

			if ( empty( $template_status['exists'] ) || $template_status['status'] !== 'APPROVED' ) {
				// Template doesn't exist or is not approved.
				if ( empty( $template_status['exists'] ) ) {
					// Template not found.
					$errors[] = esc_html__( 'WhatsApp alert not sent: Template not found.', 'wp-mail-smtp-pro' );
				} else {
					// Template exists but not approved.
					$status_msg = sprintf(
					/* translators: %s - Template status (e.g., PENDING, REJECTED). */
						esc_html__( 'Template status is %s.', 'wp-mail-smtp-pro' ),
						$template_status['status']
					);
					$errors[] = esc_html__( 'WhatsApp alert not sent: ', 'wp-mail-smtp-pro' ) . $status_msg;
				}

				// Skip to the next connection.
				continue;
			}

			// Template is approved, proceed with sending the alert.
			$access_token    = $connection['access_token'];
			$phone_number_id = $connection['phone_number_id'];
			$to_phone_number = $connection['to_phone_number'];

			// Format to WhatsApp number format (remove any non-numeric characters).
			$to_phone_number = preg_replace( '/[^0-9]/', '', $to_phone_number );

			$message_data = $this->get_message_data( $alert, $to_phone_number, $connection );

			$args = [
				'timeout' => MINUTE_IN_SECONDS,
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $access_token,
				],
				'body'    => wp_json_encode( $message_data ),
			];

			/**
			 * Filters WhatsApp API request arguments.
			 *
			 * @since 4.5.0
			 *
			 * @param array $args       WhatsApp API request arguments.
			 * @param array $connection Connection settings.
			 * @param Alert $alert      Alert object.
			 */
			$args = apply_filters( 'wp_mail_smtp_pro_alerts_providers_whats_app_handler_handle_request_args', $args, $connection, $alert );

			$endpoint      = self::API_URL . $phone_number_id . '/messages';
			$response      = wp_remote_post( $endpoint, $args );
			$response_code = wp_remote_retrieve_response_code( $response );

			// 200 OK response means success.
			if ( $response_code === 200 ) {
				$result = true;
			} else {
				$errors[] = WP::wp_remote_get_response_error_message( $response );
			}
		}

		DebugEvents::add_debug( esc_html__( 'WhatsApp alert request was sent.', 'wp-mail-smtp-pro' ) );

		if ( ! empty( $errors ) && DebugEvents::is_debug_enabled() ) {
			DebugEvents::add( esc_html__( 'Alert: WhatsApp.', 'wp-mail-smtp-pro' ) . WP::EOL . implode( WP::EOL, array_unique( $errors ) ) );
		}

		return $result;
	}

	/**
	 * Build message data for WhatsApp Cloud API.
	 *
	 * @since 4.5.0
	 *
	 * @link  https://developers.facebook.com/docs/whatsapp/cloud-api/reference/messages
	 *
	 * @param Alert  $alert           Alert object.
	 * @param string $to_phone_number Recipient phone number.
	 * @param array  $connection      Connection settings.
	 *
	 * @return array
	 */
	private function get_message_data( Alert $alert, $to_phone_number, $connection = [] ) {

		$data          = $alert->get_data();
		$settings_link = wp_mail_smtp()->get_admin()->get_admin_page_url();
		$language_code = $this->get_language_code( $connection );

		// Build message for template parameters.
		$template_components = [
			'components' => [
				[
					'type'       => 'body',
					'parameters' => [
						[
							'type' => 'text',
							'text' => isset( $data['website_url'] ) ? esc_url_raw( $data['website_url'] ) : site_url(), // Website URL parameter.
						],
						[
							'type' => 'text',
							'text' => isset( $data['to_email_addresses'] ) ? $data['to_email_addresses'] : esc_html__( 'Unknown recipient', 'wp-mail-smtp-pro' ), // Recipient parameter.
						],
						[
							'type' => 'text',
							'text' => $data['subject'], // Subject parameter.
						],
						[
							'type' => 'text',
							'text' => ! empty( $data['error_message'] ) ? sanitize_text_field( $data['error_message'] ) : esc_html__( 'No error message provided', 'wp-mail-smtp-pro' ), // Error message parameter.
						],
						[
							'type' => 'text',
							'text' => esc_url_raw( $settings_link ), // Settings URL parameter.
						],
					],
				],
			],
		];

		// Prepare template message data.
		$message_data = [
			'messaging_product' => 'whatsapp',
			'to'                => $to_phone_number,
			'type'              => self::MESSAGE_TYPE,
			'template'          => array_merge(
				[
					'name'     => $this->get_template_name(),
					'language' => [
						'code' => $language_code,
					],
				],
				$template_components
			),
		];

		return $message_data;
	}

	/**
	 * Check template status for a WhatsApp connection.
	 *
	 * @since 4.5.0
	 *
	 * @param array $connection Connection settings with access_token and phone_number_id.
	 * @param bool  $force      Whether to force a fresh check bypassing cache. Default false.
	 *
	 * @return array Template status information.
	 */
	public function check_template_status( $connection, $force = false ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.CyclomaticComplexity.MaxExceeded

		$status = [
			'exists' => false,
			'status' => '',
			'error'  => '',
		];

		if ( empty( $connection['access_token'] ) || empty( $connection['phone_number_id'] ) || empty( $connection['whatsapp_business_id'] ) ) {
			$status['error'] = esc_html__( 'Missing connection details', 'wp-mail-smtp-pro' );

			return $status;
		}

		$transient_key = $this->get_template_status_cache_key( $connection );

		// Check if we have a cached result and we're not forcing a fresh check.
		if ( ! $force ) {
			$cached_status = get_transient( $transient_key );

			if ( $cached_status !== false ) {
				return $cached_status;
			}
		}

		$waba_id = $connection['whatsapp_business_id'];

		// Direct check for template status using the WABA ID.
		$api_url = self::API_URL . $waba_id . '/message_templates?fields=name,status,language&name=' . $this->get_template_name();

		$response = wp_remote_get(
			$api_url,
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $connection['access_token'],
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			$status['error'] = $response->get_error_message();

			DebugEvents::add(
				esc_html__( 'Alert: WhatsApp.', 'wp-mail-smtp-pro' ) . WP::EOL .
				sprintf(
					/* translators: %s: Error message from WhatsApp API request. */
					esc_html__( 'WhatsApp template status check failed: %s', 'wp-mail-smtp-pro' ),
					$response->get_error_message()
				)
			);

			return $status;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code !== 200 ) {
			$status['error'] = WP::wp_remote_get_response_error_message( $response );

			DebugEvents::add(
				esc_html__( 'Alert: WhatsApp.', 'wp-mail-smtp-pro' ) . WP::EOL .
				sprintf(
					/* translators: %s: Error message from WhatsApp API request. */
					esc_html__( 'WhatsApp template status check failed: %s', 'wp-mail-smtp-pro' ),
					$status['error']
				)
			);

			return $status;
		}

		$template_data     = json_decode( wp_remote_retrieve_body( $response ), true );
		$cache_duration    = 2 * MINUTE_IN_SECONDS;
		$expected_language = $this->get_language_code( $connection );

		// Check for our template in the retrieved data.
		if ( ! empty( $template_data['data'] ) && is_array( $template_data['data'] ) ) {
			$status = $this->find_template_by_language( $template_data['data'], $expected_language, $status );
		}

		// If no template exists for the current language, create one.
		if ( ! $status['exists'] ) {
			$response = $this->create_template( $connection );

			if ( $response['success'] ) {
				$status['exists'] = true;
				$status['status'] = $response['template']['status'];
			} else {
				$status['exists'] = false;
				$status['status'] = 'NOT_FOUND';
			}

			set_transient( $transient_key, $status, $cache_duration );

			return $status;
		}

		// Use longer cache duration (1 hour) for all statuses except pending ones.
		if ( $status['exists'] && $status['status'] !== 'PENDING' && $status['status'] !== 'IN_APPEAL' ) {
			$cache_duration = HOUR_IN_SECONDS;
		}

		set_transient( $transient_key, $status, $cache_duration );

		return $status;
	}

	/**
	 * Create a WhatsApp message template for alerts.
	 *
	 * When a template is successfully created, the template_language should be saved
	 * to the connection settings to ensure all future API calls use the same language.
	 *
	 * @since 4.5.0
	 *
	 * @param array $connection Connection settings with access_token and whatsapp_business_id.
	 *
	 * @return array Template creation response with success status and details.
	 */
	public function create_template( $connection ) {

		// Check if we have the required fields.
		if ( empty( $connection['access_token'] ) || empty( $connection['whatsapp_business_id'] ) ) {
			return [
				'success' => false,
				'error'   => esc_html__( 'Missing connection details', 'wp-mail-smtp-pro' ),
			];
		}

		/**
		 * Filter the template data before creating a WhatsApp template.
		 *
		 * @since 4.5.0
		 *
		 * @param array $template_data WhatsApp template data.
		 */
		$template_data = apply_filters(
			'wp_mail_smtp_pro_alerts_providers_whats_app_handler_template_data',
			[
				'name'       => $this->get_template_name(),
				'category'   => 'UTILITY',
				'language'   => $this->get_language_code( $connection ),
				'components' => [
					[
						'type'   => 'HEADER',
						'format' => 'TEXT',
						'text'   => esc_html__( 'Your Site Failed to Send an Email', 'wp-mail-smtp-pro' ),
					],
					[
						'type'    => 'BODY',
						'text'    => '*' . esc_html__( 'Website', 'wp-mail-smtp-pro' ) . ':* {{1}}' . "\n\n" .
												 '*' . esc_html__( 'To email', 'wp-mail-smtp-pro' ) . ':* {{2}}' . "\n\n" .
												 '*' . esc_html__( 'Subject', 'wp-mail-smtp-pro' ) . ':* {{3}}' . "\n\n" .
												 '*' . esc_html__( 'Error message', 'wp-mail-smtp-pro' ) . ':* {{4}}' . "\n\n" .
												 '*' . esc_html__( 'Check your WP Mail SMTP settings', 'wp-mail-smtp-pro' ) . ':* {{5}}' . "\n\n" .
												 esc_html__( 'Need more help? Read our troubleshooting guide', 'wp-mail-smtp-pro' ) . ': https://wpmailsmtp.com/docs/troubleshooting',
						'example' => [
							'body_text' => [
								[
									'https://example.com',
									'pattie@example.com',
									'WP Mail SMTP Alerts Test',
									'This is a test error message triggered from the WP Mail SMTP Alerts settings',
									'https://example.com/wp-admin/admin.php?page=wp-mail-smtp',
								],
							],
						],
					],
					[
						'type' => 'FOOTER',
						'text' => esc_html__( 'Powered by WP Mail SMTP', 'wp-mail-smtp-pro' ),
					],
				],
			]
		);

		// API endpoint for creating templates.
		$waba_id = $connection['whatsapp_business_id'];
		$api_url = self::API_URL . $waba_id . '/message_templates';

		// Make the API request.
		$response = wp_remote_post(
			$api_url,
			[
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $connection['access_token'],
				],
				'body'    => wp_json_encode( $template_data ),
				'timeout' => MINUTE_IN_SECONDS,
			]
		);

		// Handle the response.
		if ( is_wp_error( $response ) ) {
			DebugEvents::add(
				esc_html__( 'Alert: WhatsApp.', 'wp-mail-smtp-pro' ) . WP::EOL .
				sprintf(
					/* translators: %s: Error message from WhatsApp API request. */
					esc_html__( 'WhatsApp template creation failed: %s', 'wp-mail-smtp-pro' ),
					$response->get_error_message()
				)
			);

			return [
				'success' => false,
				'error'   => $response->get_error_message(),
			];
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $response_code !== 200 ) {
			$error_message = WP::wp_remote_get_response_error_message( $response );

			DebugEvents::add(
				esc_html__( 'Alert: WhatsApp.', 'wp-mail-smtp-pro' ) . WP::EOL .
				sprintf(
					/* translators: %s: Error message from WhatsApp API request. */
					esc_html__( 'WhatsApp template creation failed: %s', 'wp-mail-smtp-pro' ),
					$error_message
				)
			);

			return [
				'success' => false,
				'error'   => $error_message,
				'code'    => $response_code,
			];
		}

		delete_transient( $this->get_template_status_cache_key( $connection ) );

		return [
			'success'           => true,
			'template'          => $response_body,
			'template_language' => $this->get_language_code( $connection ),
		];
	}

	/**
	 * Find template by language in the retrieved template data.
	 *
	 * @since 4.5.0
	 *
	 * @param array  $templates_data    Array of template data from API.
	 * @param string $expected_language Expected language code.
	 * @param array  $status            Current status array to update.
	 *
	 * @return array Updated status array.
	 */
	private function find_template_by_language( $templates_data, $expected_language, $status ) {

		foreach ( $templates_data as $template ) {
			if ( isset( $template['name'] ) && $template['name'] === $this->get_template_name() ) {
				// Check if language matches (if language field is available).
				$template_language = isset( $template['language'] ) ? $template['language'] : $expected_language;

				if ( $template_language === $expected_language ) {
					$status['exists'] = true;
					$status['status'] = $template['status'];

					break;
				}
			}
		}

		return $status;
	}

	/**
	 * Get the template name with filter applied.
	 *
	 * @since 4.5.0
	 *
	 * @return string Template name.
	 */
	private function get_template_name() {

		/**
		 * Filter the WhatsApp template name.
		 *
		 * @since 4.5.0
		 *
		 * @param string $template_name The template name to use for WhatsApp alerts.
		 */
		return apply_filters( 'wp_mail_smtp_pro_alerts_providers_whats_app_handler_template_name', self::TEMPLATE_NAME );
	}

	/**
	 * Get the cache key for the template status.
	 *
	 * @since 4.5.0
	 *
	 * @param array $connection Connection settings.
	 *
	 * @return string Cache key.
	 */
	private function get_template_status_cache_key( $connection ) {

		$cache_key = $connection['whatsapp_business_id'] . '_' . $this->get_language_code( $connection );

		return self::TEMPLATE_STATUS_TRANSIENT . '_' . $cache_key;
	}

	/**
	 * Get the language code for the template.
	 *
	 * @since 4.5.0
	 *
	 * @param array $connection Optional. Connection settings to get stored language from.
	 *
	 * @return string Language code.
	 */
	private function get_language_code( $connection = [] ) {

		// If we have a stored template language in the connection, use it.
		if ( ! empty( $connection['template_language'] ) ) {
			return $connection['template_language'];
		}

		// Otherwise, determine language from user locale (for initial setup).
		$wp_locale     = get_user_locale();
		$language_code = 'en_US';

		$language_mapper = [
			'af'     => 'af',
			'sq'     => 'sq',
			'ar'     => 'ar',
			'ar_EG'  => 'ar_EG',
			'ar_AE'  => 'ar_AE',
			'ar_LB'  => 'ar_LB',
			'ar_MA'  => 'ar_MA',
			'ar_QA'  => 'ar_QA',
			'az'     => 'az',
			'be_BY'  => 'be_BY',
			'bn'     => 'bn',
			'bn_IN'  => 'bn_IN',
			'bg'     => 'bg',
			'ca'     => 'ca',
			'zh_CN'  => 'zh_CN',
			'zh_HK'  => 'zh_HK',
			'zh_TW'  => 'zh_TW',
			'hr'     => 'hr',
			'cs'     => 'cs',
			'da'     => 'da',
			'prs_AF' => 'prs_AF',
			'nl'     => 'nl',
			'nl_BE'  => 'nl_BE',
			'en'     => 'en',
			'en_GB'  => 'en_GB',
			'en_US'  => 'en_US',
			'en_AE'  => 'en_AE',
			'en_AU'  => 'en_AU',
			'en_CA'  => 'en_CA',
			'en_GH'  => 'en_GH',
			'en_IE'  => 'en_IE',
			'en_IN'  => 'en_IN',
			'en_JM'  => 'en_JM',
			'en_MY'  => 'en_MY',
			'en_NZ'  => 'en_NZ',
			'en_QA'  => 'en_QA',
			'en_SG'  => 'en_SG',
			'en_UG'  => 'en_UG',
			'en_ZA'  => 'en_ZA',
			'et'     => 'et',
			'fil'    => 'fil',
			'fi'     => 'fi',
			'fr'     => 'fr',
			'fr_BE'  => 'fr_BE',
			'fr_CA'  => 'fr_CA',
			'fr_CH'  => 'fr_CH',
			'fr_CI'  => 'fr_CI',
			'fr_MA'  => 'fr_MA',
			'ka'     => 'ka',
			'de'     => 'de',
			'de_AT'  => 'de_AT',
			'de_CH'  => 'de_CH',
			'el'     => 'el',
			'gu'     => 'gu',
			'ha'     => 'ha',
			'he'     => 'he',
			'hi'     => 'hi',
			'hu'     => 'hu',
			'id'     => 'id',
			'ga'     => 'ga',
			'it'     => 'it',
			'ja'     => 'ja',
			'kn'     => 'kn',
			'kk'     => 'kk',
			'rw_RW'  => 'rw_RW',
			'ko'     => 'ko',
			'ky_KG'  => 'ky_KG',
			'lo'     => 'lo',
			'lv'     => 'lv',
			'lt'     => 'lt',
			'mk'     => 'mk',
			'ms'     => 'ms',
			'ml'     => 'ml',
			'mr'     => 'mr',
			'nb'     => 'nb',
			'ps_AF'  => 'ps_AF',
			'fa'     => 'fa',
			'pl'     => 'pl',
			'pt_BR'  => 'pt_BR',
			'pt_PT'  => 'pt_PT',
			'pa'     => 'pa',
			'ro'     => 'ro',
			'ru'     => 'ru',
			'sr'     => 'sr',
			'si_LK'  => 'si_LK',
			'sk'     => 'sk',
			'sl'     => 'sl',
			'es'     => 'es',
			'es_AR'  => 'es_AR',
			'es_CL'  => 'es_CL',
			'es_CO'  => 'es_CO',
			'es_CR'  => 'es_CR',
			'es_DO'  => 'es_DO',
			'es_EC'  => 'es_EC',
			'es_HN'  => 'es_HN',
			'es_MX'  => 'es_MX',
			'es_PA'  => 'es_PA',
			'es_PE'  => 'es_PE',
			'es_ES'  => 'es_ES',
			'es_UY'  => 'es_UY',
			'sw'     => 'sw',
			'sv'     => 'sv',
			'ta'     => 'ta',
			'te'     => 'te',
			'th'     => 'th',
			'tr'     => 'tr',
			'uk'     => 'uk',
			'ur'     => 'ur',
			'uz'     => 'uz',
			'vi'     => 'vi',
			'zu'     => 'zu',
			'ar_SA'  => 'ar',
			'az_AZ'  => 'az',
			'bg_BG'  => 'bg',
			'bn_BD'  => 'bn',
			'bs_BA'  => 'sr',
			'ca_ES'  => 'ca',
			'cs_CZ'  => 'cs',
			'da_DK'  => 'da',
			'de_DE'  => 'de',
			'el_GR'  => 'el',
			'fa_IR'  => 'fa',
			'fi_FI'  => 'fi',
			'fr_FR'  => 'fr',
			'gu_IN'  => 'gu',
			'he_IL'  => 'he',
			'hi_IN'  => 'hi',
			'hr_HR'  => 'hr',
			'hu_HU'  => 'hu',
			'id_ID'  => 'id',
			'is_IS'  => 'en',
			'it_IT'  => 'it',
			'ja_JP'  => 'ja',
			'ko_KR'  => 'ko',
			'lt_LT'  => 'lt',
			'lv_LV'  => 'lv',
			'mk_MK'  => 'mk',
			'mr_IN'  => 'mr',
			'ms_MY'  => 'ms',
			'nb_NO'  => 'nb',
			'nl_NL'  => 'nl',
			'nn_NO'  => 'nb',
			'pl_PL'  => 'pl',
			'ro_RO'  => 'ro',
			'ru_RU'  => 'ru',
			'sk_SK'  => 'sk',
			'sl_SI'  => 'sl',
			'sq_AL'  => 'sq',
			'sr_RS'  => 'sr',
			'sv_SE'  => 'sv',
			'ta_IN'  => 'ta',
			'th_TH'  => 'th',
			'tr_TR'  => 'tr',
			'uk_UA'  => 'uk',
			'vi_VN'  => 'vi',
		];

		if ( isset( $language_mapper[ $wp_locale ] ) ) {
			$language_code = $language_mapper[ $wp_locale ];
		} else {
			$language_part = substr( $wp_locale, 0, 2 );

			if ( isset( $language_mapper[ $language_part ] ) ) {
				$language_code = $language_mapper[ $language_part ];
			}
		}

		/**
		 * Filter the language code used for WhatsApp templates.
		 *
		 * @since 4.5.0
		 *
		 * @param string $language_code Language code to use for the WhatsApp template.
		 * @param string $wp_locale     Current WordPress locale.
		 */
		return apply_filters( 'wp_mail_smtp_pro_alerts_providers_whats_app_handler_language_code', $language_code, $wp_locale );
	}

	/**
	 * Get the user's template language for new connections.
	 *
	 * This method is used when creating new WhatsApp connections to determine
	 * what language should be stored for the template.
	 *
	 * @since 4.5.0
	 *
	 * @return string Language code based on current user locale.
	 */
	public function get_user_template_language() {

		// Use the same logic as get_language_code but without connection parameter.
		return $this->get_language_code();
	}
}
