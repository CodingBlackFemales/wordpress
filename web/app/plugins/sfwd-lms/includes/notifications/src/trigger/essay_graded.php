<?php
/**
 * Essay graded trigger.
 *
 * @package LearnDash\Notifications
 *
 * cspell:ignore leanrdash
 */

namespace LearnDash_Notification\Trigger;

use LearnDash_Notification\Notification;
use LearnDash_Notification\Trigger;

class Essay_Graded extends Trigger {
	protected $trigger = 'essay_graded';

	public function monitor( $quiz_id, $question_id, $updated_scoring, $essay ) {
		if ( $essay->post_status !== 'graded' ) {
			return;
		}
		$user_id   = $essay->post_author;
		$course_id = learndash_get_course_id( $essay->ID );
		$models    = $this->get_notifications( $this->trigger );
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
				'quiz_id'     => $quiz_id,
				'question_id' => $question_id,
			];
			$model->populate_shortcode_data( $args );
			if ( absint( $model->delay ) ) {
				$this->queue_use_db( $emails, $model, $args );
			} else {
				$this->send( $emails, $model, $args );
				$this->log( 'Done, moving next if any' );
			}
			$this->log( '==========Job end========' );
		}
	}

	/**
	 * A base point for monitoring the events
	 *
	 * @return void
	 */
	function listen() {
		add_action( 'learndash_essay_quiz_data_updated', [ &$this, 'monitor' ], 10, 4 );
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
