<?php
/**
 * Deprecated functions from LD 3.2.0
 * The functions will be removed in a later version.
 *
 * @package LearnDash\Deprecated
 * @since 3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'post2pdf_conv_post_to_pdf' ) ) {
	/**
	 * Converts post data to pdf.
	 *
	 * @deprecated 3.2 Use {@see 'learndash_certificate_post_shortcode'} instead.
	 */
	function post2pdf_conv_post_to_pdf() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.0', 'learndash_certificate_post_shortcode' );
		}

		return learndash_certificate_post_shortcode();
	}
}
