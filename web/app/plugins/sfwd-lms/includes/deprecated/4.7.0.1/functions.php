<?php
/**
 * Deprecated functions from LD 4.7.0.1.
 * The functions will be removed in a later version.
 *
 * @since 4.7.0.1
 *
 * @package LearnDash\Deprecated
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'learndash_get_course_enrollment_url' ) ) {
	/**
	 * Returns course enrollment url.
	 *
	 * @param WP_Post|int|null $post Post or Post ID.
	 *
	 * @since 4.1.0
	 * @deprecated 4.7.0.1
	 *
	 * @return string
	 */
	function learndash_get_course_enrollment_url( $post ): string {
		_deprecated_function( __FUNCTION__, '4.7.0.1', 'Learndash_Payment_Gateway::get_url_success' );

		if ( empty( $post ) ) {
			return '';
		}

		if ( is_int( $post ) ) {
			$post = get_post( $post );

			if ( is_null( $post ) ) {
				return '';
			}
		}

		$url = get_permalink( $post );

		$settings = learndash_get_setting( $post );

		if ( 'paynow' === $settings['course_price_type'] && ! empty( $settings['course_price_type_paynow_enrollment_url'] ) ) { // @phpstan-ignore-line -- Deprecated.
			$url = $settings['course_price_type_paynow_enrollment_url']; // @phpstan-ignore-line -- Deprecated.
		} elseif ( 'subscribe' === $settings['course_price_type'] && ! empty( $settings['course_price_type_subscribe_enrollment_url'] ) ) { // @phpstan-ignore-line -- Deprecated.
			$url = $settings['course_price_type_subscribe_enrollment_url']; // @phpstan-ignore-line -- Deprecated.
		}

		/** This filter is documented in includes/course/ld-course-functions.php */
		return apply_filters( 'learndash_course_join_redirect', $url, $post->ID );
	}
}

if ( ! function_exists( 'learndash_get_group_enrollment_url' ) ) {
	/**
	 * Returns group enrollment url.
	 *
	 * @since 4.1.0
	 * @deprecated 4.7.0.1
	 *
	 * @param WP_Post|int|null $post Post or Post ID.
	 *
	 * @return string
	 */
	function learndash_get_group_enrollment_url( $post ): string {
		_deprecated_function( __FUNCTION__, '4.7.0.1', 'Learndash_Payment_Gateway::get_url_success' );

		if ( empty( $post ) ) {
			return '';
		}

		if ( is_int( $post ) ) {
			$post = get_post( $post );

			if ( is_null( $post ) ) {
				return '';
			}
		}

		$url = get_permalink( $post );

		$settings = learndash_get_setting( $post );

		if ( 'paynow' === $settings['group_price_type'] && ! empty( $settings['group_price_type_paynow_enrollment_url'] ) ) { // @phpstan-ignore-line -- Deprecated.
			$url = $settings['group_price_type_paynow_enrollment_url']; // @phpstan-ignore-line -- Deprecated.
		} elseif ( 'subscribe' === $settings['group_price_type'] && ! empty( $settings['group_price_type_subscribe_enrollment_url'] ) ) { // @phpstan-ignore-line -- Deprecated.
			$url = $settings['group_price_type_subscribe_enrollment_url']; // @phpstan-ignore-line -- Deprecated.
		}

		/** This filter is documented in includes/course/ld-course-functions.php */
		return apply_filters( 'learndash_group_join_redirect', $url, $post->ID );
	}
}
