<?php
/**
 * LearnDash LD30 Displays an informational bar
 *
 * This will have to be variable based on the current users context.
 * Different information is passed in based on if they are on a course, lesson,
 * topic etc...
 *
 * Having it in one place is advantageous over multiple instances of the status
 * bar for Gutenberg block placement.
 *
 * Available Variables:
 *
 * $course_status : Course Status
 *
 * $user_id      : Current User ID
 * $logged_in     : User is logged in
 * $current_user  : (object) Currently logged in user object
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30\Modules
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thought process:
 *
 * Have some function that checks for the existance of a post type specific
 * variant of a template and falls back to a generic one if it doesn't
 * exist.
 *
 * e.g
 *
 * get_contextualized_template( $slug, $string );
 *
 * if( file_exists( $slug . '-' . $string . '.php' ) ) {
 *      return $slug . '-' . $string . '.php';
 * } else {
 *      return $slug . '-' . 'generic.php';
 * }
 */

/**
 * Fires before the infobar (all locations).
 *
 * @since 3.0.0
 *
 * @param string|false $post_type Post type slug.
 * @param int          $user_id   User ID.
 */
do_action( 'learndash-all-infobar-before', get_post_type(), $user_id );

learndash_get_template_part( 'infobar', get_post_type() );

/**
 * Fires after the infobar (all locations).
 *
 * @since 3.0.0
 *
 * @param string|false $post_type Post type slug.
 * @param int          $user_id   User ID.
 */
do_action( 'learndash-all-infobar-after', get_post_type(), $user_id );


if ( $logged_in ) :

	/**
	 * User is logged in - can contextualize
	 *
	 * Some logic to determine if this is a course lesson, topic, quiz, etc...
	 */

	// TODO: Needs to be a filterable template call with more elegant fallback.
	if ( file_exists( 'infobar-' . get_post_type() . '.php' ) ) {
		include 'infobar-' . get_post_type() . '.php';
	} else {
		include __DIR__ . '/infobar-generic.php';
	}

else :

	/**
	 * User isn't logged in - can't contextualize
	 */

endif;
