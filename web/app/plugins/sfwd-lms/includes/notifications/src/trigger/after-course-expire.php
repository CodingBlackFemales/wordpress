<?php
/**
 * After course expire trigger.
 *
 * @package LearnDash\Notifications
 */

namespace LearnDash_Notification\Trigger;

use LearnDash\Core\Utilities\Cast;
use LearnDash_Notification\Notification;
use LearnDash_Notification\Trigger;
use WP_User_Query;

/**
 * Trigger for sending notifications after a course expires.
 *
 * @since 5.2.0
 */
class After_Course_Expire extends Trigger {
	protected $trigger = 'course_expires_after';

	/**
	 * Sends the notification emails for users whose course access has expired.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function maybe_send_reminder() {
		foreach ( $this->get_notifications( $this->trigger ) as $model ) {
			$course_ids = [];
			if ( ! in_array( 'all', $model->course_id, true ) ) {
				// okay we have the course_id
				$course_ids = $model->course_id;
			} else {
				// all ids
				$course_ids = $this->get_all_course();
			}

			foreach ( $course_ids as $course_id ) {
				if (
					learndash_get_setting( $course_id, 'expire_access' ) !== 'on'
					|| absint( learndash_get_setting( $course_id, 'expire_access_days' ) ) <= 0
				) {
					continue;
				}

				// @phpstan-ignore-next-line Known bug to be fixed later
				$user_ids = $this->get_users_from_a_course( $course_id );

				foreach ( $user_ids as $user_id ) {
					if ( ! $this->is_valid(
						$model,
						[
							'user_id'   => $user_id,
							'course_id' => $course_id,
						]
					) ) {
						continue;
					}

					if ( $model->is_sent( $user_id, $this->trigger, $model->post->ID, $course_id ) ) {
						continue;
					}

					$timestamp = ld_course_access_expires_on( $course_id, $user_id );
					if (
						$timestamp === 0
						|| $timestamp > $this->get_timestamp()
					) {
						$this->log( 'course has not expired' );
						continue;
					}
					$init_time = get_option( 'ld_notifications_init' );
					if (
						$init_time
						&& $init_time > $timestamp
					) {
						// prevent duplicate email
						continue;
					}
					$this->log( sprintf( 'The course expired at %s', $this->get_current_time_from( $timestamp ) ) );

					$course_expired = strtotime( '+ ' . $model->after_course_expiry . ' days', $timestamp ) <= $this->get_timestamp();

					if ( $course_expired ) {
						// send emails
						$args   = [
							'user_id'   => $user_id,
							'course_id' => $course_id,
						];
						$emails = $model->gather_emails( $user_id, $course_id );
						$this->send( $emails, $model, $args );
						$model->mark_sent( $user_id, $this->trigger, $model->post->ID, $course_id );
					}
				}
			}
		}
	}

	/**
	 * Check trigger sent status when user course access is updated
	 *
	 * @param int   $user_id
	 * @param int   $course_id
	 * @param array $course_access_list
	 * @param bool  $remove
	 * @return void
	 */
	public function monitor_sent_status( $user_id, $course_id, $course_access_list, $remove ) {
		$this->models = $this->get_notifications( $this->trigger );
		if ( empty( $this->models ) ) {
			return [];
		}

		// Parse the variable to the right type.
		$user_id   = absint( $user_id );
		$course_id = absint( $course_id );
		$remove    = filter_var( $remove, FILTER_VALIDATE_BOOLEAN );
		$result    = [];
		$this->log( sprintf( 'Process %d notifications', count( $this->models ) ) );
		foreach ( $this->models as $model ) {
			$this->log( sprintf( '- Process notification %s', $model->post->post_title ) );
			if ( $model->is_sent( $user_id, $this->trigger, $model->post->ID, $course_id ) ) {
				$model->mark_unsent( $user_id, $this->trigger, $model->post->ID, $course_id );
				$this->log( sprintf( 'Clear sent status for user #%d in course #%d', $user_id, $course_id ) );
				$result[ $model->id ] = 'removed';
			}
		}

		return $result;
	}

	/**
	 * Retrieves the users whose course access has expired, combining both
	 * users currently visible in the course and those already flagged as
	 * expired by LearnDash.
	 *
	 * @since 5.2.0
	 *
	 * @param int $id The course ID.
	 *
	 * @return int[]
	 */
	protected function get_users_from_a_course( $id ) {
		$user_ids = [];

		$query = learndash_get_users_for_course( $id, [], false );
		if ( $query instanceof WP_User_Query ) {
			$user_ids = $query->get_results();
		}

		$expired_user_ids = learndash_get_course_expired_access_from_meta( $id );
		$user_ids         = array_merge( $user_ids, $expired_user_ids );
		$user_ids         = array_unique( array_map( [ Cast::class, 'to_int' ], $user_ids ) );

		return $user_ids;
	}

	/**
	 * A base point for monitoring the events
	 *
	 * @return void
	 */
	function listen() {
		add_action( 'learndash_notifications_cron', [ &$this, 'maybe_send_reminder' ] );
		add_action( 'learndash_update_course_access', [ &$this, 'monitor_sent_status' ], 10, 4 );
	}

	/**
	 * @param Notification $model
	 * @param $args
	 *
	 * @return bool
	 */
	protected function can_send_delayed_email( Notification $model, $args ) {
		return false;
	}
}
