<?php
/**
 * LearnDash Theme Helper functions.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the Learndash content wrapper CSS class.
 * Filterable function to add a class to all LearnDash content, allows conditional adding of additional classes.
 *
 * @since 3.0.0
 * @since 4.6.0 Added the optional `$additional_classes` parameter.
 *
 * @global WP_Post $post Global post object.
 *
 * @param int|WP_Post|null $post               `WP_Post` object or post ID. Default to global $post.
 * @param string           $additional_classes Additional classes to add to the wrapper.
 *
 * @return string Wrapper CSS class.
 */
function learndash_get_wrapper_class( $post = null, string $additional_classes = '' ): string {
	if ( null === $post ) {
		global $post;
	}

	if ( is_numeric( $post ) ) {
		$post = get_post( (int) $post ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- I suppose it's what they wanted.
	}

	/**
	 * Filters LearnDash content wrapper class.
	 *
	 * @since 3.0.0
	 * @since 4.6.0 Added the optional `$additional_classes` parameter.
	 *
	 * @param string     $wrapper_class      Wrapper class.
	 * @param int|object $post               Post ID or post object.
	 * @param string     $additional_classes Additional classes to add to the wrapper.
	 */
	return apply_filters( 'learndash_wrapper_class', 'learndash-wrapper', $post, $additional_classes );
}

/**
 * Escapes and outputs the learndash_get_wrapper_class function result.
 *
 * @since 3.0.0
 * @since 4.6.0 Added the optional `$additional_classes` parameter.
 *
 * @param int|WP_Post|null $post               `WP_Post` object or post ID. Default to global $post.
 * @param string           $additional_classes Additional classes to add to the wrapper.
 *
 * @return void Outputs the Learndash content wrapper CSS class.
 */
function learndash_the_wrapper_class( $post = null, string $additional_classes = '' ): void {
	echo esc_attr(
		learndash_get_wrapper_class( $post, $additional_classes )
	);
}
