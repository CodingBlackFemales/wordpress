<?php
/**
 * Whodat client class.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal;

use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\DB\DB;
use WP_Error;

/**
 * Whodat client class.
 *
 * @since 4.25.0
 */
class Whodat_Client {
	/**
	 * WhoDat connect base URL.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	private static string $base_url = 'https://whodat.stellarwp.com/ld/commerce/v1/paypal';

	/**
	 * The transient hash key.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	private const TRANSIENT_HASH_KEY = 'learndash_paypal_checkout_unique_signup_hash';

	/**
	 * The signup data transient key.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	private const SIGNUP_DATA_TRANSIENT_KEY = 'learndash_paypal_checkout_signup_data';

	/**
	 * The signup URL transient key.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	private const SIGNUP_URL_TRANSIENT_KEY = 'learndash_paypal_checkout_signup_url';

	/**
	 * Returns the transient hash.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public static function get_transient_hash(): string {
		return Cast::to_string(
			get_transient( self::TRANSIENT_HASH_KEY )
		);
	}

	/**
	 * Deletes all transients.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public static function delete_all_transients(): void {
		delete_transient( self::TRANSIENT_HASH_KEY );
		delete_transient( self::SIGNUP_DATA_TRANSIENT_KEY );

		/**
		 * Delete all transients for the signup URL.
		 *
		 * @var array<int,array<string,string>> $transients The transients to delete.
		 */
		$transients = DB::table( 'options' )
			->whereLike( 'option_name', '_transient_' . self::SIGNUP_URL_TRANSIENT_KEY )
			->getAll( ARRAY_A );

