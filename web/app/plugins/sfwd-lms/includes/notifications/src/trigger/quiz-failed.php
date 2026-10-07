<?php
/**
 * Quiz failed trigger.
 *
 * @package LearnDash\Notifications
 */

namespace LearnDash_Notification\Trigger;

/**
 * Trigger for when a quiz is failed.
 *
 * Extends the Quiz_Passed trigger to handle failed quiz scenarios.
 *
 * @since 5.2.0
 */
class Quiz_Failed extends Quiz_Passed {
	/**
	 * The trigger key for failed quizzes.
	 *
	 * @since 5.2.0
	 *
	 * @var string
	 */
	protected $trigger = 'fail_quiz';

	/**
	 * Only continue when the quiz is failed.
	 *
	 * @since 5.2.0
	 *
	 * @param array{pass?:numeric,has_graded?:bool,graded?:array<int,array{status?:mixed}>} $quiz_data The quiz data array.
	 *
	 * @return bool True if the process should continue, false otherwise.
	 */
	protected function is_process( $quiz_data ) {
		if ( ! is_array( $quiz_data ) ) {
			return false;
		}
		// If quiz passed, don't process.
		if (
			isset( $quiz_data['pass'] )
			&& absint( $quiz_data['pass'] ) === 1
		) {
			return false;
		}

		// If quiz has graded questions and they're not all graded yet, don't process.
		if (
			isset( $quiz_data['has_graded'] )
			&& true === $quiz_data['has_graded']
			&& ! empty( $quiz_data['graded'] )
		) {
			foreach ( $quiz_data['graded'] as $grade_item ) {
				if (
					isset( $grade_item['status'] )
					&& 'graded' !== $grade_item['status']
				) {
					return false;
				}
			}
		}

		// Quiz failed and all essays are graded (if any).
		return true;
	}
}
