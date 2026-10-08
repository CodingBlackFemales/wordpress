<?php
/**
 * Before course expires trigger.
 *
 * @package LearnDash\Notifications
 */

namespace LearnDash_Notification\Trigger;

use LearnDash_Notification\Notification;
use LearnDash_Notification\Trigger;

/**
 * Class Before_Course_Expire
 */
class Before_Course_Expire extends Trigger {
	protected $trigger = 'course_expires';

	/**
	 * Sends the reminder emails for every course whose access is about to expire.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function maybe_send_reminder() {
		foreach ( $this->get_notifications( $this->trigger ) as $model ) {
			$course_ids = [];
			if ( ! in_array( 'all', $model->course_id ) ) {
				// okay we have the course_id
				$course_ids = $model->course_id;
			} else {
				// all ids
				$course_ids = $this->get_all_course();
			}

			foreach ( $course_ids as $course_id ) {
				$expire_access_days = absint( learndash_get_setting( $course_id, 'expire_access_days' ) );

				if ( $model->before_course_expiry > $expire_access_days ) {
					continue;
				}

				if ( learndash_get_setting( $course_id, 'expire_access' ) !== 'on' || $expire_access_days <= 0 ) {
					continue;
				}

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
					if ( learndash_course_completed( $user_id, $course_id ) || ld_course_access_expired( $course_id, $user_id ) ) {
						// the user has done this
						continue;
					}
					$timestamp = ld_course_access_expires_on( $course_id, $user_id );
					if ( $timestamp === 0 ) {
						continue;
					}
					// translators: %s: Date and time the course access expires on.
					$this->log( sprintf( esc_html__( 'The course will be expired at %s', 'learndash' ), $this->get_current_time_from( $timestamp ) ) );
					$init_time = get_option( 'ld_notifications_init' );
					if ( $init_time && $init_time > $timestamp ) {
						// prevent duplicate email
						continue;
					}
					if ( strtotime( '+ ' . $model->before_course_expiry . ' days', $this->get_timestamp() ) >= $timestamp ) {
						// send emails
						$emails = $model->gather_emails( $user_id, $course_id );
						$args   = [
							'user_id'   => $user_id,
							'course_id' => $course_id,
						];
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
		$models = $this->get_notifications( $this->trigger );
		if ( empty( $models ) ) {
			return [];
		}

		// Parse the variable to the right type.
		$user_id   = absint( $user_id );
		$course_id = absint( $course_id );
		$remove    = filter_var( $remove, FILTER_VALIDATE_BOOLEAN );
		$result    = [];
		$this->log( sprintf( 'Process %d notifications', count( $models ) ) );
		foreach ( $models as $model ) {
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
	 * Get users from a course.
	 *
	 * @param int $id Course ID.
	 *
	 * @return array
	 */
	protected function get_users_from_a_course( $id ) {
		$query = learndash_get_users_for_course( $id, [], false );
		if ( ! $query instanceof \WP_User_Query ) {
			// something was wrong
			return [];
		}

		return $query->get_results();
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
		// should never here
		return false;
	}
}
