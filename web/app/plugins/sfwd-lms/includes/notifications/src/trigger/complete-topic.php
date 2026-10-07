<?php
/**
 * Complete topic trigger.
 *
 * @package LearnDash\Notifications
 *
 * cspell:ignore leanrdash
 */

namespace LearnDash_Notification\Trigger;

use LearnDash_Notification\Notification;
use LearnDash_Notification\Trigger;

class Complete_Topic extends Trigger {
	/**
	 * @var string
	 */
	protected $trigger = 'complete_topic';

	public function monitor( $args ) {
		$course = isset( $args['course'] ) ? $args['course'] : null;
		$user   = isset( $args['user'] ) ? $args['user'] : null;
		$lesson = isset( $args['lesson'] ) ? $args['lesson'] : null;
		$topic  = isset( $args['topic'] ) ? $args['topic'] : null;
		if ( ! $course instanceof \WP_Post || ! is_object( $user ) || ! $topic instanceof \WP_Post ) {
			// nothing to do here
			$this->log( 'Invalid access', $this->trigger );

			return;
		}
		$models = $this->get_notifications( $this->trigger );
		if ( empty( $models ) ) {
			return;
		}

		$args = [
			'user_id'   => $user->ID,
			'course_id' => $course->ID,
			'lesson_id' => $lesson->ID,
			'topic_id'  => $topic->ID,
		];

		$this->log( '==========Job start========' );
		$this->log( sprintf( 'Process %d notifications', count( $models ) ) );
		foreach ( $models as $model ) {
			if ( ! $this->is_valid( $model, $args ) ) {
				continue;
			}

			$emails = $model->gather_emails( $user->ID, $course->ID );

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
		add_action( 'learndash_topic_completed', [ &$this, 'monitor' ], 10 );
		add_action( 'leanrdash_notifications_send_delayed_email', [ &$this, 'send_db_delayed_email' ] );
	}

	/**
	 * @param Notification $model
	 * @param $args
	 *
	 * @return bool
	 */
	protected function can_send_delayed_email( Notification $model, $args ) {
		$user_id   = $args['user_id'];
		$course_id = $args['course_id'];
		$lesson_id = $args['lesson_id'];
		$topic_id  = $args['topic_id'];

		if ( ! $this->is_valid(
			$model,
			[
				'user_id'   => $user_id,
				'course_id' => $course_id,
				'lesson_id' => $lesson_id,
				'topic_id'  => $topic_id,
			]
		) ) {
			return false;
		}

		return true;
	}
}
