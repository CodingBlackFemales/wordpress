<?php
/**
 * This class provides the easy way to operate an assignment.
 *
 * @since 4.24.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models;

use LDLMS_Post_Types;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Infrastructure\File_Protection\File_Download_Handler;
use Exception;
use LearnDash\Core\Mappers\Models\Step_Mapper;
use LearnDash\Core\Models;
use WP_User;

/**
 * Assignment model class.
 *
 * @since 4.24.0
 */
class Assignment extends Post {
	/**
	 * Returns allowed post types.
	 *
	 * @since 4.24.0
	 *
	 * @return string[]
	 */
	public static function get_allowed_post_types(): array {
		return [
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::ASSIGNMENT ),
		];
	}

	/**
	 * Returns whether the assignment is approved.
	 *
	 * @since 4.24.0
	 *
	 * @return bool Whether the assignment is approved.
	 */
	public function is_approved(): bool {
		$is_approved = Cast::to_bool( $this->getAttribute( 'approval_status' ) );

		/**
		 * Filters whether the assignment is approved.
		 *
		 * @since 4.24.0
		 *
		 * @param bool       $is_approved Whether the assignment is approved.
		 * @param Assignment $assignment  Assignment model.
		 *
		 * @return bool Whether the assignment is approved.
		 */
		return apply_filters(
			'learndash_model_assignment_is_approved',
			$is_approved,
			$this
		);
	}

	/**
	 * Returns the assignment download URL.
	 *
	 * @since 4.24.0
	 *
	 * @return string The assignment download URL. Empty string if the assignment has no file or the file is not available.
	 */
	public function get_download_url(): string {
		$download_url = '';

		if ( ! empty( $this->getAttribute( 'file_name' ) ) ) {
			try {
				$download_url = File_Download_Handler::get_download_url(
					$this->getAttribute( 'learndash_version' ) ? 'uploads_learndash_assignments' : 'uploads_assignments', // Since LD version 4.10.3 we've changed the path ID and added the learndash_version meta.
					Cast::to_string( $this->getAttribute( 'file_name' ) )
				);
			} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Ignore.
				// We use it on the frontend, so we don't want to throw an exception.
			}
		}

		/**
		 * Filters the assignment download URL.
		 *
		 * @since 4.24.0
		 *
		 * @param string     $download_url The download URL.
		 * @param Assignment $assignment   Assignment model.
		 *
		 * @return string The assignment download URL.
		 */
		return apply_filters(
			'learndash_model_assignment_download_url',
			$download_url,
			$this
		);
	}

	/**
	 * Returns whether the assignment can be deleted.
	 *
	 * @since 4.24.0
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return bool Whether the assignment can be deleted.
	 */
	public function can_be_deleted( $user = null ): bool {
		$user    = $this->map_user( $user );
		$user_id = $user instanceof WP_User ? $user->ID : $user;

		$assignment_author_id = $this->get_post_author_id();

		$related_step = $this->get_related_step();

		$can_be_deleted = ! $this->is_approved()
			&& (
				learndash_is_admin_user( $user_id )
				|| learndash_is_group_leader_of_user( $user_id, $assignment_author_id )
				|| (
					$assignment_author_id > 0
					&& $user_id === $assignment_author_id
					&& $related_step
					&& 'on' === $related_step->get_setting( 'lesson_assignment_deletion_enabled' )
				)
			);

		/**
		 * Filters whether the assignment can be deleted.
		 *
		 * @since 4.24.0
		 *
		 * @param bool        $can_be_deleted Whether the assignment can be deleted.
		 * @param Assignment  $assignment     Assignment model.
		 * @param WP_User|int $user           The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return bool Whether the assignment can be deleted.
		 */
		return apply_filters(
			'learndash_model_assignment_can_be_deleted',
			$can_be_deleted,
			$this,
			$user
		);
	}

	/**
	 * Returns the points awarded.
	 *
	 * @since 4.24.0
	 *
	 * @return float The points awarded.
	 */
	public function get_points_awarded(): float {
		/**
		 * Filters the points awarded.
		 *
		 * @since 4.24.0
		 *
		 * @param float      $points     The points awarded.
		 * @param Assignment $assignment The assignment model.
		 *
		 * @return float The points awarded.
		 */
		return apply_filters(
			'learndash_model_assignment_points_awarded',
			Cast::to_float(
				$this->getAttribute( 'points' )
			),
			$this
		);
	}

	/**
	 * Returns the deletion URL.
	 *
	 * @since 4.24.0
	 *
	 * @return string The deletion URL.
	 */
	public function get_delete_url(): string {
		/**
		 * Filters the assignment deletion URL.
		 *
		 * @since 4.24.0
		 *
		 * @param string     $delete_url The deletion URL.
		 * @param Assignment $assignment Assignment model.
		 *
		 * @return string The assignment deletion URL.
		 */
		return apply_filters(
			'learndash_model_assignment_delete_url',
			add_query_arg( 'learndash_delete_attachment', $this->get_id() ),
			$this
		);
	}

	/**
	 * Returns the uploaded file name.
	 *
	 * @since 4.24.0
	 *
	 * @return string The uploaded file name.
	 */
	public function get_uploaded_file_name(): string {
		$uploaded_file_name = Cast::to_string( $this->getAttribute( 'uploaded_file_name' ) ); // It's available since v4.24.0 only.

		// If the uploaded file name is not stored, attempt to guess it from the post title.
		if ( empty( $uploaded_file_name ) ) {
			$uploaded_file_name = preg_replace(
				'/^' . preg_quote( $this->get_post_type_label(), '/' ) . '\s*/',
				'',
				$this->get_title()
			);

			$uploaded_file_name .= '.' . pathinfo(
				Cast::to_string( $this->getAttribute( 'file_name' ) ),
				PATHINFO_EXTENSION
			);
		}

		/**
		 * Filters the uploaded file name.
		 *
		 * @since 4.24.0
		 *
		 * @param string     $uploaded_file_name The uploaded file name.
		 * @param Assignment $assignment         Assignment model.
		 *
		 * @return string The uploaded file name.
		 */
		return apply_filters(
			'learndash_model_assignment_uploaded_file_name',
			$uploaded_file_name,
			$this
		);
	}

	/**
	 * Returns the lesson/topic that the assignment was created for.
	 *
	 * @since 4.24.0
	 *
	 * @return Models\Lesson|Models\Topic|null The lesson/topic that the assignment was created for or null if not found for some reason (deleted, etc.).
	 */
	public function get_related_step(): ?Models\Step {
		$step = Step_Mapper::create(
			Cast::to_int( $this->getAttribute( 'lesson_id' ) )
		);

		if (
			! $step instanceof Models\Lesson
			&& ! $step instanceof Models\Topic
		) {
			return null; // Only lessons and topics are supported.
		}

		/**
		 * Filters the related step.
		 *
		 * @since 4.24.0
		 *
		 * @param Models\Lesson|Models\Topic|null $step       The related step.
		 * @param Assignment                      $assignment Assignment model.
		 *
		 * @return Models\Lesson|Models\Topic|null The related step.
		 */
		return apply_filters(
			'learndash_model_assignment_related_step',
			$step,
			$this
		);
	}

	/**
	 * Returns the course associated with the assignment.
	 *
	 * @since 5.0.0
	 *
	 * @return ?Models\Course The course associated with the assignment or null if not found.
	 */
	public function get_course(): ?Models\Course {
		$course = Course::find(
			Cast::to_int( $this->getAttribute( 'course_id' ) )
		);

		/**
		 * Filters the course associated with the assignment.
		 *
		 * @since 5.0.0
		 *
		 * @param Models\Course|null $course     The course associated with the assignment or null if not found.
		 * @param Assignment         $assignment Assignment model.
		 *
		 * @return Models\Course|null The course associated with the assignment or null if not found.
		 */
		return apply_filters(
			'learndash_model_assignment_course',
			$course,
			$this
		);
	}

	/**
	 * Returns the ID of the course associated with the assignment. 0 if the assignment is not associated with a course or if the course is not found.
	 *
	 * @since 5.0.0
	 *
	 * @return int Course ID or 0 if the assignment is not associated with a course or if the course is not found.
	 */
	public function get_course_id(): int {
		$course = $this->get_course();

		return $course ? $course->get_id() : 0;
	}
}
