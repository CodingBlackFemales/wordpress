<?php
/**
 * This class provides the easy way to operate a quiz.
 *
 * @since   4.6.0
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
use LearnDash\Core\Models\Traits\Has_Lesson;
use LearnDash\Core\Models\Traits\Supports_Timer;
use WP_User;

/**
 * Quiz model class.
 *
 * @since 4.6.0
 */
class Quiz extends Post implements Interfaces\Course_Step {
	use Has_Course {
		get_course as get_course_from_trait;
	}
	use Has_Lesson {
		get_lesson as get_lesson_from_trait;
	}
	use Supports_Timer {
		get_time_limit_in_seconds as get_time_limit_in_seconds_from_trait;
		get_time_limit_formatted as get_time_limit_formatted_from_trait;
	}
	use Traits\Has_Materials;

	/**
	 * Returns allowed post types.
	 *
	 * @since 4.6.0
	 *
	 * @return string[]
	 */
	public static function get_allowed_post_types(): array {
		return [
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ),
		];
	}

	/**
	 * Returns a course of the quiz or null if the quiz is not associated with a course.
	 *
	 * @since 4.6.0
	 *
	 * @return Course|null
	 */
	public function get_course(): ?Course {
		/**
		 * Filters a quiz course.
		 *
		 * @since 4.6.0
		 *
		 * @param Course|null $course Course model.
		 * @param Quiz $quiz          Quiz model.
		 *
		 * @return Course|null Quiz course model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_quiz_course',
			$this->get_course_from_trait(),
			$this
		);
	}

	/**
	 * Returns a lesson of the quiz or null.
	 *
	 * @since 4.6.0
	 *
	 * @return Lesson|null
	 */
	public function get_lesson(): ?Lesson {
		/**
		 * Filters a quiz lesson.
		 *
		 * @since 4.6.0
		 *
		 * @param Lesson|null $lesson Lesson model.
		 * @param Quiz        $quiz   Quiz model.
		 *
		 * @return Lesson|null Quiz lesson model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_quiz_lesson',
			$this->get_lesson_from_trait(),
			$this
		);
	}

	/**
	 * Returns a topic of the quiz or null.
	 *
	 * @since 4.6.0
	 *
	 * @return Topic|null
	 */
	public function get_topic(): ?Topic {
		/**
		 * Filters a quiz topic.
		 *
		 * @since 4.6.0
		 *
		 * @param Topic|null $topic Topic model.
		 * @param Quiz       $quiz  Quiz model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_quiz_topic',
			$this->memoize(
				function (): ?Topic {
					$topic_id = (int) learndash_get_lesson_id( $this->get_id() );

					if ( $topic_id <= 0 ) {
						return null;
					}

					$topic = Topic::find( $topic_id );

					if ( ! $topic ) {
						return null;
					}

					if ( $this->memoization_is_enabled() ) {
						$topic->enable_memoization();
					}

					return $topic;
				}
			),
			$this
		);
	}

	/**
	 * Gets the quiz time limit in seconds.
	 *
	 * @since 4.6.0
	 *
	 * @return int The number of seconds.
	 */
	public function get_time_limit_in_seconds(): int {
		/**
		 * Filters the quiz time limit in seconds.
		 *
		 * @since 4.6.0
		 *
		 * @param int  $time_limit_in_seconds The quiz time limit in seconds.
		 * @param Quiz $quiz                  The quiz model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_quiz_time_limit',
			$this->get_time_limit_in_seconds_from_trait(),
			$this
		);
	}

	/**
	 * Gets the quiz time limit as a H:M:S string.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_time_limit_formatted(): string {
		/**
		 * Filters the quiz time limit.
		 *
		 * @since 4.6.0
		 *
		 * @param string $time_limit            The quiz time limit as a H:M:S string.
		 * @param int    $time_limit_in_seconds The quiz time limit in seconds.
		 * @param Quiz   $quiz                  The quiz model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_quiz_time_limit_formatted',
			$this->get_time_limit_formatted_from_trait(),
			$this->get_time_limit_in_seconds(),
			$this
		);
	}

	/**
	 * Returns whether or not quiz content should be visible.
	 *
	 * @since 4.6.0
	 *
	 * @return bool
	 */
	public function is_content_visible(): bool {
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
		// TODO: Implement this.
		return 0;
	}
}
