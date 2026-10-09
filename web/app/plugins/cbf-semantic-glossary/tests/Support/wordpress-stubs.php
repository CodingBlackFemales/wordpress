<?php
/**
 * The slice of WordPress the plugin's pure classes touch.
 *
 * Included only by the Unit suite. Each stub mirrors the real function closely
 * enough for assertions about escaping and markup to mean something, and no
 * further. Filters are real enough to test the plugin's hooks: add_filter()
 * registers, apply_filters() runs, and the Filters helper clears them after
 * every test.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

declare( strict_types=1 );

// Plugin files bail out unless they believe they are running inside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/_data/fake-wp/' );
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal stand-in for WordPress's WP_Error.
	 */
	class WP_Error {

		/**
		 * Constructor.
		 *
		 * @param string $code    Error code.
		 * @param string $message Message.
		 * @param mixed  $data    Data.
		 */
		public function __construct( public string $code = '', public string $message = '', public $data = '' ) {}

		/** Error message. */
		public function get_error_message(): string {
			return $this->message;
		}
	}
}

/**
 * Callbacks registered with add_filter(), by hook.
 *
 * @var array<string, array<int, callable>>
 */
$GLOBALS['cbf_glossary_test_filters'] = array();

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Register a filter. Priorities are ignored: callbacks run in the order added.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 */
	function add_filter( $hook, $callback ): bool {
		$GLOBALS['cbf_glossary_test_filters'][ $hook ][] = $callback;
		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Run a hook's filters.
	 *
	 * @param string $hook  Hook name.
	 * @param mixed  $value Value to filter.
	 * @param mixed  ...$args Extra arguments.
	 * @return mixed
	 */
	function apply_filters( $hook, $value, ...$args ) {
		foreach ( $GLOBALS['cbf_glossary_test_filters'][ $hook ] ?? array() as $callback ) {
			$value = $callback( $value, ...$args );
		}
		return $value;
	}
}

/**
 * Stored options, for tests that need get_option() to return something.
 *
 * @var array<string, mixed>
 */
$GLOBALS['cbf_glossary_test_options'] = array();

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Read an option from the test store.
	 *
	 * @param string $name    Option name.
	 * @param mixed  $default Value when unset.
	 * @return mixed
	 */
	function get_option( $name, $default = false ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames
		return $GLOBALS['cbf_glossary_test_options'][ $name ] ?? $default;
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * Whether a value is an error.
	 *
	 * @param mixed $thing Value.
	 */
	function is_wp_error( $thing ): bool {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Escape for HTML text.
	 *
	 * @param string $text Text.
	 */
	function esc_html( $text ): string {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Escape for an HTML attribute.
	 *
	 * @param string $text Text.
	 */
	function esc_attr( $text ): string {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * Allows http(s), mailto, fragments and root-relative URLs, as the plugin
	 * produces; anything else (javascript:, data:) is dropped, like WordPress.
	 *
	 * @param string $url URL.
	 */
	function esc_url( $url ): string {
		$url = trim( (string) $url );
		if ( ! preg_match( '#^(https?://|mailto:|\#|/)#i', $url ) ) {
			return '';
		}
		return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Translate (identity).
	 *
	 * @param string $text   Text.
	 * @param string $domain Text domain.
	 */
	function __( $text, $domain = 'default' ): string { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames
		return (string) $text;
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	/**
	 * Translate and escape for an attribute.
	 *
	 * @param string $text   Text.
	 * @param string $domain Text domain.
	 */
	function esc_attr__( $text, $domain = 'default' ): string {
		return esc_attr( $text );
	}
}

if ( ! function_exists( 'wp_kses' ) ) {
	/**
	 * Strip tags not in the allowed list; attributes on allowed tags are kept.
	 *
	 * Enough to check that definitions cannot carry scripts or block-level
	 * markup; it does not filter attributes the way the real function does.
	 *
	 * @param string               $html    Markup.
	 * @param array<string, mixed> $allowed Allowed tags.
	 */
	function wp_kses( $html, $allowed ): string {
		$html = (string) preg_replace( '#<(script|style)\b.*?</\1>#is', '', (string) $html );
		return strip_tags( $html, array_keys( $allowed ) );
	}
}

if ( ! function_exists( 'wpautop' ) ) {
	/**
	 * Wrap blank-line-separated blocks of text in paragraphs.
	 *
	 * @param string $text Text.
	 */
	function wpautop( $text ): string {
		$blocks = array_filter( array_map( 'trim', (array) preg_split( '/\n\s*\n/', trim( (string) $text ) ) ), 'strlen' );
		return implode( "\n", array_map( fn ( $block ) => '<p>' . $block . '</p>', $blocks ) ) . "\n";
	}
}

if ( ! function_exists( 'remove_accents' ) ) {
	/**
	 * Transliterate accented Latin letters to ASCII.
	 *
	 * @param string $text Text.
	 */
	function remove_accents( $text ): string {
		$decomposed = Normalizer::normalize( (string) $text, Normalizer::FORM_D );
		return (string) preg_replace( '/\p{Mn}/u', '', (string) $decomposed );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	/**
	 * Lower-case, hyphen-separated slug.
	 *
	 * @param string $title Title.
	 */
	function sanitize_title( $title ): string {
		return trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( remove_accents( (string) $title ) ) ), '-' );
	}
}