		foreach ( $transients as $transient ) {
			// Remove _transient_ prefix before calling delete_transient().
			delete_transient(
				str_replace(
					'_transient_',
					'',
					Cast::to_string( Arr::get( $transient, 'option_name', '' ) )
				)
			);
		}
	}

	/**
	 * Returns the referral data link.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_referral_data_link(): string {
		$links = Arr::wrap( get_transient( self::SIGNUP_DATA_TRANSIENT_KEY ) );

		return Cast::to_string( Arr::get( $links, 'links.0.href', '' ) );
	}

	/**
	 * Returns the signup link that redirects the seller to PayPal.
	 *
	 * @since 4.25.0
	 *
	 * @param string $country         Which country code we are generating the URL for.
	 * @param bool   $is_sandbox      Whether we are using the sandbox mode. Defaults to false.
	 * @param bool   $is_setup_wizard Whether we are using the setup wizard. Defaults to false.
	 *
	 * @return string|WP_Error
	 */
	public function get_signup_url(
		string $country = 'US',
		bool $is_sandbox = false,
		bool $is_setup_wizard = false
	) {
		$transient = sprintf(
			'%1$s_%2$s_%3$s_%4$s',
			self::SIGNUP_URL_TRANSIENT_KEY,
			strtolower( $country ),
			$is_sandbox ? 'sandbox' : 'live',
			$is_setup_wizard ? 'setup_wizard' : 'settings'
		);

		$url = Cast::to_string( get_transient( $transient ) );

		if ( ! empty( $url ) ) {
			return $url;
		}

		$signup_data = $this->get_seller_signup_data(
			$country,
			$is_sandbox,
			$is_setup_wizard
		);

		if ( is_wp_error( $signup_data ) ) {
			return $signup_data;
		}

		$signup_url = Cast::to_string( Arr::get( $signup_data, 'links.1.href', '' ) );

		set_transient( $transient, $signup_url, DAY_IN_SECONDS );

		return $signup_url;
	}

	/**
	 * Fetches the referral data from WhoDat/PayPal.
	 *
	 * @since 4.25.0
	 *
	 * @param string $url        The URL to fetch the referral data for.
	 * @param bool   $is_sandbox Whether we are using the sandbox mode. Defaults to false.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_seller_referral_data(
		string $url,
		bool $is_sandbox = false
	) {
		return $this->client_get(
			'seller/referral-data',
			[
				'is_sandbox' => $is_sandbox,
				'url'        => $url,
			]
		);
	}

	/**
	 * Fetches the seller credentials from PayPal.
	 *
	 * @since 4.25.0
	 *
	 * @param string $access_token The access token to use.
	 * @param bool   $is_sandbox   Whether we are using the sandbox mode. Defaults to false.
	 *
	 * @return array<string,array<int,array<string,string>>>|WP_Error
	 */
	public function get_seller_credentials(
		string $access_token,
		bool $is_sandbox = false
	) {
		return $this->client_post(
			'seller/credentials',
			[
				'is_sandbox'   => $is_sandbox,
				'access_token' => $access_token,
			]
		);
	}

	/**
	 * Returns the base URL.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	protected function get_base_url(): string {
		return self::$base_url;
	}

	/**
	 * Returns the connect URL.
	 *
	 * @since 4.25.0
	 *
	 * @param string              $endpoint The endpoint to connect to.
	 * @param array<string,mixed> $args     The arguments to pass to the endpoint.
	 *
	 * @return string
	 */
	protected function get_api_url( string $endpoint, array $args = [] ): string {
		$url = trailingslashit( $this->get_base_url() ) . $endpoint;

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

	/**
	 * Fetches data from the WhoDat API.
	 *
	 * @since 4.25.0
	 *
	 * @param string              $endpoint The endpoint to connect to.
	 * @param array<string,mixed> $args     The arguments to pass to the endpoint.
	 *
	 * @return array<string,string|array<int,array<string,string>>>|WP_Error
	 */
	protected function client_get( string $endpoint, array $args = [] ) {
		$url = $this->get_api_url( $endpoint, $args );

		$request = wp_remote_get(
			$url,
			[
				'timeout' => MINUTE_IN_SECONDS / 2, // 30 seconds.
			]
		);

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		$body = wp_remote_retrieve_body( $request );
		$body = json_decode( $body, true );

		if ( ! is_array( $body ) ) {
			return new WP_Error(
				'whodat_client_error',
				esc_html__( 'We encountered an unexpected error with the response. Please try again later or contact support if the issue persists.', 'learndash' ),
				[
					'status'        => 502,
					'endpoint'      => $endpoint,
					'response_body' => $body,
				]
			);
		}

		return $body;
	}

	/**
	 * Posts data to the WhoDat API.
	 *
	 * @since 4.25.0
	 *
	 * @param string              $endpoint The endpoint to connect to.
	 * @param array<string,mixed> $args     The arguments to pass to the endpoint.
	 *
	 * @return array<string,array<int,array<string,string>>>|WP_Error
	 */
	protected function client_post( string $endpoint, array $args = [] ) {
		$url = $this->get_api_url( $endpoint );

		$request = wp_remote_post(
			$url,
			[
				'body'    => $args,
				'timeout' => MINUTE_IN_SECONDS / 2, // 30 seconds.
			]
		);

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		$body = wp_remote_retrieve_body( $request );
		$body = json_decode( $body, true );

		if ( ! is_array( $body ) ) {
			return new WP_Error(
				'whodat_client_error',
				esc_html__( 'We encountered an unexpected error with the response. Please try again later or contact support if the issue persists.', 'learndash' ),
				[
					'status'        => 502,
					'endpoint'      => $endpoint,
					'response_body' => $body,
				]
			);
		}

		return $body;
	}

	/**
	 * Generates a Tracking ID for this website.
	 *
	 * The Tracking ID is a site-specific identifier that links the client and platform accounts in the Payment Gateway
	 * without exposing sensitive data. By default, the identifier generated is a URL in the format:
	 *
	 * {SITE_URL}?v={GATEWAY_VERSION}-{RANDOM_6_CHAR_HASH}
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	protected function generate_unique_tracking_id(): string {
		$id        = wp_generate_password( 6, false, false );
		$url_frags = wp_parse_url( home_url() );
		$scheme    = Arr::get( $url_frags, 'scheme', 'http' );
		$host      = Arr::get( $url_frags, 'host', 'localhost' );
		$url       = $scheme . '://' . $host;
		$url       = add_query_arg(
			[
				'v' => LEARNDASH_VERSION . '-' . $id,
			],
			$url
		);

		// Always limit it to 127 chars.
		return substr( $url, 0, 127 );
	}

	/**
	 * Generates a Unique Hash for signup. It will always be 20 characters long.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	protected function generate_unique_signup_hash(): string {
		$nonce_key  = defined( 'NONCE_KEY' ) ? NONCE_KEY : uniqid( '', true );
		$nonce_salt = defined( 'NONCE_SALT' ) ? NONCE_SALT : uniqid( '', true );

		$unique = uniqid( '', true );

		$keys = [ $nonce_key, $nonce_salt, $unique ];
		$keys = array_map( 'md5', $keys );

		return substr( str_shuffle( implode( '-', $keys ) ), 0, 45 );
	}

	/**
	 * Fetches the signup link from PayPal.
	 *
	 * @since 4.25.0
	 *
	 * @param string $country         Which country code we are using.
	 * @param bool   $is_sandbox      Whether we are using the sandbox mode. Defaults to false.
	 * @param bool   $is_setup_wizard Whether we are using the setup wizard. Defaults to false.
	 *
	 * @return array<string,string|array<int,array<string,string>>>|WP_Error
	 */
	protected function get_seller_signup_data(
		string $country = 'US',
		bool $is_sandbox = false,
		bool $is_setup_wizard = false
	) {
		// Generate a unique hash and store it in the transient.
		$hash = $this->generate_unique_signup_hash();
		set_transient( self::TRANSIENT_HASH_KEY, $hash, DAY_IN_SECONDS );

		$query_args = [
			'is_sandbox'  => $is_sandbox,
			'nonce'       => $hash,
			'tracking_id' => rawurlencode( $this->generate_unique_tracking_id() ),
			'return_url'  => rawurlencode(
				add_query_arg(
					[
						'page'            => 'learndash_lms_payments',
						'section-payment' => 'settings_paypal_checkout',
						'signup_return'   => '1',
						'is_setup_wizard' => $is_setup_wizard ? '1' : '0',
					],
					admin_url( 'admin.php' )
				)
			),
			'country'     => $country,
			'vaulting'    => true,
		];

		$request = $this->client_get( 'seller/signup', $query_args );

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		if ( empty( $request['links'] ) ) {
			return new WP_Error(
				'whodat_client_error',
				sprintf(
					// translators: %1$s is the error message.
					esc_html__( 'PayPal signup failed: %1$s', 'learndash' ),
					Cast::to_string( Arr::get( $request, 'message', '' ) )
				),
				[
					'status' => 502,
				]
			);
		}

		// Store the signup data in the transient for one day.
		set_transient( self::SIGNUP_DATA_TRANSIENT_KEY, $request, DAY_IN_SECONDS );

		return $request;
	}
}
