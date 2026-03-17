<?php

declare(strict_types=1);

namespace BuddyBossTheme\Admin\Mothership;

use BuddyBossTheme\GroundLevel\Mothership\Manager\LicenseManager;
use BuddyBossTheme\GroundLevel\Mothership\Service as MothershipService;
use BuddyBossTheme\GroundLevel\Mothership\Credentials;
use BuddyBossTheme\GroundLevel\Mothership\Api\Response;
use BuddyBossTheme\GroundLevel\Mothership\Api\Request\LicenseActivations;
use BuddyBossTheme\GroundLevel\Mothership\AbstractPluginConnection;

/**
 * BuddyBoss License Manager extends the base LicenseManager
 * to add dynamic plugin ID functionality.
 */
class BB_Theme_License_Manager extends LicenseManager {

	/**
	 * Flag to control when the HTTP filter is active.
	 * Only set to true during actual license operations to minimize performance impact.
	 *
	 * @var bool
	 */
	private static $capture_headers_enabled = false;

	/**
	 * Enable HTTP header capture for the next license operation.
	 * Call this before making license API calls.
	 *
	 * @return void
	 */
	private static function enable_header_capture(): void {
		if ( ! self::$capture_headers_enabled ) {
			self::$capture_headers_enabled = true;
			add_filter( 'http_response', array( __CLASS__, 'capture_api_headers' ), 10, 3 );
		}
	}

	/**
	 * Disable HTTP header capture after license operation completes.
	 * Call this after making license API calls.
	 *
	 * @return void
	 */
	private static function disable_header_capture(): void {
		if ( self::$capture_headers_enabled ) {
			self::$capture_headers_enabled = false;
			remove_filter( 'http_response', array( __CLASS__, 'capture_api_headers' ), 10 );
		}
	}

	/**
	 * Get the API base URL for license operations.
	 * Environment-aware with multiple fallback options.
	 *
	 * @param string $plugin_id The plugin ID for product-specific constants.
	 *
	 * @return string The API base URL.
	 */
	private static function get_api_base_url( string $plugin_id = '' ): string {
		// Priority 1: Product-specific constant (e.g., BUDDYBOSS_THEME_MOTHERSHIP_API_BASE_URL).
		if ( ! empty( $plugin_id ) ) {
			$constant_name = strtoupper( str_replace( '-', '_', $plugin_id ) . '_MOTHERSHIP_API_BASE_URL' );
			if ( defined( $constant_name ) ) {
				return constant( $constant_name );
			}
		}

		// Priority 2: Generic BuddyBoss constant.
		if ( defined( 'BUDDYBOSS_MOTHERSHIP_API_BASE_URL' ) ) {
			return BUDDYBOSS_MOTHERSHIP_API_BASE_URL;
		}

		// Priority 3: Environment variable (useful for Docker/containerized environments).
		$env_api_url = getenv( 'BUDDYBOSS_API_URL' );
		if ( false !== $env_api_url && ! empty( $env_api_url ) ) {
			return trailingslashit( $env_api_url );
		}

		// Priority 4: WordPress option (for runtime configuration).
		$option_api_url = get_option( 'buddyboss_api_base_url' );
		if ( ! empty( $option_api_url ) ) {
			return trailingslashit( $option_api_url );
		}

		// Priority 5: Filter (allows plugins/themes to override).
		$default_url  = 'https://licenses.caseproof.com/api/v1/';
		$filtered_url = apply_filters( 'buddyboss_mothership_api_base_url', $default_url, $plugin_id );

		return $filtered_url;
	}

