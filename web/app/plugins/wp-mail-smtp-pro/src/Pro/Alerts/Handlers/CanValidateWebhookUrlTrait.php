<?php

namespace WPMailSMTP\Pro\Alerts\Handlers;

/**
 * Webhook URL validation + safe HTTP request helper.
 *
 * Blocks link-local destinations (169.254.0.0/16, fe80::/10) — the cloud
 * metadata range on AWS, GCP, and Azure that wp_http_validate_url() does
 * not cover. Other internal ranges (loopback, RFC1918, IPv6 ULA) are left
 * to wp_safe_remote_* and the http_request_host_is_external filter, so
 * callers that need to reach internal receivers can opt in.
 *
 * @since 4.9.0
 */
trait CanValidateWebhookUrlTrait {

	/**
	 * Validate a webhook URL and send a safe HTTP request, pinning curl's
	 * DNS resolution to the IP this trait validated.
	 *
	 * Returns false if the URL failed validation. Otherwise returns the
	 * wp_safe_remote_request() result (response array or WP_Error).
	 *
	 * @since 4.9.0
	 *
	 * @param string $url  URL to call.
	 * @param array  $args Request args. Caller is responsible for setting `method`.
	 *
	 * @return array|\WP_Error|false
	 */
	protected function safe_webhook_request( $url, $args ) {

		$pin = $this->get_safe_webhook_request_pin( $url );

		if ( ! $pin ) {
			return false;
		}

		$pin_curl = static function ( $handle ) use ( $pin ) {
			curl_setopt( $handle, CURLOPT_RESOLVE, [ "{$pin['host']}:{$pin['port']}:{$pin['ip']}" ] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		};

		add_action( 'http_api_curl', $pin_curl );

		try {
			return wp_safe_remote_request( $url, $args );
		} finally {
			remove_action( 'http_api_curl', $pin_curl );
		}
	}

	/**
	 * Validate a webhook URL. Returns the host/IP/port to pin curl's
	 * resolution against, or false if the URL is unsafe.
	 *
	 * @since 4.9.0
	 *
	 * @param string $url URL to validate.
	 *
	 * @return array|false ['host' => string, 'ip' => string, 'port' => int]
	 */
	protected function get_safe_webhook_request_pin( $url ) {

		if ( ! is_string( $url ) || $url === '' ) {
			return false;
		}

		$parts = wp_parse_url( $url );

		if ( empty( $parts['host'] ) ) {
			return false;
		}

		$host = trim( $parts['host'], '[]' );
		$ip   = filter_var( $host, FILTER_VALIDATE_IP );

		if ( $ip === false ) {
			$resolved = gethostbyname( $host );
			$ip       = $resolved !== $host ? $resolved : '';
		}

		if ( $ip === '' ) {
			return false;
		}

		if ( $this->is_link_local_ip( $ip ) ) {
			return false;
		}

		if ( ! empty( $parts['port'] ) ) {
			$port = (int) $parts['port'];
		} else {
			$scheme = isset( $parts['scheme'] ) ? strtolower( $parts['scheme'] ) : '';
			$port   = $scheme === 'http' ? 80 : 443;
		}

		return [
			'host' => $parts['host'],
			'ip'   => $ip,
			'port' => $port,
		];
	}

	/**
	 * Whether an IP literal belongs to the IPv4 (169.254.0.0/16) or IPv6
	 * (fe80::/10) link-local range.
	 *
	 * @since 4.9.0
	 *
	 * @param string $ip IP literal.
	 *
	 * @return bool
	 */
	private function is_link_local_ip( $ip ) {

		$packed = @inet_pton( $ip ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( $packed === false ) {
			return true;
		}

		// IPv4-mapped IPv6 (`::ffff:a.b.c.d`) → IPv4 — curl on dual-stack
		// hosts connects to the embedded IPv4, so the IPv4 rule must apply.
		if (
			strlen( $packed ) === 16 &&
			substr( $packed, 0, 12 ) === "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff"
		) {
			$packed = substr( $packed, 12 );
		}

		if ( strlen( $packed ) === 4 ) {
			return ord( $packed[0] ) === 169 && ord( $packed[1] ) === 254;
		}

		return ord( $packed[0] ) === 0xFE && ( ord( $packed[1] ) & 0xC0 ) === 0x80;
	}
}
