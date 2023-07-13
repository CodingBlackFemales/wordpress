<?php
/**
 * Deprecated functions from LD 4.6.0.
 * The functions will be removed in a later version.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Deprecated
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'learndash_the_breadcrumbs' ) ) {
	/**
	 * Prints breadcrumbs output.
	 *
	 * Sames as learndash_get_breadcrumbs only it actually outputs escaped markup.
	 *
	 * @since 3.0.0
	 * @deprecated 4.6.0
	 *
	 * @global WP_Post $post Global post object.
	 *
	 * @param int|WP_Post|null $post `WP_Post` object. Default to global $post.
	 *
	 * @return void
	 */
	function learndash_the_breadcrumbs( $post = null ): void {
		_deprecated_function( __FUNCTION__, '4.6.0', 'learndash_get_breadcrumbs' );

		// Do nothing. It never worked properly according to the code inside it, and it was not used at the deprecation time.
	}
}
