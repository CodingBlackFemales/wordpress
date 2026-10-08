<?php
/**
 * Quiz completed trigger.
 *
 * @package LearnDash\Notifications
 */

namespace LearnDash_Notification\Trigger;

class Quiz_Completed extends Quiz_Passed {
	protected $trigger = 'complete_quiz';

	/**
	 * It should always run
	 *
	 * @param $quiz_data
	 *
	 * @return bool
	 */
	protected function is_process( $quiz_data ) {
		return true;
	}
}
