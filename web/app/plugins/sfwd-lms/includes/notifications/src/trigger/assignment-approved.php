<?php
/**
 * Assignment approved trigger.
 *
 * @package LearnDash\Notifications
 *
 * cspell:ignore leanrdash
 */

namespace LearnDash_Notification\Trigger;

use LearnDash\Core\Models\Assignment;
use LearnDash\Core\Models\Lesson;
use LearnDash\Core\Models\Topic;
use LearnDash\Core\Utilities\Cast;
use LearnDash_Notification\Notification;
use LearnDash_Notification\Trigger;

/**
 * Trigger for assignment approval emails.
 */
class Assignment_Approved extends Trigger {
	protected $trigger = 'approve_assignment';

	/**
	 * Handle assignment approved event and dispatch notifications.
	 *
	 * @since 5.2.0
	 *
	 * @param int $assignment_id Assignment post ID.
	 *
	 * @return void
	 */
	public function monitor( $assignment_id ) {
		$assignment_id = Cast::to_int( $assignment_id );
		$context       = $this->resolve_assignment_context_for_monitor( $assignment_id );
		$user_id       = $context['user_id'];
		$course_id     = $context['course_id'];
		$lesson_id     = $context['lesson_id'];
		$topic_id      = $context['topic_id'];
		$models        = $this->get_notifications( $this->trigger );
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
				'assignment_id' => $assignment_id,
			];
			if ( absint( $model->delay ) ) {
				$this->queue_use_db( $emails, $model, $args );
			} else {
				$this->send( $emails, $model, $args );
				$model->mark_sent( $user_id, $this->trigger, $model->post->ID, $course_id );
				$this->log( 'Done, moving next if any' );
			}
		}
	}

	/**
	 * Resolves user, course, lesson, and topic IDs for the assignment-approved monitor.
	 *
	 * Returns zeroed IDs when the assignment cannot be loaded so {@see monitor()} can short-circuit naturally.
	 *
	 * @since 5.2.0
	 *
	 * @param int $assignment_id Assignment post ID.
	 *
	 * @return array{user_id: int, course_id: int, lesson_id: int, topic_id: int}
	 */
	private function resolve_assignment_context_for_monitor( int $assignment_id ): array {
		$assignment = Assignment::find( $assignment_id );
		if ( ! $assignment instanceof Assignment ) {
			return [
				'user_id'   => 0,
				'course_id' => 0,
				'lesson_id' => 0,
				'topic_id'  => 0,
			];
		}

		$user_id   = Cast::to_int( $assignment->get_post_author_id() );
		$course_id = Cast::to_int( $assignment->get_course_id() );
		$lesson_id = 0;
		$topic_id  = 0;

		$related = $assignment->get_related_step();
		if ( $related instanceof Topic ) {
			$topic_id  = $related->get_id();
			$lesson    = $related->get_lesson();
			$lesson_id = $lesson instanceof Lesson
				? $lesson->get_id()
				: Cast::to_int( learndash_get_lesson_id( $topic_id ) );
		} elseif ( $related instanceof Lesson ) {
			$lesson_id = $related->get_id();
		}

		return [
			'user_id'   => $user_id,
			'course_id' => $course_id,
			'lesson_id' => $lesson_id,
			'topic_id'  => $topic_id,
		];
	}

	/**
	 * A base point for monitoring the events
	 *
	 * @return void
	 */
	function listen() {
		add_action( 'learndash_assignment_approved', [ &$this, 'monitor' ] );
		add_action( 'leanrdash_notifications_send_delayed_email', [ &$this, 'send_db_delayed_email' ] );
	}

	/**
	 * Whether a delayed notification may still be sent (re-validates trigger conditions).
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
