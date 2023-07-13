<?php
/**
 * Trait for models that may have a course attached.
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

use LearnDash\Core\Models\Course;
use LearnDash_Settings_Section;

/**
 * Trait for models that may have a course attached.
 *
 * @since 4.6.0
 */
trait Has_Course {
	/**
	 * Returns a course step permalink.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_permalink(): string {
		$nested_urls_enabled = 'yes' === LearnDash_Settings_Section::get_section_setting(
			'LearnDash_Settings_Section_Permalinks',
			'nested_urls'
		);

		if ( $nested_urls_enabled ) {
			$course = $this->get_course();

			if ( $course ) {
				return (string) learndash_get_step_permalink( $this->get_id(), $course->get_id() );
			}
		}

		return (string) get_permalink( $this->get_id() );
	}

	/**
	 * Returns the related course or null.
	 *
	 * @since 4.6.0
	 *
	 * @return Course|null
	 */
	protected function get_course(): ?Course {
		return $this->memoize(
			function(): ?Course {
				$course_id = (int) learndash_get_course_id( $this->get_id() );

				if ( $course_id <= 0 ) {
					return null;
				}

				$course = Course::find( $course_id );

				if ( ! $course ) {
					return null;
				}

				if ( $this->memoization_is_enabled() ) {
					$course->enable_memoization();
				}

				return $course;
			}
		);
	}
}
