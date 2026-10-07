<?php
/**
 * Assignment uploaded trigger.
 *
 * @package LearnDash\Notifications
 *
 * cspell:ignore leanrdash
 */

namespace LearnDash_Notification\Trigger;

use LearnDash_Notification\Notification;
use LearnDash_Notification\Trigger;

/**
 * Trigger for assignment upload emails.
 */
class Assignment_Uploaded extends Trigger {
	protected $trigger = 'upload_assignment';

	public function monitor( $assignment_post_id, $assignment_meta ) {
		$user_id   = $assignment_meta['user_id'];
		$course_id = $assignment_meta['course_id'];
		$lesson_id = $assignment_meta['lesson_id'];
		$topic_id  = isset( $assignment_meta['topic_id'] ) ? $assignment_meta['topic_id'] : 0;

		if ( 'sfwd-topic' == $assignment_meta['lesson_type'] ) {
			$topic_id  = $lesson_id;
			$lesson_id = learndash_get_lesson_id( $topic_id );
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
					'user_id'   => $user_id,
					'course_id' => $course_id,
					'lesson_id' => $lesson_id,
					'topic_id'  => $topic_id,
				]
			) ) {
				continue;
			}

			$emails = $model->gather_emails( $user_id, $course_id );
			$args   = [
				'user_id'       => $user_id,
				'course_id'     => $course_id,
				'topic_id'      => $topic_id,
				'lesson_id'     => $lesson_id,
				'assignment_id' => $assignment_post_id,
			];
			if ( absint( $model->delay ) ) {
				$this->queue_use_db( $emails, $model, $args );
			} else {
				$this->send( $emails, $model, $args );
				$model->mark_sent( $user_id, $this->trigger, $model->post->ID, $course_id );
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
		add_action( 'learndash_assignment_uploaded', [ &$this, 'monitor' ], 10, 2 );
		add_action( 'leanrdash_notifications_send_delayed_email', [ &$this, 'send_db_delayed_email' ] );
	}

	/**
	 * Determines whether a delayed email can be sent for the given notification and queue payload.
	 *
	 * @since 5.2.0
	 *
	 * @param Notification $model Notification model.
	 * @param array        $args  Delayed queue payload.
	 *
	 * @return bool
	 */
	protected function can_send_delayed_email( Notification $model, $args ) {
		$user_id   = $args['user_id'] ?? null;
		$course_id = $args['course_id'] ?? null;
		$lesson_id = $args['lesson_id'] ?? null;
		$topic_id  = $args['topic_id'] ?? null;

		return $this->is_valid(
			$model,
			[
				'user_id'   => $user_id,
				'course_id' => $course_id,
				'lesson_id' => $lesson_id,
				'topic_id'  => $topic_id,
			]
		);
	}
}
