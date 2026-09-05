<?php
/**
 * The slice of WordPress the plugin's parsing code touches.
 *
 * Included only by the Unit suite. Each stub mirrors the real function closely
 * enough for assertions about escaping and path handling to mean something, and
 * no further. If a test needs behaviour beyond this, that is the signal it
 * belongs in the WordPress-backed integration suite rather than the unit one —
 * which loads real WordPress and must never see these.
 *
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

// Plugin files bail out unless they believe they are running inside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/tests/_data/fake-wp/' );
}

foreach ( array(
	'MINUTE_IN_SECONDS' => 60,
	'HOUR_IN_SECONDS'   => 3600,
	'DAY_IN_SECONDS'    => 86400,
) as $name => $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal stand-in for WordPress's WP_Error.
	 */
	class WP_Error {

		/** @var string */
		private $code;

		/** @var string */
		private $message;

		/** @var mixed */
		private $data;

		/**
		 * @param string $code    Error code.
		 * @param string $message Human-readable message.
		 * @param mixed  $data    Optional error data.
		 */
		public function __construct( string $code = '', string $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		/** @return string */
		public function get_error_code() {
			return $this->code;
		}

		/** @return string */
		public function get_error_message() {
			return $this->message;
		}

		/** @return mixed */
		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * @param mixed $thing Value to test.
	 */
	function is_wp_error( $thing ): bool {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * @param string $text Text to escape.
	 */
	function esc_html( $text ): string {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * @param string $text Text to escape.
	 */
	function esc_attr( $text ): string {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * Approximates WordPress's scheme allow-list: anything that is not an
	 * http(s) URL is rejected, which is the property BlockRenderer relies on.
	 *
	 * @param string $url URL to sanitise.
	 */
	function esc_url( $url ): string {
		$url = trim( (string) $url );
		if ( $url === '' || ! preg_match( '#^https?://#i', $url ) ) {
			return '';
		}
		return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	/**
	 * @param string $value Path or URL.
	 */
	function trailingslashit( $value ): string {
		return rtrim( (string) $value, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'untrailingslashit' ) ) {
	/**
	 * @param string $value Path or URL.
	 */
	function untrailingslashit( $value ): string {
		return rtrim( (string) $value, '/\\' );
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 */
	function __( $text, $domain = 'default' ): string { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames
		return (string) $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 */
	function esc_html__( $text, $domain = 'default' ): string {
		return esc_html( $text );
	}
}

if ( ! function_exists( 'sanitize_file_name' ) ) {
	/**
	 * @param string $filename Raw file name.
	 */
	function sanitize_file_name( $filename ): string {
		return (string) preg_replace( '/[^A-Za-z0-9._-]/', '-', (string) $filename );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * @param mixed $data  Value to encode.
	 * @param int   $flags JSON flags.
	 */
	function wp_json_encode( $data, $flags = 0 ) {
		return json_encode( $data, (int) $flags ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
	}
}

if ( ! defined( 'CBF_SI_TEST_UPLOADS' ) ) {
	define( 'CBF_SI_TEST_UPLOADS', sys_get_temp_dir() . '/cbf-si-test-uploads' );
}

if ( ! function_exists( 'wp_upload_dir' ) ) {
	/**
	 * A real, writable uploads location.
	 *
	 * PreviewRenderer maps an image directory's filesystem path onto a URL by
	 * substituting basedir for baseurl, so the basedir has to be somewhere a
	 * parser can actually write for that mapping to be worth asserting on.
	 */
	function wp_upload_dir(): array {
		if ( ! is_dir( CBF_SI_TEST_UPLOADS ) ) {
			mkdir( CBF_SI_TEST_UPLOADS, 0777, true );
		}

		return array(
			'basedir' => CBF_SI_TEST_UPLOADS,
			'baseurl' => 'https://example.test/wp-content/uploads',
			'error'   => false,
		);
	}
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
	/**
	 * @param string $target Directory to create.
	 */
	function wp_mkdir_p( $target ): bool {
		return is_dir( $target ) || mkdir( (string) $target, 0777, true );
	}
}

if ( ! function_exists( 'wp_delete_file' ) ) {
	/**
	 * @param string $file Path to delete.
	 */
	function wp_delete_file( $file ): void {
		if ( is_file( $file ) ) {
			unlink( (string) $file );
		}
	}
}
