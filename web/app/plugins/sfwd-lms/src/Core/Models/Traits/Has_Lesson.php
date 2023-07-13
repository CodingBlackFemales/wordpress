<?php
/**
 * Trait for models that may have a lesson attached.
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

namespace LearnDash\Core\Models\Traits;

use LearnDash\Core\Models\Lesson;

/**
 * Trait for models that may have a lesson attached.
 *
 * @since 4.6.0
 */
trait Has_Lesson {
	/**
	 * Returns the related lesson or null.
	 *
	 * @since 4.6.0
	 *
	 * @return Lesson|null
	 */
	protected function get_lesson(): ?Lesson {
		return $this->memoize(
			function (): ?Lesson {
				$lesson_id = (int) learndash_get_lesson_id( $this->get_id() );

				if ( $lesson_id <= 0 ) {
					return null;
				}

				$lesson = Lesson::find( $lesson_id );

				if ( ! $lesson ) {
					return null;
				}

				if ( $this->memoization_is_enabled() ) {
					$lesson->enable_memoization();
				}

				return $lesson;
			}
		);
	}
}
