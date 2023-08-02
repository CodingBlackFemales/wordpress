<?php
/**
 * This class provides the easy way to operate a lesson.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

namespace LearnDash\Core\Models;

use LDLMS_Post_Types;
use LearnDash\Core\Models\Traits\Has_Course;
use LearnDash\Core\Models\Traits\Has_Quizzes;
use LearnDash\Core\Models\Traits\Supports_Timer;
use WP_User;

/**
 * Lesson model class.
 *
 * @since 4.6.0
 */
class Lesson extends Post implements Interfaces\Course_Step {
	use Has_Course {
		get_course as get_course_from_trait;
	}
	use Has_Quizzes {
		get_quizzes as get_quizzes_from_trait;
		get_quizzes_number as get_quizzes_number_from_trait;
	}
	use Supports_Timer {
		get_time_limit_in_seconds as get_time_limit_in_seconds_from_trait;
		get_time_limit_formatted as get_time_limit_formatted_from_trait;
	}
	use Traits\Has_Materials;
	use Traits\Supports_Video_Progression;

	/**
	 * Returns allowed post types.
	 *
	 * @since 4.6.0
	 *
	 * @return string[]
	 */
	public static function get_allowed_post_types(): array {
		return array(
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON ),
		);
	}

	/**
	 * Returns a course of the lesson or null if the lesson is not associated with a course.
	 *
	 * @since 4.6.0
	 *
	 * @return Course|null
	 */
	public function get_course(): ?Course {
		/**
		 * Filters a lesson course.
		 *
		 * @since 4.6.0
		 *
		 * @param Course|null $course Course model.
		 * @param Lesson      $lesson Lesson model.
		 *
		 * @return Course|null Lesson course model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_lesson_course',
			$this->get_course_from_trait(),
			$this
		);
	}

	/**
	 * Returns related topics models.
	 *
	 * @since 4.6.0
	 *
	 * @param int $limit  Optional. Limit. Default is 0 which will be changed with LD settings.
	 * @param int $offset Optional. Offset. Default 0.
	 *
	 * @return Topic[]
	 */
	public function get_topics( int $limit = 0, int $offset = 0 ): array {
		/**
		 * Filters lesson topics.
		 *
		 * @since 4.6.0
		 *
		 * @param Topic[] $topics Topics.
		 * @param Lesson  $lesson Lesson model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_lesson_topics',
			$this->memoize(
				function() use ( $limit, $offset ): array {
					$course = $this->get_course();

					if ( ! $course ) {
						return [];
					}

					// TODO: This must be refactored to the direct call to the instance with disabling steps objects loading.
					$topic_ids = learndash_course_get_children_of_step(
						$course->get_id(),
						$this->get_id(),
						LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC )
					);

					// TODO: Course model has a similar logic, maybe refactor.

					// TODO: Here we need to remove those lessons that a user can't read. We need to refactor it, it can be an additional call to filter by statuses, but a loop is too inefficient.
					// It was done in the legacy code with the following method:
					// protected function can_user_read_step( $step_post_id = 0 ) {
					//
					// if ( ! empty( $lesson_ids ) ) {
					// foreach ( $lesson_ids as $lesson_id => $lesson_post ) {
					// if ( ! $this->can_user_read_step( $lesson_post->ID ) ) {
					// unset( $lessons[ $lesson_id ] );
					// }
					// }
					// }.

					$topic_ids = array_slice( $topic_ids, $offset, $limit > 0 ? $limit : null );

					return Topic::find_many( $topic_ids );
				}
			),
			$this
		);
	}

	/**
	 * Returns the total number of related topics.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function get_topics_number(): int {
		/**
		 * Filters lesson topics number.
		 *
		 * @since 4.6.0
		 *
		 * @param int    $number  Number of lessons.
		 * @param Lesson $lesson  Lesson model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_lesson_topics_number',
			$this->memoize(
				function() : int {
					$course = $this->get_course();

					if ( ! $course ) {
						return 0;
					}

					return count(
						// TODO: Inefficient, we need to refactor it to the direct call to the instance with disabling steps objects loading.
						learndash_course_get_children_of_step(
							$course->get_id(),
							$this->get_id(),
							LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC )
						)
					);
				}
			),
			$this
		);
	}

	/**
	 * Returns related quizzes models.
	 *
	 * TODO: Move it to the respective trait.
	 *
	 * @since 4.6.0
	 *
	 * @param int $limit  Optional. Limit. Default is 0 which will be changed with LD settings.
	 * @param int $offset Optional. Offset. Default 0.
	 *
	 * @return Quiz[]
	 */
	public function get_quizzes( int $limit = 0, int $offset = 0 ): array {
		/**
		 * Filters lesson quizzes.
		 *
		 * @since 4.6.0
		 *
		 * @param Quiz[] $quizzes Quizzes.
		 * @param Lesson $lesson  Lesson model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_lesson_quizzes',
			$this->get_quizzes_from_trait( $limit, $offset ),
			$this
		);
	}

	/**
	 * Returns the total number of related quizzes.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function get_quizzes_number(): int {
		/**
		 * Filters lesson quizzes number.
		 *
		 * @since 4.6.0
		 *
		 * @param int    $number  Number of quizzes.
		 * @param Lesson $lesson  Lesson model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_lesson_quizzes_number',
			$this->get_quizzes_number_from_trait(),
			$this
		);
	}

	/**
	 * Gets the lesson time limit in seconds.
	 *
	 * @since 4.6.0
	 *
	 * @return int The number of seconds.
	 */
	public function get_time_limit_in_seconds(): int {
		/**
		 * Filters the lesson time limit in seconds.
		 *
		 * @since 4.6.0
		 *
		 * @param int    $time_limit_in_seconds The lesson time limit in seconds.
		 * @param Lesson $lesson                The lesson model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_lesson_time_limit',
			$this->get_time_limit_in_seconds_from_trait(),
			$this
		);
	}

	/**
	 * Gets the lesson time limit as a H:M:S string.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_time_limit_formatted(): string {
		/**
		 * Filters the lesson time limit.
		 *
		 * @since 4.6.0
		 *
		 * @param string $time_limit            The lesson time limit as a H:M:S string.
		 * @param int    $time_limit_in_seconds The lesson time limit in seconds.
		 * @param Lesson $lesson                The lesson model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_lesson_time_limit_formatted',
			$this->get_time_limit_formatted_from_trait(),
			$this->get_time_limit_in_seconds(),
			$this
		);
	}

	/**
	 * Returns whether lesson content should be visible.
	 *
	 * TODO: Add a hook, tests. Maybe it should be in the Product.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_User $user User.
	 *
	 * @return bool
	 */
	public function is_content_visible( WP_User $user ): bool {
		// TODO: It was moved to the product.
		return true;
	}

	/**
	 * Returns whether lesson can be completed.
	 *
	 * @since 4.6.0
	 *
	 * @return bool
	 */
	public function can_be_completed(): bool {
		// TODO: Implement this.
		return false;
	}

	/**
	 * Returns whether lesson is locked (for example when the previous lesson is not completed).
	 *
	 * @since 4.6.0
	 *
	 * @return bool
	 */
	public function is_locked(): bool {
		// TODO: Implement this.
		return true;
	}

	/**
	 * Returns the progress percentage for a user.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_User $user User.
	 *
	 * @return int
	 */
	public function get_progress_percentage( WP_User $user ): int {
		// TODO: Memoize, tests, hook.

		// TODO: Implement it.
		return 0;
	}
}