	/**
	 * Initialize hooks to capture API response headers.
	 * Should be called early in the WordPress lifecycle.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Hook is now registered dynamically during license operations only.
		// This init method kept for backward compatibility but doesn't register global hooks.
	}

	/**
	 * Capture rate limit headers from Caseproof API responses.
	 * Only runs when explicitly enabled during license operations.
	 *
	 * @param array  $response HTTP response.
	 * @param array  $args     HTTP request arguments.
	 * @param string $url      The request URL.
	 *
	 * @return array Unmodified response (we only read headers).
	 */
	public static function capture_api_headers( $response, $args, $url ) {
		// Only process responses from the Caseproof license API.
		if ( false === strpos( $url, 'licenses.caseproof.com' ) ) {
			return $response;
		}

		// Skip if response is an error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Check for Cloudflare errors using multiple detection signals.
		$body        = wp_remote_retrieve_body( $response );
		$status_code = wp_remote_retrieve_response_code( $response );

		if ( ! empty( $body ) ) {
			$body_lower = strtolower( $body );

			// Signal 1: HTTP status code 429 or 503.
			$is_rate_limit_status = in_array( $status_code, array( 429, 503 ), true );

			// Signal 2: Cloudflare error codes (case-insensitive).
			$has_cloudflare_error = (
				false !== strpos( $body_lower, 'error 1015' ) ||
				false !== strpos( $body_lower, 'error 1014' ) ||
				false !== strpos( $body_lower, 'error 1020' )
			);

			// Signal 3: Rate limit keywords (case-insensitive).
			$has_rate_limit_text = (
				false !== strpos( $body_lower, 'rate limit' ) ||
				false !== strpos( $body_lower, 'too many requests' )
			);

			// Signal 4: Cloudflare branding.
			$is_cloudflare = false !== strpos( $body_lower, 'cloudflare' );

			// Detect Cloudflare rate limit with multiple signal validation.
			$cloudflare_detected = (
				$has_cloudflare_error ||
				( $is_rate_limit_status && $is_cloudflare ) ||
				( $has_rate_limit_text && $is_cloudflare )
			);

			if ( $cloudflare_detected ) {
				// Cloudflare rate limit detected - store extended block (60 minutes).
				error_log( 'BuddyBoss Theme: Cloudflare rate limit detected - blocking for 60 minutes' );

				$reset_time      = time() + 3600; // 60 minutes.
				$rate_limit_data = array(
					'limit'     => null,
					'remaining' => 0,
					'reset'     => $reset_time,
					'source'    => 'cloudflare_detected',
					'timestamp' => time(),
				);

				self::set_rate_limit_transient(
					'rate_limit',
					$rate_limit_data,
					3660 // 61 minutes.
				);

				return $response;
			}
		}

		// Extract headers.
		$headers = wp_remote_retrieve_headers( $response );

		if ( empty( $headers ) ) {
			return $response;
		}

		// Convert headers to array format (WP_HTTP_Requests_Response may return object).
		if ( is_object( $headers ) && method_exists( $headers, 'getAll' ) ) {
			$headers = $headers->getAll();
		} elseif ( is_object( $headers ) ) {
			$headers = (array) $headers;
		}

		// Make headers case-insensitive for easier access.
		$headers = array_change_key_case( $headers, CASE_LOWER );

		// Extract all rate limit headers.
		$limit           = isset( $headers['x-ratelimit-limit'] ) ? (int) $headers['x-ratelimit-limit'] : null;
		$remaining       = isset( $headers['x-ratelimit-remaining'] ) ? (int) $headers['x-ratelimit-remaining'] : null;
		$reset_timestamp = isset( $headers['x-ratelimit-reset'] ) ? (int) $headers['x-ratelimit-reset'] : null;
		$retry_after     = isset( $headers['retry-after'] ) ? (int) $headers['retry-after'] : null;

		// Check if we have any rate limit headers.
		$has_rate_limit_data = (
			null !== $limit ||
			null !== $remaining ||
			null !== $reset_timestamp ||
			null !== $retry_after
		);

		if ( $has_rate_limit_data ) {
			// Log rate limit info only on 429 errors.
			if ( 429 === $status_code ) {
				$reset_info = '';
				if ( null !== $reset_timestamp ) {
					$reset_info = sprintf(
						' - Reset: %s',
						gmdate( 'Y-m-d H:i:s', $reset_timestamp )
					);
				} elseif ( null !== $retry_after ) {
					$reset_info = sprintf( ' - Reset in %d seconds', $retry_after );
				}

				error_log(
					sprintf(
						'BuddyBoss Theme: Rate limit exceeded (remaining: %s)%s',
						null !== $remaining ? $remaining : 'N/A',
						$reset_info
					)
				);
			}

			// Determine the best reset time to use.
			$final_reset_time = null;
			$source           = 'unknown';

			// Priority: X-RateLimit-Reset > Retry-After calculation.
			if ( null !== $reset_timestamp ) {
				$final_reset_time = $reset_timestamp;
				$source           = 'x_ratelimit_reset';
			} elseif ( null !== $retry_after ) {
				$final_reset_time = time() + $retry_after;
				$source           = 'retry_after_header';
			} elseif ( 429 === $status_code ) {
				// For 429 errors without reset time, use default 1 hour window.
				$final_reset_time = time() + HOUR_IN_SECONDS;
				$source           = 'default_429_window';
				error_log( 'BuddyBoss Theme: 429 error without reset time - using 1 hour default' );
			}

			// Build rate limit data.
			$rate_limit_data = array(
				'limit'     => $limit,
				'remaining' => null !== $remaining ? $remaining : ( 429 === $status_code ? 0 : null ),
				'reset'     => $final_reset_time,
				'source'    => $source,
				'timestamp' => time(),
			);

			// Calculate expiration for transient.
			$expiration = HOUR_IN_SECONDS;
			if ( $final_reset_time ) {
				$time_until_reset = max( 0, $final_reset_time - time() );
				$expiration       = $time_until_reset + 60; // Add 60 second buffer.
			}

			self::set_rate_limit_transient(
				'rate_limit',
				$rate_limit_data,
				$expiration
			);
		}

		return $response;
	}

