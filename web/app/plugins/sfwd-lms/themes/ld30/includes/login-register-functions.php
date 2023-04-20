<?php
/**
 * LearnDash LD30 Login and Registration functions
 *
 * Handles authentication, registering, resetting passwords and other user handling.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LOGIN FUNCTIONS
 */

/**
 * Adds a hidden form field to the login form.
 *
 * Fires on `login_form_top` hook.
 *
 * @since 3.0.0
 *
 * @param string $content Login form content.
 *
 * @return string Login form hidden field content.
 */
function learndash_add_login_field_top( $content = '' ) {
	$content .= '<input id="learndash-login-form" type="hidden" name="learndash-login-form" value="' . wp_create_nonce( 'learndash-login-form' ) . '" />';

	$post_type = get_post_type( get_the_ID() );
	if ( in_array( $post_type, learndash_get_post_types( 'course' ), true ) ) {
		$course_id = learndash_get_course_id( get_the_ID() );

		/**
		 * Filters whether to allow enrollment of course with the login. The default value is true.
		 *
		 * @since 3.1.0
		 *
		 * @param boolean $include_course Whether to allow login from the course.
		 * @param int     $course_id      Course ID.
		 */
		if ( ( ! empty( $course_id ) ) && ( in_array( learndash_get_setting( $course_id, 'course_price_type' ), array( 'free' ), true ) ) && ( apply_filters( 'learndash_login_form_include_course', true, $course_id ) ) ) {
			$content .= '<input name="learndash-login-form-course" value="' . $course_id . '" type="hidden" />';
			$content .= wp_nonce_field( 'learndash-login-form-course-' . $course_id . '-nonce', 'learndash-login-form-course-nonce', false, false );
		}
	} elseif ( in_array( $post_type, array( learndash_get_post_type_slug( 'group' ) ), true ) ) {
		$group_id = get_the_ID();

		/**
		 * Filters whether to allow enrollment of group with the login. The default value is true.
		 *
		 * @since 3.2.0
		 *
		 * @param boolean $include_group Whether to allow login from the group.
		 * @param int     $group_id       Group ID.
		 */
		if ( ( ! empty( $group_id ) ) && ( in_array( learndash_get_setting( $group_id, 'group_price_type' ), array( 'free' ), true ) ) && ( apply_filters( 'learndash_login_form_include_group', true, $group_id ) ) ) {
			$content .= '<input name="learndash-login-form-post" value="' . $group_id . '" type="hidden" />';
			$content .= wp_nonce_field( 'learndash-login-form-post-' . $group_id . '-nonce', 'learndash-login-form-post-nonce', false, false );
		}
	}

	return $content;
}

// Add a filter for validation returns.
add_filter( 'login_form_top', 'learndash_add_login_field_top' );
