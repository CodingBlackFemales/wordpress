<?php
/**
 * Deprecated functions from LD 4.11.0.
 * The functions will be removed in a later version.
 *
 * @since 4.11.0
 *
 * @package LearnDash\Deprecated
 */

use LearnDash\Core\Utilities\Cast;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'learndash_get_next_lesson_redirect' ) ) {
	/**
	 * Redirects the user to the next lesson.
	 *
	 * @global WP_Post $post Global post object.
	 *
	 * @since 2.1.0
	 * @deprecated 4.11.0 This complex logic is not needed anymore, we use learndash_next_post_link() to get the next step.
	 *
	 * @param WP_Post|null $post Optional. The `WP_Post` object. Defaults to global post object. Default null.
	 *
	 * @return string Returns empty string if the next lesson's redirect link empty.
	 */
	function learndash_get_next_lesson_redirect( $post = null ) {
		_deprecated_function( __FUNCTION__, '4.11.0', 'learndash_next_post_link' );

		if ( empty( $post->ID ) ) {
			global $post;
		}

		$next = learndash_next_post_link( '', true, $post );

		if ( ! empty( $next ) ) {
			$link = $next;
		} else {
			if ( 'sfwd-topic' === $post->post_type ) {
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
					$course_id = learndash_get_course_id( $post->ID );
					$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
				} else {
					$lesson_id = learndash_get_setting( $post, 'lesson' );
				}
				$link = get_permalink( $lesson_id );
			} else {
				$course_id = learndash_get_course_id( $post );
				$link      = learndash_next_global_quiz( true, null, $course_id );
			}
		}

		if ( ! empty( $link ) ) {

			/** This filter is documented in includes/course/ld-course-progress.php */
			$link = apply_filters( 'learndash_completion_redirect', $link, $post->ID );
			if ( ! empty( $link ) ) {
				learndash_safe_redirect( $link );
			}
		}

		return '';
	}
}

if ( ! function_exists( 'learndash_next_global_quiz' ) ) {
	/**
	 * Gets the next quiz for a course.
	 *
	 * @since 2.1.0
	 * @deprecated 4.11.0 This complex logic is not needed anymore, we use learndash_next_post_link() to get the next step.
	 *
	 * @param boolean  $url     Optional. Whether to return URL for the next quiz. Default true.
	 * @param int|null $user_id Optional. User ID.  Defaults to the current logged-in user. Default null.
	 * @param int|null $id      Optional. The ID of the resource. Default null.
	 * @param array    $exclude Optional. An array of quiz IDs to exclude. Default empty array.
	 *
	 * @return int|string The ID or the URL of the quiz.
	 */
	function learndash_next_global_quiz( $url = true, $user_id = null, $id = null, $exclude = [] ) {
		_deprecated_function( __FUNCTION__, '4.11.0', 'learndash_next_post_link' );

		if ( empty( $id ) ) {
			$id = learndash_get_course_id();
		}

		if ( empty( $user_id ) ) {
			$current_user = wp_get_current_user();
			$user_id      = $current_user->ID;
		}

		$quizzes = learndash_get_global_quiz_list( $id );
		$return  = get_permalink( $id );

		if ( ! empty( $quizzes ) ) {
			foreach ( $quizzes as $quiz ) {
				if ( ! in_array( $quiz->ID, $exclude, true ) && learndash_is_quiz_notcomplete( $user_id, [ $quiz->ID => 1 ], false, $id ) && learndash_can_attempt_again( $user_id, $quiz->ID ) ) {
					if ( $url ) {
						return get_permalink( $quiz->ID );
					} else {
						return $quiz->ID;
					}
				}
			}
		}

		// Good to know:
		// Filter name `learndash_course_completion_url` does not seem correct in the context of this function.
		// But it will stay here for backward compatibility.
		// It is moved to the correct place in version 4.11.0 together with parameters updating.

		/** This filter is documented in includes/course/ld-course-functions.php */
		$return = apply_filters(
			'learndash_course_completion_url',
			Cast::to_string( $return ),
			Cast::to_int( $id ),
			0
		);

		return $return;
	}
}

if ( ! function_exists( 'learndash_ajax_mark_complete' ) ) {
	/**
	 * Handles the AJAX output to mark a quiz complete.
	 *
	 * @since 2.1.0
	 * @deprecated 4.11.0 This function was not used anywhere, and it's not useful anymore.
	 *
	 * @global WP_Post $post      Global post object.
	 *
	 * @param int|null $quiz_id   Optional. Quiz ID. Default null.
	 * @param int|null $lesson_id Optional. Lesson ID. Default null.
	 */
	function learndash_ajax_mark_complete( $quiz_id = null, $lesson_id = null ) {
		_deprecated_function( __FUNCTION__, '4.11.0', 'There is no replacement for it.' );

		if ( empty( $quiz_id ) || empty( $lesson_id ) ) {
			return;
		}

		global $post;

		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;

		$can_attempt_again = learndash_can_attempt_again( $user_id, $quiz_id );

		if ( $can_attempt_again ) {
			$link = learndash_next_lesson_quiz( false, $user_id, $lesson_id, null );
		} else {
			$link = learndash_next_lesson_quiz( false, $user_id, $lesson_id, [ $quiz_id ] );
		}

	}
}

