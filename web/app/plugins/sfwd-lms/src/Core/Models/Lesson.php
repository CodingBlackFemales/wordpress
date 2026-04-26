<?php
/**
 * This class provides the easy way to operate a lesson.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models;

use LDLMS_Post_Types;
use Learndash_Course_Video;

/**
 * Lesson model class.
 *
 * @since 4.6.0
 */
class Lesson extends Step {
	use Traits\Has_Materials;
	use Traits\Has_Quizzes;
	use Traits\Has_Steps;
	use Traits\Has_Assignments;
	use Traits\Has_Topics;
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
	 * Returns true if a lesson has steps, otherwise false.
	 *
	 * @since 4.21.0
	 *
	 * @return bool
	 */
	public function has_steps(): bool {
		/**
		 * Filters whether a lesson has steps.
		 *
		 * @since 4.21.0
		 *
		 * @param bool   $has_steps Whether a lesson has steps.
		 * @param Lesson $lesson    Lesson model.
		 *
		 * @return bool Whether a lesson has steps.
		 */
		return apply_filters(
			'learndash_model_lesson_has_steps',
			$this->get_topics_number() > 0 || $this->get_quizzes_number() > 0,
			$this
		);
	}

	/**
	 * Returns the lesson content.
	 *
	 * @since 4.24.0
	 *
	 * @param bool $raw Whether to return raw content or not. Default is false.
	 *
	 * @return string
	 */
	public function get_content( bool $raw = false ): string {
		$content = parent::get_content( false );

		// Add the lesson video logic.

		if (
			defined( 'LEARNDASH_LESSON_VIDEO' )
			&& true === LEARNDASH_LESSON_VIDEO
		) {
			$content = Learndash_Course_Video::get_instance()->add_video_to_content(
				$content,
				$this->get_post(),
				$this->get_settings()
			);
		}

		if ( ! $raw ) {
			return apply_filters( 'the_content', $content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- It's a WordPress core filter.
		}

		return $content;
	}
}
