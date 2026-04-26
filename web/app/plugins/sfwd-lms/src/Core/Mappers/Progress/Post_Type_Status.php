<?php
/**
 * LearnDash Post Type Progress Status mapper class.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Mappers\Progress;

use LDLMS_Post_Types;

/**
 * Class to handle post type progress status mapping.
 *
 * @since 5.0.0
 */
class Post_Type_Status {
	/**
	 * Returns available statuses for a specific post type.
	 *
	 * Note: Some of these come from the global variables $learndash_course_statuses, $learndash_exam_challenge_statuses.
	 * We want to move away from globals and have a single source of truth for progress statuses.
	 * This is the preferred way to map the progress statuses for a specific post type.
	 *
	 * @since 5.0.0
	 *
	 * @param string $post_type The post type to get statuses for.
	 *
	 * @return array<string,string> Array of progress statuses with keys and labels. Empty array if no statuses are found.
	 */
	public static function get_statuses( string $post_type ): array {
		global $learndash_course_statuses, $learndash_exam_challenge_statuses;

		$statuses = [];

		switch ( $post_type ) {
			case LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ):
				$statuses = ! empty( $learndash_course_statuses ) ? $learndash_course_statuses : [];
				break;

			case LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC ):
			case LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON ):
				$statuses = [
					'not_started' => esc_html__( 'Not Started', 'learndash' ),
					'in_progress' => esc_html__( 'In Progress', 'learndash' ),
					'completed'   => esc_html__( 'Completed', 'learndash' ),
				];
				break;

			case LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ):
				$statuses = [
					'not_started' => esc_html__( 'Not Started', 'learndash' ),
					'in_progress' => esc_html__( 'In Progress', 'learndash' ),
					'passed'      => esc_html__( 'Passed', 'learndash' ),
					'failed'      => esc_html__( 'Failed', 'learndash' ),
				];
				break;

			case LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::EXAM ):
				$statuses = ! empty( $learndash_exam_challenge_statuses ) ? $learndash_exam_challenge_statuses : [];
				break;

			default:
				$statuses = [];
				break;
		}

		/**
		 * Filters the progress statuses for a specific post type.
		 *
		 * @since 5.0.0
		 *
		 * @param array<string,string> $statuses  Array of progress statuses with keys and labels. Empty array if no progress statuses are found for the post type.
		 * @param string               $post_type The post type slug.
		 *
		 * @return array<string,string> Array of progress statuses with keys and labels. Empty array if no progress statuses are found for the post type.
		 */
		return apply_filters( 'learndash_post_type_progress_statuses', $statuses, $post_type );
	}
}
