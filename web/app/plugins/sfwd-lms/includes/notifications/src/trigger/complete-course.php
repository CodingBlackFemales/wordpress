<?php
/**
 * Complete course trigger.
 *
 * @package LearnDash\Notifications
 *
 * cspell:ignore leanrdash
 */

namespace LearnDash_Notification\Trigger;

use LearnDash_Notification\Notification;
use LearnDash_Notification\Trigger;

/**
 * Handles the complete_course trigger.
 *
 * @since 5.2.0
 */
class Complete_Course extends Trigger {
	/**
	 * @var string
	 */
	protected $trigger = 'complete_course';

	/**
	 * Processes the complete_course event.
	 *
	 * @since 5.2.0
	 *
	 * @param array{course?: \WP_Post, user?: \WP_User} $args The event arguments.
	 *
	 * @return void
	 */
	public function monitor( $args ) {
		$course = isset( $args['course'] ) ? $args['course'] : null;
		$user   = isset( $args['user'] ) ? $args['user'] : null;
		if ( ! $course instanceof \WP_Post || ! is_object( $user ) ) {
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
				]
			) ) {
				continue;
			}

			$emails = $model->gather_emails( $user->ID, $course->ID );
			$args   = [
				'user_id'   => $user->ID,
				'course_id' => $course->ID,
			];
			if ( absint( $model->delay ) ) {
				$this->queue_use_db( $emails, $model, $args );
			} else {
				$this->send( $emails, $model, $args );
				$model->mark_sent( $user->ID, $this->trigger, $model->post->ID, $course->ID );
				$this->log( 'Done, moving next if any' );
			}
		}
		$this->log( '==========Job end========' );
	}

	/**
	 * A base point for monitoring the events
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	function listen() {
		add_action( 'learndash_course_completed', [ &$this, 'monitor' ], 10 );
		add_action( 'leanrdash_notifications_send_delayed_email', [ &$this, 'send_db_delayed_email' ] );
	}

	/**
	 * Mark notification as sent after a delayed email is dispatched.
	 *
	 * @since 5.2.0
	 *
	 * @param Notification                        $model The notification model.
	 * @param array{user_id: int, course_id: int} $args  Triggering arguments.
	 *
	 * @return void
	 */
	protected function after_email_sent( Notification $model, array $args ) {
		$model->mark_sent( $args['user_id'], $this->trigger, $model->post->ID, $args['course_id'] );
	}

	/**
	 * Checks if a delayed email can be sent for the given notification and arguments.
	 *
	 * @since 5.2.0
	 *
	 * @param Notification                        $model The notification model.
	 * @param array{user_id: int, course_id: int} $args  The data.
	 *
	 * @return bool
	 */
	protected function can_send_delayed_email( Notification $model, $args ) {
		$user_id   = $args['user_id'];
		$course_id = $args['course_id'];

		if ( ! $this->is_valid(
			$model,
			[
				'user_id'   => $user_id,
				'course_id' => $course_id,
			]
		) ) {
			return false;
		}

		return true;
	}
}
