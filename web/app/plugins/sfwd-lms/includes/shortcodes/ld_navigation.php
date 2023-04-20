<?php
/**
 * LearnDash `[ld_navigation]` shortcode processing.
 *
 * @since 4.0.0
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[ld_navigation]` shortcode output.
 *
 * Shortcode that shows the course navigation for previous step, next step, and return to course links.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 4.0.0
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type int     $course_id Course ID. Default false.
 *    @type int     $post_id   Course Step ID. Default false.
 *    @type int     $user_id   User ID. Default false.
 * }
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'ld_navigation'.
 *
 * @return string The `ld_navigation` shortcode output.
 */
function learndash_navigation_shortcode( $atts = array(), $content = '', $shortcode_slug = 'ld_navigation' ) {
	if ( learndash_is_active_theme( 'legacy' ) ) {
		return $content;
	}

	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	static $shown_content = array();

	$atts = shortcode_atts(
		array(
			'course_id' => false,
			'post_id'   => false,
			'user_id'   => false,
		),
		$atts
	);

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$atts = apply_filters( 'learndash_shortcode_atts', $atts, $shortcode_slug );

	if ( false === $atts['course_id'] ) {
		$course_id = learndash_get_course_id();
		if ( ! empty( $course_id ) ) {
			$atts['course_id'] = intval( $course_id );
		}
	}

	if ( false === $atts['post_id'] ) {
		$post_id = get_the_ID();
		if ( ! empty( $post_id ) ) {
			$post_type = get_post_type( $post_id );
			if ( in_array( $post_type, learndash_get_post_types( 'course' ), true ) ) {
				$atts['post_id'] = absint( $post_id );
			}
		}
	}

	if ( false === $atts['user_id'] ) {
		if ( is_user_logged_in() ) {
			$atts['user_id'] = get_current_user_id();
		}
	}

	$atts['user_id']   = absint( $atts['user_id'] );
	$atts['course_id'] = absint( $atts['course_id'] );
	$atts['post_id']   = absint( $atts['post_id'] );

	// Only for Lessons or Topics. Use on Quiz or Course will not display anythings.
	if ( ! empty( $atts['post_id'] ) ) {
		$post_type = get_post_type( $atts['post_id'] );
		if ( ( empty( $post_type ) ) || ( ! in_array( $post_type, learndash_get_post_type_slug( array( 'lesson', 'topic' ) ), true ) ) ) {
			return $content;
		}
	}

	$shown_content_key = $atts['course_id'] . '_' . $atts['post_id'] . '_' . $atts['user_id'];

	$shown_content[ $shown_content_key ] = '';

	if ( ( ! empty( $atts['course_id'] ) ) && ( ! empty( $atts['post_id'] ) ) ) {
		$can_complete = false;
		if ( ! empty( $atts['user_id'] ) ) {
			if ( ( true !== learndash_lesson_progression_enabled( $atts['course_id'] ) ) || ( true === learndash_can_user_bypass( $atts['user_id'], 'learndash_course_progression' ) ) ) {
				$incomplete_child_steps = learndash_user_progression_get_incomplete_child_steps( $atts['user_id'], $atts['course_id'], $atts['post_id'] );
				if ( empty( $incomplete_child_steps ) ) {
					$can_complete = true;
				} else {
					$can_complete = false;
				}
			} else {
				$can_complete = learndash_can_complete_step( $atts['user_id'], $atts['post_id'], $atts['course_id'], true );
			}
		}

		/**
		 * Filters whether a user can complete the lesson or not.
		 *
		 * @since 3.0.0
		 *
		 * @param boolean $can_complete Whether user can complete lesson or not.
		 * @param int     $post_id      Lesson ID/Topic ID.
		 * @param int     $course_id    Course ID.
		 * @param int     $user_id      User ID.
		 */
		$can_complete = apply_filters( 'learndash-lesson-can-complete', $can_complete, $atts['post_id'], $atts['course_id'], $atts['user_id'] ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

		$context = learndash_get_post_type_key( get_post_type( $atts['post_id'] ) );

		$level = ob_get_level();
		ob_start();

		SFWD_LMS::get_template(
			'modules/course-steps.php',
			array(
				'course_id'        => absint( $atts['course_id'] ),
				'course_step_post' => get_post( $atts['post_id'] ),
				'user_id'          => absint( $atts['user_id'] ),
				'course_settings'  => learndash_get_setting( $atts['course_id'] ),
				'can_complete'     => $can_complete,
				'context'          => $context,
			),
			true
		);

		$shortcode_out = learndash_ob_get_clean( $level );
		if ( ! empty( $shortcode_out ) ) {
			$shown_content[ $shown_content_key ] .= '<div class="learndash-wrapper learndash-shortcode-wrap-' . esc_attr( $shortcode_slug ) . '-' . esc_attr( $shown_content_key ) . '">' . $shortcode_out . '</div>';
		}
	}

	if ( ( isset( $shown_content[ $shown_content_key ] ) ) && ( ! empty( $shown_content[ $shown_content_key ] ) ) ) {
		$content .= $shown_content[ $shown_content_key ];
	}

	return $content;
}
add_shortcode( 'ld_navigation', 'learndash_navigation_shortcode', 10, 3 );
