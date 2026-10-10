<?php
/**
 * WordPress stubs this plugin needs beyond core's.
 *
 * Core's tests/Support/wordpress-stubs.php is loaded first and provides
 * escaping, translation, filters and options. Each stub here is guarded, since
 * at the repository root every plugin's suite shares one PHP process.
 *
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

declare( strict_types=1 );

if ( ! function_exists( '_n' ) ) {
	/**
	 * Pick the singular or plural form.
	 *
	 * @param string $single Singular form.
	 * @param string $plural Plural form.
	 * @param int    $number Quantity.
	 * @param string $domain Text domain.
	 */
	function _n( $single, $plural, $number, $domain = 'default' ): string {
		return (string) ( (int) $number === 1 ? $single : $plural );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Translate and escape for HTML.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 */
	function esc_html__( $text, $domain = 'default' ): string {
		return esc_html( $text );
	}
}
