<?php
/**
 * Essay submitted trigger.
 *
 * @package LearnDash\Notifications
 *
 * cspell:ignore leanrdash
 */

namespace LearnDash_Notification\Trigger;

use LearnDash_Notification\Notification;
use LearnDash_Notification\Trigger;

class Essay_Submitted extends Trigger {
	protected $trigger = 'submit_essay';

	public function monitor( $essay_id, $essay_args ) {
		$course_id   = get_post_meta( $essay_id, 'course_id', true );
		$lesson_id   = get_post_meta( $essay_id, 'lesson_id', true );
		$quiz_id     = get_post_meta( $essay_id, 'quiz_id', true );
		$question_id = get_post_meta( $essay_id, 'question_id', true );

		$user_id = $essay_args['post_author'];
		$models  = $this->get_notifications( $this->trigger );
		if ( empty( $models ) ) {
			return;
		}
		$this->log( '==========Job start========' );
		$this->log( sprintf( 'Process %d notifications', count( $models ) ) );
		foreach ( $models as $model ) {
			$emails = $model->gather_emails( $user_id, $course_id );
			$args   = [
				'user_id'     => $user_id,
				'course_id'   => $course_id,
				'lesson_id'   => $lesson_id,
				'quiz_id'     => $quiz_id,
				'question_id' => $question_id,
			];
			if ( absint( $model->delay ) ) {
				$this->queue_use_db( $emails, $model, $args );
			} else {
				$this->send( $emails, $model, $args );
				$model->mark_sent( $user_id, $this->trigger, $model->post->ID, $essay_id );
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
		add_action( 'learndash_new_essay_submitted', [ &$this, 'monitor' ], 10, 2 );
		add_action( 'leanrdash_notifications_send_delayed_email', [ &$this, 'send_db_delayed_email' ] );
	}

	/**
	 * @param Notification $model
	 * @param $args
	 *
	 * @return bool
	 */
	protected function can_send_delayed_email( Notification $model, $args ) {
		return true;
	}
}