	/**
	 * The controller for handling the license activation/deactivation post requests.
	 * Overrides the parent controller to add dynamic plugin ID support.
	 *
	 * @return void
	 */
	public static function controller(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce is verified in activateLicense() and deactivateLicense() methods
		if ( isset( $_POST['buddyboss_platform_license_button'] ) ) {
			$plugin_connector = self::getContainer()->get( AbstractPluginConnection::class ); // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

			// Setup dynamic plugin ID if present in license key.
			if ( isset( $_POST['license_key'] ) ) {
				$license_key          = sanitize_text_field( wp_unslash( $_POST['license_key'] ) );
				$_POST['license_key'] = self::setup_dynamic_plugin_id( $license_key, $plugin_connector );
			}

			$plugin_id = $plugin_connector->pluginId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			if ( 'activate' === $_POST['buddyboss_platform_license_button'] ) {
				try {
					$license_key       = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
					$activation_domain = isset( $_POST['activation_domain'] ) ? sanitize_text_field( wp_unslash( $_POST['activation_domain'] ) ) : '';
					self::activateLicense( $license_key, $activation_domain );
					printf(
						'<div class="notice notice-success"><p>%s</p></div>',
						esc_html__( 'License activated successfully', 'buddyboss-theme' )
					);
				} catch ( \Exception $e ) {
					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html( $e->getMessage() )
					);
				}
			} elseif ( 'deactivate' === $_POST['buddyboss_platform_license_button'] ) {
				try {
					$license_key       = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
					$activation_domain = isset( $_POST['activation_domain'] ) ? sanitize_text_field( wp_unslash( $_POST['activation_domain'] ) ) : '';
					self::deactivateLicense( $license_key, $activation_domain );
					printf(
						'<div class="notice notice-success"><p>%s</p></div>',
						esc_html__( 'License deactivated successfully', 'buddyboss-theme' )
					);
				} catch ( \Exception $e ) {
					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html( $e->getMessage() )
					);
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Activate a license with dynamic plugin ID support.
	 *
	 * @param string $license_key The license key.
	 * @param string $domain     The domain to activate on.
	 *
	 * @throws \Exception If the activation fails.
	 * @return void
	 */
	public static function activateLicense( string $license_key, string $domain ): void {
		self::validate_activation_permissions();
		self::validate_activation_inputs( $license_key, $domain );

		$plugin_connector = self::getContainer()->get( AbstractPluginConnection::class ); // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$license_key      = self::setup_dynamic_plugin_id( $license_key, $plugin_connector );

		$validation_error = self::validate_product_before_activation( $license_key, $plugin_connector->pluginId ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( is_wp_error( $validation_error ) ) {
			$plugin_connector->clearDynamicPluginId();
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- WP_Error::get_error_message() returns sanitized text
			throw new \Exception( $validation_error->get_error_message() );
		}

		$response = self::perform_license_activation_api_call( $plugin_connector->pluginId, $license_key, $domain ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		if ( $response instanceof Response && $response->isError() ) {
			self::handle_activation_error( $response, $plugin_connector );
		}

		if ( $response instanceof Response && ! $response->isError() ) {
			self::process_successful_activation( $license_key, $plugin_connector, $domain );
		}

		self::disable_header_capture();
	}

	/**
	 * Validate user permissions for license activation.
	 *
	 * @throws \Exception If validation fails.
	 * @return void
	 */
	private static function validate_activation_permissions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			throw new \Exception( esc_html__( 'You do not have permission to activate a license', 'buddyboss-theme' ) );
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'mothership_activate_license' ) ) {
			throw new \Exception( esc_html__( 'Invalid nonce', 'buddyboss-theme' ) );
		}
	}

	/**
	 * Validate activation inputs and check rate limits.
	 *
	 * @param string $license_key The license key.
	 * @param string $domain     The activation domain.
	 *
	 * @throws \Exception If validation fails.
	 * @return void
	 */
	private static function validate_activation_inputs( string $license_key, string $domain ): void {
		if ( empty( $license_key ) ) {
			throw new \Exception( esc_html__( 'License key is required', 'buddyboss-theme' ) );
		}

		if ( empty( $domain ) ) {
			throw new \Exception( esc_html__( 'Activation domain is required', 'buddyboss-theme' ) );
		}

		$rate_limit_check = self::check_rate_limit();
		if ( is_wp_error( $rate_limit_check ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- WP_Error::get_error_message() returns sanitized text
			throw new \Exception( $rate_limit_check->get_error_message() );
		}
	}

	/**
	 * Perform the license activation API call.
	 *
	 * @param string $product    The product ID.
	 * @param string $license_key The license key.
	 * @param string $domain     The activation domain.
	 *
	 * @throws \Exception If API call fails.
	 * @return Response The API response.
	 */
	private static function perform_license_activation_api_call( string $product, string $license_key, string $domain ): Response {
		self::enable_header_capture();

		try {
			error_log(
				sprintf(
					'BuddyBoss Theme: Attempting license activation - Product: %s, Domain: %s',
					$product,
					$domain
				)
			);

			return LicenseActivations::activate( $product, $license_key, $domain );
		} catch ( \Exception $e ) {
			self::disable_header_capture();
			error_log( sprintf( 'BuddyBoss Theme: License activation API exception: %s', $e->getMessage() ) );
			throw new \Exception(
				esc_html__( 'License activation failed. Please check your license key and try again. If the problem persists, contact support.', 'buddyboss-theme' )
			);
		}
	}

	/**
	 * Handle activation errors based on error code.
	 *
	 * @param Response                 $response        The API response.
	 * @param AbstractPluginConnection $plugin_connector The plugin connector.
	 *
	 * @throws \Exception Always throws exception with appropriate message.
	 * @return void
	 */
	private static function handle_activation_error( Response $response, $plugin_connector ): void {
		self::disable_header_capture();

		$error_code    = $response->__get( 'errorCode' );
		$error_message = $response->__get( 'error' );
		$errors        = $response->__get( 'errors' );

		self::track_failed_activation();

		error_log(
			sprintf(
				'BuddyBoss Theme: License activation failed - Code: %d, Message: %s, Product: %s',
				$error_code,
				$error_message,
				$plugin_connector->pluginId // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			)
		);

		if ( 422 === $error_code ) {
			self::handle_activation_error_422( $errors, $plugin_connector );
		}

		if ( 429 === $error_code ) {
			self::handle_activation_error_429();
		}

		throw new \Exception(
			sprintf(
				/* translators: %s is the error message from API */
				esc_html__( 'License activation failed: %s', 'buddyboss-theme' ),
				esc_html( $error_message )
			)
		);
	}

	/**
	 * Handle 422 product mismatch errors.
	 *
	 * @param array|null               $errors          The error details.
	 * @param AbstractPluginConnection $plugin_connector The plugin connector.
	 *
	 * @throws \Exception If product mismatch detected.
	 * @return void
	 */
	private static function handle_activation_error_422( $errors, $plugin_connector ): void {
		if ( is_array( $errors ) && isset( $errors['product'] ) ) {
			$plugin_connector->clearDynamicPluginId();
			error_log( 'BuddyBoss Theme: Cleared orphaned plugin ID (422)' );

			throw new \Exception(
				esc_html__( 'License activation failed: The stored product ID did not match your license. Please try activating again with your license key.', 'buddyboss-theme' )
			);
		}
	}

	/**
	 * Handle 429 rate limit errors with exponential backoff.
	 *
	 * @throws \Exception Always throws exception with wait time message.
	 * @return void
	 */
	private static function handle_activation_error_429(): void {
		$rate_limit_data = self::get_rate_limit_transient( 'rate_limit' );

		if ( $rate_limit_data && isset( $rate_limit_data['reset'] ) && $rate_limit_data['reset'] > 0 ) {
			$reset_time   = $rate_limit_data['reset'];
			$backoff_time = max( 0, $reset_time - time() );
			$wait_minutes = ceil( $backoff_time / 60 );
			error_log( 'BuddyBoss Theme: Using API reset time' );
		} else {
			$backoff_time = self::get_backoff_wait_time();
			$reset_time   = time() + $backoff_time;
			$wait_minutes = ceil( $backoff_time / 60 );
			error_log( 'BuddyBoss Theme: Using calculated backoff time' );
		}

		error_log(
			sprintf(
				'BuddyBoss Theme: Wait %d minutes (reset: %s)',
				$wait_minutes,
				gmdate( 'Y-m-d H:i:s', $reset_time )
			)
		);

		$rate_limit_data = array(
			'limit'     => 10,
			'remaining' => 0,
			'reset'     => $reset_time,
			'timestamp' => time(),
			'source'    => 'calculated_backoff',
		);
		self::set_rate_limit_transient( 'rate_limit', $rate_limit_data, HOUR_IN_SECONDS );

		throw new \Exception(
			sprintf(
				/* translators: %d is the number of minutes to wait */
				esc_html__( 'License activation failed: Too many activation requests. Please wait approximately %d minute(s) before trying again.', 'buddyboss-theme' ),
				max( 1, $wait_minutes ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- max() returns integer
			)
		);
	}

	/**
	 * Process successful license activation.
	 *
	 * @param string                   $license_key      The license key.
	 * @param AbstractPluginConnection $plugin_connector The plugin connector.
	 * @param string                   $domain          The activation domain.
	 *
	 * @throws \Exception If storing credentials fails.
	 * @return void
	 */
	private static function process_successful_activation( string $license_key, $plugin_connector, string $domain ): void {
		try {
			Credentials::storeLicenseKey( $license_key );
			$plugin_connector->updateLicenseActivationStatus( true );

			$plugin_id = $plugin_connector->pluginId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			delete_transient( $plugin_id . '-mosh-products' );
			delete_transient( $plugin_id . '-mosh-addons-update-check' );

			self::reset_failed_attempts();

			error_log(
				sprintf(
					'BuddyBoss Theme: License activated successfully - Product: %s, Domain: %s',
					$plugin_id,
					$domain
				)
			);
		} catch ( \Exception $e ) {
			self::disable_header_capture();
			error_log( sprintf( 'BuddyBoss Theme: Error storing license credentials: %s', $e->getMessage() ) );
			throw new \Exception( esc_html__( 'License activation succeeded but failed to save. Please try again.', 'buddyboss-theme' ) );
		}
	}

	/**
	 * Validate that the product ID matches the license before activation.
	 * Prevents activation failures due to product mismatch.
	 *
	 * @param string $license_key The license key to validate.
	 * @param string $product_id  The product ID we're attempting to activate.
	 *
	 * @return true|WP_Error True if validation passes, WP_Error if fails.
	 */
	private static function validate_product_before_activation( string $license_key, string $product_id ) {
		// Skip validation if license key is empty (will be caught by input validation).
		if ( empty( $license_key ) ) {
			return true;
		}

		// Skip validation if we have any recent failed attempts (likely rate limited).
		$failed_attempts = self::get_rate_limit_transient( 'failed_attempts' );
		if ( $failed_attempts && (int) $failed_attempts > 0 ) {
			error_log(
				sprintf(
					'BuddyBoss Theme: Skipping pre-activation validation - %d recent failed attempts',
					$failed_attempts
				)
			);
			return true;
		}

		// Skip if currently rate limited.
		$rate_limit_data = self::get_rate_limit_transient( 'rate_limit' );
		if ( $rate_limit_data && is_array( $rate_limit_data ) ) {
			$reset_time   = isset( $rate_limit_data['reset'] ) ? (int) $rate_limit_data['reset'] : 0;
			$current_time = time();

			if ( $reset_time > 0 && $current_time < $reset_time ) {
				$wait_minutes = ceil( max( 0, $reset_time - $current_time ) / 60 );
				error_log(
					sprintf(
						'BuddyBoss Theme: Skipping pre-validation - rate limited until %s (%d minutes)',
						gmdate( 'Y-m-d H:i:s', $reset_time ),
						$wait_minutes
					)
				);
				return true;
			}

			$remaining = isset( $rate_limit_data['remaining'] ) ? (int) $rate_limit_data['remaining'] : null;
			if ( null !== $remaining && $remaining <= 1 ) {
				error_log(
					sprintf(
						'BuddyBoss Theme: Skipping pre-validation - only %d requests remaining',
						$remaining
					)
				);
				return true;
			}
		}

		try {
			$response = \BuddyBossTheme\GroundLevel\Mothership\Api\Request\Licenses::get( $license_key );

			if ( $response instanceof Response && $response->isError() ) {
				$error_code = $response->__get( 'errorCode' );

				if ( 429 === $error_code ) {
					error_log( 'BuddyBoss Theme: Validation encountered rate limit - blocking activation' );

					self::track_failed_activation();

					$rate_limit_data = self::get_rate_limit_transient( 'rate_limit' );

					if ( $rate_limit_data && isset( $rate_limit_data['reset'] ) && $rate_limit_data['reset'] > 0 ) {
						$reset_time   = $rate_limit_data['reset'];
						$backoff_time = max( 0, $reset_time - time() );
						error_log( 'BuddyBoss Theme: Using API reset time' );
					} else {
						$backoff_time = self::get_backoff_wait_time();
						$reset_time   = time() + $backoff_time;
						error_log( 'BuddyBoss Theme: Using calculated backoff time' );
					}

					$wait_minutes = (int) ceil( $backoff_time / 60 );

					error_log(
						sprintf(
							'BuddyBoss Theme: Rate limit detected during validation - wait time: %d seconds (%d minutes)',
							$backoff_time,
							$wait_minutes
						)
					);

					if ( ! $rate_limit_data || ! isset( $rate_limit_data['reset'] ) ) {
						set_transient(
							'rate_limit',
							array(
								'remaining' => 0,
								'reset'     => $reset_time,
								'source'    => 'validation_calculated',
							),
							$backoff_time + 60
						);
					}

					return new \WP_Error(
						'rate_limit',
						sprintf(
							/* translators: %d is the number of minutes to wait */
							esc_html__( 'Too many activation requests. Please wait approximately %d minute(s) before trying again.', 'buddyboss-theme' ),
							max( 1, $wait_minutes )
						)
					);
				}

				error_log(
					sprintf(
						'BuddyBoss Theme: Pre-activation validation failed (non-blocking): %s',
						$response->__get( 'error' )
					)
				);
				return true;
			}

			if ( $response instanceof Response && ! $response->isError() ) {
				$license_data = $response->toArray();

				if ( isset( $license_data['product'] ) ) {
					$actual_product = $license_data['product'];

					error_log(
						sprintf(
							'BuddyBoss Theme: License product validation - Expected: %s, Actual: %s',
							$product_id,
							$actual_product
						)
					);

					if ( $actual_product !== $product_id ) {
						error_log( 'BuddyBoss Theme: Product mismatch detected - clearing orphaned plugin ID' );

						$plugin_connector = self::getContainer()->get( AbstractPluginConnection::class ); // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
						$plugin_connector->clearDynamicPluginId();

						return new \WP_Error(
							'product_mismatch',
							sprintf(
								/* translators: 1: Expected product, 2: Actual product from license */
								esc_html__( 'Product validation failed: Your license is for "%2$s" but the system was configured for "%1$s". The configuration has been reset. Please try activating again.', 'buddyboss-theme' ),
								$product_id,
								$actual_product
							)
						);
					}

					return true;
				}
			}
		} catch ( \Exception $e ) {
			error_log(
				sprintf(
					'BuddyBoss Theme: Pre-activation validation exception (non-blocking): %s',
					$e->getMessage()
				)
			);
			return true;
		}

		return true;
	}

	/**
	 * Clear license details cache.
	 * Called when license status changes or plugin ID changes.
	 *
	 * @return void
	 */
	public static function clearLicenseDetailsCache(): void {
		$plugin_id = self::getContainer()->get( AbstractPluginConnection::class )->pluginId; // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$cache_key = $plugin_id . '_theme_license_details';
		delete_transient( $cache_key );
	}

	/**
	 * Parse license key for dynamic plugin ID.
	 * License keys can have format: "LICENSE_KEY:PLUGIN_ID"
	 * where PLUGIN_ID starts with "bb-"
	 *
	 * @param string $license_key     The license key that may contain plugin ID.
	 * @param object $plugin_connector The plugin connector instance.
	 *
	 * @return string The cleaned license key without plugin ID.
	 */
	private static function setup_dynamic_plugin_id( string $license_key, $plugin_connector ): string {
		$key_parts = explode( ':', $license_key );

		// Check if license key contains plugin ID in format KEY:PLUGIN_ID
		if ( count( $key_parts ) === 2 && preg_match( '/^bb-/', $key_parts[1] ) ) {
			$plugin_id = $key_parts[1];

			// Set the dynamic plugin ID
			$plugin_connector->setDynamicPluginId( $plugin_id );

			// Return the actual license key part
			return $key_parts[0];
		}

		return $license_key;
	}

	/**
	 * Generates the HTML for the activation form.
	 * Overrides parent to use BuddyBoss specific button naming.
	 *
	 * @return string The HTML for the activation form.
	 */
	public function generateActivationForm(): string {
		ob_start();
		$plugin_id = self::getContainer()->get( AbstractPluginConnection::class )->pluginId; // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		?>
		<h2><?php esc_html_e( 'License Activation', 'buddyboss-theme' ); ?></h2>
		<form method="post" action="" name="<?php echo esc_attr( $plugin_id ); ?>_activate_license_form">
			<div class="<?php echo esc_attr( $plugin_id ); ?>-license-form license-form-wrap">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="license_key"><?php esc_html_e( 'License Key', 'buddyboss-theme' ); ?></label>
						</th>
						<td>
							<input type="text" name="license_key" id="license_key" placeholder="<?php esc_attr_e( 'Enter your license key', 'buddyboss-theme' ); ?>" value="<?php echo esc_attr( Credentials::getLicenseKey() ); ?>" >
							<input type="hidden" name="activation_domain" id="activation_domain" value="<?php echo esc_attr( Credentials::getActivationDomain() ); ?>" >
						</td>
					</tr>
					<tr>
						<td colspan="2" scope="row">
							<?php wp_nonce_field( 'mothership_activate_license', '_wpnonce' ); ?>
							<input type="hidden" name="buddyboss_platform_license_button" value="activate">
							<input type="submit" value="<?php esc_html_e( 'Activate License', 'buddyboss-theme' ); ?>" class="button button-primary <?php echo esc_attr( $plugin_id ); ?>-button-activate">
						</td>
					</tr>
				</table>
			</div>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Generates the HTML for the disconnect/deactivate form.
	 * Overrides parent to use BuddyBoss specific button naming.
	 *
	 * @return string The HTML for the disconnect form.
	 */
	public function generateDisconnectForm(): string {
		ob_start();
		$plugin_id = self::getContainer()->get( AbstractPluginConnection::class )->pluginId; // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

		$license_key  = Credentials::getLicenseKey();
		$license_info = $this->bb_theme_get_license_details( $license_key );
		?>
		<h2><?php esc_html_e( 'License Management', 'buddyboss-theme' ); ?></h2>

		<?php
		if ( ! is_wp_error( $license_info ) ) {
			$activation_text = sprintf(
				__( '%1$s of %2$s sites have been activated with this license key', 'buddyboss-theme' ),
				$license_info['total_prod_used'],
				999 <= (int) $license_info['total_prod_allowed'] ? 'unlimited' : $license_info['total_prod_allowed']
			);
			?>
			<div class="activated-licence">
				<p class=""><?php esc_html_e( 'License Key: ', 'buddyboss-theme' ); ?><?php echo esc_html( $license_info['license_key'] ); ?></p>
				<p class=""><?php esc_html_e( 'Status: ', 'buddyboss-theme' ); ?><?php echo esc_html( $license_info['status'] ); ?></p>
				<p class=""><?php esc_html_e( 'Product: ', 'buddyboss-theme' ); ?><?php echo esc_html( $license_info['product'] ); ?></p>
				<p class=""><?php esc_html_e( 'Activations: ', 'buddyboss-theme' ); ?><?php echo esc_html( $activation_text ); ?></p>
			</div>
		<?php } ?>

		<form method="post" action="" name="<?php echo esc_attr( $plugin_id ); ?>_deactivate_license_form">
			<div class="<?php echo esc_attr( $plugin_id ); ?>-license-form license-form-wrap">
				<table class="form-table">
					<tr>
						<td colspan="2" scope="row">
							<input type="hidden" name="license_key" id="license_key" placeholder="<?php esc_attr_e( 'Enter your license key', 'buddyboss-theme' ); ?>" value="<?php echo esc_attr( Credentials::getLicenseKey() ); ?>" readonly />
							<input type="hidden" name="activation_domain" id="activation_domain" value="<?php echo esc_attr( Credentials::getActivationDomain() ); ?>" />
							<?php wp_nonce_field( 'mothership_deactivate_license', '_wpnonce' ); ?>
							<input type="hidden" name="buddyboss_platform_license_button" value="deactivate">
							<input type="submit" value="<?php esc_html_e( 'Deactivate License', 'buddyboss-theme' ); ?>" class="button button-secondary <?php echo esc_attr( $plugin_id ); ?>-button-deactivate" >
						</td>
					</tr>
				</table>
			</div>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Check if we're currently being rate limited.
	 * Looks at stored rate limit data from previous requests.
	 *
	 * @return true|WP_Error True if OK to proceed, WP_Error if rate limited
	 */
	private static function check_rate_limit() {
		$rate_limit_data = self::get_rate_limit_transient( 'rate_limit' );

		if ( ! $rate_limit_data || ! is_array( $rate_limit_data ) ) {
			return true; // No rate limit data, proceed.
		}

		$remaining    = isset( $rate_limit_data['remaining'] ) ? (int) $rate_limit_data['remaining'] : null;
		$reset_time   = isset( $rate_limit_data['reset'] ) ? (int) $rate_limit_data['reset'] : 0;
		$current_time = time();
		$source       = isset( $rate_limit_data['source'] ) ? $rate_limit_data['source'] : 'unknown';

		// Check for invalid reset time (0 or very old timestamp).
		if ( $reset_time <= 0 || $reset_time < ( $current_time - YEAR_IN_SECONDS ) ) {
			error_log(
				sprintf(
					'BuddyBoss Theme: Invalid rate limit data detected (reset: %d) - clearing corrupted data',
					$reset_time
				)
			);
			self::delete_rate_limit_transient( 'rate_limit' );
			return true;
		}

		error_log(
			sprintf(
				'BuddyBoss Theme: Checking rate limit - Source: %s, Remaining: %d, Reset at: %s (Unix: %d), Current: %s (Unix: %d)',
				$source,
				$remaining,
				gmdate( 'Y-m-d H:i:s', $reset_time ),
				$reset_time,
				gmdate( 'Y-m-d H:i:s', $current_time ),
				$current_time
			)
		);

		// If reset time has passed, clear the rate limit AND reset failed attempts.
		if ( $current_time >= $reset_time ) {
			self::delete_rate_limit_transient( 'rate_limit' );
			self::delete_rate_limit_transient( 'failed_attempts' );
			error_log(
				sprintf(
					'BuddyBoss Theme: Rate limit window EXPIRED - Reset time %s has passed',
					gmdate( 'Y-m-d H:i:s', $reset_time )
				)
			);
			return true;
		}

		// Check if we're currently blocked by remaining count.
		if ( null !== $remaining && $remaining <= 0 ) {
			$wait_time    = max( 0, $reset_time - $current_time );
			$wait_minutes = max( 1, ceil( $wait_time / 60 ) );

			error_log(
				sprintf(
					'BuddyBoss Theme: Rate limit exceeded - Wait %d minutes (reset: %s)',
					$wait_minutes,
					gmdate( 'Y-m-d H:i:s', $reset_time )
				)
			);

			return new \WP_Error(
				'rate_limit_exceeded',
				sprintf(
					/* translators: %d is the number of minutes to wait */
					esc_html__( 'Rate limit exceeded. Please wait approximately %d minute(s) before trying again.', 'buddyboss-theme' ),
					$wait_minutes
				)
			);
		}

		return true;
	}

	/**
	 * Check if plugin is network activated (multisite).
	 *
	 * @return bool True if network activated, false otherwise.
	 */
	private static function is_network_activated(): bool {
		if ( ! is_multisite() ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		// Check if theme is network enabled (themes don't have network activation like plugins).
		// For themes, check if it's the active theme on the network.
		$current_theme = wp_get_theme();
		return is_multisite();
	}

	/**
	 * Get rate limit transient (multisite-aware) with collision prevention.
	 *
	 * @param string $key Transient key.
	 * @return mixed Transient value or false.
	 */
	private static function get_rate_limit_transient( string $key ) {
		// Add prefix to prevent collisions with other plugins/themes.
		$prefixed_key = 'bb_theme_license_' . $key;

		if ( self::is_network_activated() ) {
			return get_site_transient( $prefixed_key );
		}
		return get_transient( $prefixed_key );
	}

	/**
	 * Set rate limit transient (multisite-aware) with size validation and collision prevention.
	 *
	 * @param string $key Transient key.
	 * @param mixed  $value Transient value.
	 * @param int    $expiration Expiration time in seconds.
	 * @return bool True on success, false on failure.
	 */
	private static function set_rate_limit_transient( string $key, $value, int $expiration ) {
		// Add prefix to prevent collisions with other plugins/themes.
		$prefixed_key = 'bb_theme_license_' . $key;

		// Validate data size to prevent cache bloat (limit to 2KB).
		$serialized        = maybe_serialize( $value );
		$serialized_string = is_string( $serialized ) ? $serialized : (string) $serialized;
		if ( strlen( $serialized_string ) > 2048 ) {
			error_log( 'BuddyBoss Theme: Rate limit data exceeds maximum size (2KB)' );
			return false;
		}

		if ( self::is_network_activated() ) {
			return set_site_transient( $prefixed_key, $value, $expiration );
		}
		return set_transient( $prefixed_key, $value, $expiration );
	}

	/**
	 * Delete rate limit transient (multisite-aware) with collision prevention.
	 *
	 * @param string $key Transient key.
	 * @return bool True on success, false on failure.
	 */
	private static function delete_rate_limit_transient( string $key ) {
		// Add prefix to prevent collisions with other plugins/themes.
		$prefixed_key = 'bb_theme_license_' . $key;

		if ( self::is_network_activated() ) {
			return delete_site_transient( $prefixed_key );
		}
		return delete_transient( $prefixed_key );
	}

	/**
	 * Track failed activation attempt for exponential backoff.
	 *
	 * @return void
	 */
	private static function track_failed_activation(): void {
		$attempts = self::get_rate_limit_transient( 'failed_attempts' );
		$attempts = $attempts ? (int) $attempts : 0;
		++$attempts;

		// Store for 1 hour.
		self::set_rate_limit_transient( 'failed_attempts', $attempts, HOUR_IN_SECONDS );
	}

	/**
	 * Get suggested wait time based on exponential backoff.
	 *
	 * @return int Seconds to wait before retry
	 */
	private static function get_backoff_wait_time(): int {
		$attempts = self::get_rate_limit_transient( 'failed_attempts' );
		$attempts = $attempts ? (int) $attempts : 0;

		if ( 0 === $attempts ) {
			return 0;
		}

		// Exponential backoff: 2^attempts * base (30 seconds).
		// Attempt 1: 30s, Attempt 2: 60s, Attempt 3: 120s, Attempt 4: 240s, etc.
		// Max 15 minutes.
		$base_seconds = 30;
		$wait_time    = min( pow( 2, $attempts - 1 ) * $base_seconds, 900 );

		return (int) $wait_time;
	}

	/**
	 * Reset failed activation attempts counter.
	 *
	 * @return void
	 */
	private static function reset_failed_attempts(): void {
		self::delete_rate_limit_transient( 'failed_attempts' );
	}

	/**
	 * AJAX handler for resetting license settings.
	 * Clears all license-related data including orphaned dynamic plugin ID.
	 *
	 * @return void
	 */
	public static function ajax_reset_license_settings(): void {
		// Verify nonce - check existence first to prevent PHP warnings.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bb_reset_license_settings' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'buddyboss-theme' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action', 'buddyboss-theme' ) );
		}

		try {
			$plugin_connector = self::getContainer()->get( AbstractPluginConnection::class ); // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

			// Get current plugin ID before clearing.
			$current_plugin_id = $plugin_connector->getCurrentPluginId();

			// Clear dynamic plugin ID.
			$plugin_connector->clearDynamicPluginId();

			// Clear web plugin ID (set when using KEY:PLUGIN_ID format).
			delete_option( 'buddyboss_web_plugin_id' );

			// Clear license key.
			$plugin_connector->updateLicenseKey( '' );

			// Clear license activation status.
			$plugin_connector->updateLicenseActivationStatus( false );

			// Clear migration flag.
			delete_option( 'bb_mothership_licenses_migrated' );
			delete_site_option( 'bb_mothership_licenses_migrated' );

			// List all possible plugin IDs that might have stored data.
			$all_plugin_ids = array(
				$current_plugin_id,
                THEME_EDITION,
				'bb-platform-pro-1-site',
				'bb-platform-pro-2-sites',
				'bb-platform-pro-5-sites',
				'bb-platform-pro-10-sites',
				'bb-platform-free',
				'bb-web',
				'bb-web-2-sites',
				'bb-web-5-sites',
				'bb-web-10-sites',
				'bb-web-20-sites',
			);

			// Clear all license-related data for all possible plugin IDs.
			foreach ( $all_plugin_ids as $plugin_id ) {
				// Clear license keys and activation status.
				delete_option( $plugin_id . '_license_key' );
				delete_option( $plugin_id . '_license_activation_status' );

				// Clear transients (both regular and site-wide for multisite).
				delete_transient( $plugin_id . '-mosh-products' );
				delete_transient( $plugin_id . '-mosh-addons-update-check' );
				delete_transient( $plugin_id . '_theme_license_details' );
				delete_site_transient( $plugin_id . '-mosh-products' );
				delete_site_transient( $plugin_id . '-mosh-addons-update-check' );
				delete_site_transient( $plugin_id . '_theme_license_details' );
			}

			// Clear rate limit and backoff data.
			self::delete_rate_limit_transient( 'rate_limit' );
			self::delete_rate_limit_transient( 'failed_attempts' );

			// Log the reset action.
			error_log( 'BuddyBoss Theme: License settings reset by user - all mothership data cleared' );

			wp_send_json_success(
				array(
					'message' => __( 'License settings have been reset successfully. You can now activate your license with the correct license key.', 'buddyboss-theme' ),
				)
			);
		} catch ( \Exception $e ) {
			error_log( sprintf( 'BuddyBoss Theme: Error resetting license: %s', $e->getMessage() ) );
			// Don't expose internal errors to users via AJAX response.
			wp_send_json_error(
				__( 'Failed to reset license settings. Please try again or contact support if the problem persists.', 'buddyboss-theme' )
			);
		}
	}

	/**
	 * Get License + Activations details from Caseproof API.
	 *
	 * @param string $license_key   License UUID.
	 * @param bool   $force_refresh Whether to force refresh the cache.
	 *
	 * @return array|WP_Error    Array of license + activation data, or WP_Error on failure.
	 */
	protected function bb_theme_get_license_details( $license_key, $force_refresh = false ) {
		$plugin_id = self::getContainer()->get( AbstractPluginConnection::class )->pluginId; // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

		// Create cache key based on plugin ID only (not license key for security).
		$cache_key = $plugin_id . '_theme_license_details';

		// Check cache first unless force refresh is requested.
		if ( ! $force_refresh ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data && ! is_wp_error( $cached_data ) ) {
				return $cached_data;
			}
		}

		$root_api_url = self::get_api_base_url( $plugin_id );

		$api_url     = $root_api_url . 'licenses/' . $license_key;
		$domain      = wp_parse_url( home_url(), PHP_URL_HOST );
		$credentials = base64_encode( $domain . ':' . $license_key );
		$args        = array(
			'headers' => array(
				'Authorization' => "Basic $credentials",
				'Content-Type'  => 'application / json',
				'Accept'        => 'application / json',
			),
		);

		// First request: License details.
		$response = wp_remote_get( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			return $response; // Return error.
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if (
				empty( $body['license_key'] ) ||
				empty( $body['product'] )
		) {
			return new \WP_Error( 'invalid_response', 'License key or product not found in response' );
		}

		$license_key          = $body['license_key'];
		$product              = $body['product'];
		$activations_meta_url = $body['_links']['activations-meta']['href'] ?? '';

		// If activations-meta link missing.
		if ( empty( $activations_meta_url ) ) {
			return new \WP_Error( 'missing_link', 'Activations - meta URL not found in license response' );
		}

		// Second request: Activations-meta.
		$response2 = wp_remote_get( $activations_meta_url, $args );

		if ( is_wp_error( $response2 ) ) {
			return $response2;
		}

		$body2 = json_decode( wp_remote_retrieve_body( $response2 ), true );

		// Prepare combined data.
		$license_data = array(
			'license_key'        => '********-****-****-****-' . esc_html( substr( $license_key, - 12 ) ),
			'product'            => $product,
			'status'             => $body['status'] ?? '',
			'total_prod_allowed' => $body2['prod']['allowed'] ?? 0,
			'total_prod_used'    => $body2['prod']['used'] ?? 0,
			'total_prod_free'    => $body2['prod']['free'] ?? 0,
			'total_test_allowed' => $body2['test']['allowed'] ?? 0,
			'total_test_used'    => $body2['test']['used'] ?? 0,
			'total_test_free'    => $body2['test']['free'] ?? 0,
		);

		// License details don't change frequently, so 12 hours is reasonable.
		set_transient( $cache_key, $license_data, 12 * HOUR_IN_SECONDS );

		return $license_data;
	}
}