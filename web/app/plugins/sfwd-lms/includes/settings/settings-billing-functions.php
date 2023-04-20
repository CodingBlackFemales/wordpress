<?php
/**
 * LearnDash Settings billing functions
 *
 * @since 3.5.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Billing Cycle field html output for courses
 *
 * @since 3.5.0
 *
 * @param int    $post_id   Post ID.
 * @param string $post_type Post type slug.
 *
 * @return string HTML input and selector for billing cycle field.
 */
function learndash_billing_cycle_setting_field_html( int $post_id = 0, string $post_type = '' ): string {
	$post_id = absint( $post_id );
	if ( empty( $post_id ) ) {
		if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_id = absint( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}

	$post_type = esc_attr( $post_type );
	if ( empty( $post_type ) ) {
		if ( ! empty( $post_id ) ) {
			$post_type = get_post_type( $post_id );
		}
		if ( ( empty( $post_type ) ) && ( isset( $_GET['post_type'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_type = esc_attr( $_GET['post_type'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		}
	}

	$price_billing_p3 = '';
	$price_billing_t3 = '';

	if ( learndash_get_post_type_slug( 'course' ) === $post_type ) {
		$settings_prefix = 'course';
	} elseif ( learndash_get_post_type_slug( 'group' ) === $post_type ) {
		$settings_prefix = 'group';
	} else {
		$settings_prefix = '';
	}

	if ( ! empty( $post_id ) ) {
		$price_billing_t3 = learndash_get_setting( $post_id, $settings_prefix . '_price_billing_t3' );
		$price_billing_p3 = learndash_get_setting( $post_id, $settings_prefix . '_price_billing_p3' );
	}

	$html  = '<input name="' . $settings_prefix . '_price_billing_p3" type="number" value="' . $price_billing_p3 . '" class="small-text" min="0" can_empty="1" />';
	$html .= '<select class="select_course_price_billing_p3" name="' . $settings_prefix . '_price_billing_t3">';
	$html .= '<option value="">' . esc_html__( 'select interval', 'learndash' ) . '</option>';
	$html .= '<option value="D" ' . selected( $price_billing_t3, 'D', false ) . '>' . esc_html__( 'day(s)', 'learndash' ) . '</option>';
	$html .= '<option value="W" ' . selected( $price_billing_t3, 'W', false ) . '>' . esc_html__( 'week(s)', 'learndash' ) . '</option>';
	$html .= '<option value="M" ' . selected( $price_billing_t3, 'M', false ) . '>' . esc_html__( 'month(s)', 'learndash' ) . '</option>';
	$html .= '<option value="Y" ' . selected( $price_billing_t3, 'Y', false ) . '>' . esc_html__( 'year(s)', 'learndash' ) . '</option>';
	$html .= '</select>';

	/**
	 * Filters billing cycle settings field html.
	 *
	 * @since 3.5.0
	 *
	 * @param string $html      HTML content for settings field.
	 * @param int    $post_id   Post ID.
	 * @param string $post_type Post type slug.
	 */
	return apply_filters( 'learndash_billing_cycle_settings_field_html', $html, $post_id, $post_type );
}

/**
 * Trial duration field html output for courses
 *
 * @since 3.6.0
 *
 * @param int    $post_id   Post ID.
 * @param string $post_type Post type slug.
 *
 * @return string HTML input and selector for trial duration field.
 */
function learndash_trial_duration_setting_field_html( int $post_id = 0, string $post_type = '' ): string {
	$post_id = absint( $post_id );
	if ( empty( $post_id ) ) {
		if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_id = absint( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}

	$post_type = esc_attr( $post_type );
	if ( empty( $post_type ) ) {
		if ( ! empty( $post_id ) ) {
			$post_type = get_post_type( $post_id );
		}
		if ( ( empty( $post_type ) ) && ( isset( $_GET['post_type'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_type = esc_attr( $_GET['post_type'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		}
	}

	$trial_duration_p1 = '';
	$trial_duration_t1 = '';

	if ( learndash_get_post_type_slug( 'course' ) === $post_type ) {
		$settings_prefix = 'course';
	} elseif ( learndash_get_post_type_slug( 'group' ) === $post_type ) {
		$settings_prefix = 'group';
	} else {
		$settings_prefix = '';
	}

	if ( ! empty( $post_id ) ) {
		$trial_duration_t1 = learndash_get_setting( $post_id, $settings_prefix . '_trial_duration_t1' );
		$trial_duration_p1 = learndash_get_setting( $post_id, $settings_prefix . '_trial_duration_p1' );
	}

	$html  = '<input name="' . $settings_prefix . '_trial_duration_p1" type="number" value="' . $trial_duration_p1 . '" class="small-text" min="0" can_empty="1" />';
	$html .= '<select class="select_course_price_billing_p3" name="' . $settings_prefix . '_trial_duration_t1">';
	$html .= '<option value="">' . esc_html__( 'select interval', 'learndash' ) . '</option>';
	$html .= '<option value="D" ' . selected( $trial_duration_t1, 'D', false ) . '>' . esc_html__( 'day(s)', 'learndash' ) . '</option>';
	$html .= '<option value="W" ' . selected( $trial_duration_t1, 'W', false ) . '>' . esc_html__( 'week(s)', 'learndash' ) . '</option>';
	$html .= '<option value="M" ' . selected( $trial_duration_t1, 'M', false ) . '>' . esc_html__( 'month(s)', 'learndash' ) . '</option>';
	$html .= '<option value="Y" ' . selected( $trial_duration_t1, 'Y', false ) . '>' . esc_html__( 'year(s)', 'learndash' ) . '</option>';
	$html .= '</select>';

	/**
	 * Filters trial duration settings field html.
	 *
	 * @since 3.6.0
	 *
	 * @param string $html      HTML content for settings field.
	 * @param int    $post_id   Post ID.
	 * @param string $post_type Post type slug.
	 */
	return apply_filters( 'learndash_trial_duration_settings_field_html', $html, $post_id, $post_type );
}

/**
 * Validate the billing cycle field frequency.
 *
 * @since 3.5.0
 *
 * @param string $price_billing_t3 Billing frequency code. D, W, M, or Y.
 *
 * @return string Valid frequency or empty string.
 */
function learndash_billing_cycle_field_frequency_validate( string $price_billing_t3 ): string {
	$price_billing_t3 = strtoupper( $price_billing_t3 );

	if ( ! in_array( $price_billing_t3, array( 'D', 'W', 'M', 'Y' ), true ) ) {
		$price_billing_t3 = '';
	}

	return $price_billing_t3;
}

/**
 * Validate the Billing cycle field interval.
 *
 * @since 3.5.0
 *
 * @param int    $price_billing_p3 The Billing field value.
 * @param string $price_billing_t3 The Billing field context. D, M, W, or Y.
 *
 * @return int Valid interval or zero.
 */
function learndash_billing_cycle_field_interval_validate( int $price_billing_p3, string $price_billing_t3 ): int {
	$price_billing_t3     = learndash_billing_cycle_field_frequency_validate( $price_billing_t3 );
	$price_billing_p3_max = learndash_billing_cycle_field_frequency_max( $price_billing_t3 );

	switch ( $price_billing_t3 ) {
		case 'W':
		case 'M':
		case 'Y':
		case 'D':
			if ( $price_billing_p3 < 1 ) {
				$price_billing_p3 = 1;
			} elseif ( $price_billing_p3 > $price_billing_p3_max ) {
				$price_billing_p3 = $price_billing_p3_max;
			}
			break;

		default:
			$price_billing_p3 = 0;
	}

	return $price_billing_p3;
}

/**
 * Get the billing cycle field max value for frequency.
 *
 * @since 3.5.0
 *
 * @param string $price_billing_t3 The Billing field context. D, M, W, or Y.
 *
 * @return int Valid interval or zero.
 */
function learndash_billing_cycle_field_frequency_max( string $price_billing_t3 ): int {
	switch ( $price_billing_t3 ) {
		case 'D':
			$price_billing_p3 = 90;
			break;

		case 'W':
			$price_billing_p3 = 52;
			break;

		case 'M':
			$price_billing_p3 = 24;
			break;

		case 'Y':
			$price_billing_p3 = 5;
			break;

		default:
			$price_billing_p3 = 0;
	}

	return $price_billing_p3;
}
