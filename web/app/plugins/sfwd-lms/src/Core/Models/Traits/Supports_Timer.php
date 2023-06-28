<?php
/**
 * Trait for models that support a timer.
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
 * Trait for models that support a timer.
 *
 * @since 4.6.0
 */
trait Supports_Timer {
	/**
	 * Gets the time limit in seconds.
	 *
	 * @since 4.6.0
	 *
	 * @return int The number of seconds.
	 */
	public function get_time_limit_in_seconds(): int {
		return $this->memoize(
			function(): int {
				return (int) learndash_forced_lesson_time(
					$this->get_post()
				);
			}
		);
	}

	/**
	 * Gets the time limit as a H:M:S string.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_time_limit_formatted(): string {
		return $this->memoize(
			function(): string {
				$limit_in_seconds = $this->get_time_limit_in_seconds();

				$hours   = floor( $limit_in_seconds / 3600 );
				$minutes = floor( ( $limit_in_seconds % 3600 ) / 60 );
				$seconds = $limit_in_seconds % 60;

				return sprintf( '%02d:%02d:%02d', $hours, $minutes, $seconds ); // TODO: Clarify the format.
			}
		);
	}
}
