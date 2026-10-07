<?php
/**
 * Complete lesson trigger.
 *
 * @package LearnDash\Notifications
 *
 * cspell:ignore leanrdash
 */

namespace LearnDash_Notification\Trigger;

use LearnDash_Notification\Notification;
use LearnDash_Notification\Trigger;

/**
 * Handles the complete_lesson trigger.
 *
 * @since 5.2.0
 */
class Complete_Lesson extends Trigger {
	/**
	 * @var string
	 */
	protected $trigger = 'complete_lesson';

	public function monitor( $args ) {
		$course = isset( $args['course'] ) ? $args['course'] : null;
		$user   = isset( $args['user'] ) ? $args['user'] : null;
		$lesson = isset( $args['lesson'] ) ? $args['lesson'] : null;
		if ( ! $course instanceof \WP_Post || ! is_object( $user ) || ! $lesson instanceof \WP_Post ) {
			// nothing to do here
			$this->log( 'Invalid access', $this->trigger );

			return;
		}
		$models = $this->get_notifications( $this->trigger );
		if ( empty( $models ) ) {
			return;
		}
		$this->log( '==========Job start========' );
		$this->log( sprintf( 'Process %d notifications', count( $models ) ) );
		foreach ( $models as $model ) {
			if ( ! $this->is_valid(
				$model,
				[
					'user_id'   => $user->ID,
					'course_id' => $course->ID,
					'lesson_id' => $lesson->ID,
				]
			) ) {
				continue;
			}

			$emails = $model->gather_emails( $user->ID, $course->ID );
			$args   = [
				'user_id'   => $user->ID,
				'course_id' => $course->ID,
				'lesson_id' => $lesson->ID,
			];
			if ( absint( $model->delay ) ) {
				$this->queue_use_db( $emails, $model, $args );
			} else {
				$this->send( $emails, $model, $args );
				$this->log( 'Done, moving next if any' );
			}
		}
		$this->log( '==========Job end========' );
	}

	/**
	 * A base point for monitoring the events
	 *
	 * @return void
	 */
	function listen() {
		add_action( 'learndash_lesson_completed', [ &$this, 'monitor' ], 10 );
		add_action( 'leanrdash_notifications_send_delayed_email', [ &$this, 'send_db_delayed_email' ] );
	}

	/**
	 * Checks if a delayed email can be sent for the given notification and arguments.
	 *
	 * @since 5.2.0
	 *
	 * @param Notification                                        $model The notification model.
	 * @param array{user_id: int, course_id: int, lesson_id: int} $args  The data.
	 *
	 * @return bool
	 */
	protected function can_send_delayed_email( Notification $model, $args ) {
		$user_id   = $args['user_id'];
		$course_id = $args['course_id'];
		$lesson_id = $args['lesson_id'];

		if ( ! $this->is_valid(
			$model,
			[
				'user_id'   => $user_id,
				'course_id' => $course_id,
				'lesson_id' => $lesson_id,
			]
		) ) {
			return false;
		}

		return true;
	}
}