if ( ! function_exists( 'learndash_next_lesson_quiz' ) ) {
	/**
	 * Gets the next quiz for current lesson for a user.
	 *
	 * @since 2.1.0
	 * @deprecated 4.11.0 This function was not used anywhere, and it's not useful anymore.
	 *
	 * @global WP_Post $post      Global post object.
	 *
	 * @param boolean  $url       Optional. Whether to return URL for the next quiz. Default true.
	 * @param int|null $user_id   Optional. User ID.  Defaults to the current logged-in user. Default null.
	 * @param int|null $lesson_id Optional. Lesson ID. Default null.
	 * @param array    $exclude   Optional. An array of quiz IDs to exclude. Default empty array.
	 *
	 * @return int|string The ID or the URL of the quiz.
	 */
	function learndash_next_lesson_quiz( $url = true, $user_id = null, $lesson_id = null, $exclude = [] ) {
		_deprecated_function(
			__FUNCTION__,
			'4.11.0',
			'Use learndash_user_progress_get_next_incomplete_step() or learndash_next_post_link().'
		);

		global $post;

		$return = false;

		if ( empty( $lesson_id ) ) {
			$lesson_id = $post->ID;
		}

		if ( empty( $exclude ) ) {
			$exclude = [];
		}

		if ( empty( $user_id ) ) {
			$current_user = wp_get_current_user();
			$user_id      = $current_user->ID;
		}

		$course_id = learndash_get_course_id();

		// Assumption here is the learndash_get_lesson_quiz_list returns the quizzes in the order they should be taken.
		$quizzes = learndash_get_lesson_quiz_list( $lesson_id, $user_id );
		if ( ( ! empty( $quizzes ) ) && ( is_array( $quizzes ) ) ) {
			foreach ( $quizzes as $quiz ) {
				// The logic here is we need to check all the quizzes in this lesson. If all the quizzes are complete
				// (including the current one) then we set the parent (lesson) to complete.
				if ( 'completed' == $quiz['status'] ) {
					continue;
				}

				// If not complete AND the user CAN take the quiz again...
				if ( learndash_can_attempt_again( $user_id, $quiz['post']->ID ) ) {
					$return = ( $url ) ? get_permalink( $quiz['post']->ID ) : $quiz['post']->ID;
					break;
				}

				$return = ( $url ) ? get_permalink( $quiz['post']->ID ) : $quiz['post']->ID;
				break;
			}
		}

		if ( empty( $return ) ) {
			if ( ( learndash_lesson_progression_enabled( $course_id ) ) && ( ! learndash_can_complete_step( $user_id, $lesson_id, $course_id ) ) ) {
				$return = learndash_get_step_permalink( $lesson_id, $course_id );
			} elseif ( learndash_user_is_course_children_progress_complete( $user_id, $course_id, $lesson_id ) ) {
				learndash_process_mark_complete( $user_id, $lesson_id, false, $course_id );
			}
		}

		return $return;
	}
}

if ( ! function_exists( 'learndash_course_progress_widget' ) ) {
	/**
	 * Outputs the course progress HTML for the user.
	 *
	 * @since 2.1.0
	 * @deprecated 4.11.0 This function was not used anywhere, and it's not useful anymore.
	 *
	 * @param array $atts An array of course progress attributes.
	 */
	function learndash_course_progress_widget( $atts ) {
		_deprecated_function( __FUNCTION__, '4.11.0', 'There is no replacement for it.' );

		echo learndash_course_progress( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
	}
}

if ( ! function_exists( 'learndash_update_completion') ) {
	/**
	 * Marks a resource complete.
	 *
	 * @since 2.1.0
	 * @deprecated 4.11.0 This function was not used anywhere, and it's not useful anymore.
	 *
	 * @param int $user_id Optional. User ID. Default null.
	 * @param int $postid  Optional. The ID of the resource. Default null.
	 */
	function learndash_update_completion( $user_id = null, $postid = null ) {
		_deprecated_function( __FUNCTION__, '4.11.0', 'learndash_process_mark_complete' );

		if ( empty( $postid ) ) {
			global $post;
			if ( ( $post ) && ( is_a( $post, 'WP_Post' ) ) ) {
				$postid = $post->ID;
			}
		}

		if ( ! empty( $postid ) ) {
			learndash_process_mark_complete( $user_id, $postid, true );
		}
	}
}
