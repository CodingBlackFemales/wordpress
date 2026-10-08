<?php
/**
 * User last login trigger.
 *
 * @package LearnDash\Notifications
 */

namespace LearnDash_Notification\Trigger;

use LearnDash_Notification\Notification;
use LearnDash_Notification\Trigger;

/**
 * We going to monitor when an user logged in, then check if they have any course enrollment,
 * we schedule a hook to notify them via the queue table.
 *
 * Class User_Login_Track
 */
class User_Login_Track extends Trigger {
	/**
	 * The trigger slug.
	 *
	 * @var string
	 */
	protected $trigger = 'not_logged_in';

	/**
	 * Meta key that store the last login value.
	 *
	 * @var string
	 */
	public $meta_key = '_ld_notifications_last_login';

	/**
	 * Store the last time the user logged in.
	 *
	 * @param string   $login User name.
	 * @param \WP_User $user the active user.
	 */
	public function track_logged_time( string $login, \WP_User $user ) {
		update_user_meta( $user->ID, $this->meta_key, time() );
	}

	/**
	 * Return an array which contains the users => the courses they enrolled.
	 *
	 * @param Notification $model The model.
	 *
	 * @return array
	 */
	public function get_users_and_courses( Notification $model ): array {
		if (
			is_array( $model->course_id )
			&& in_array( 'all', $model->course_id, true )
		) {
			$course_ids = $this->get_all_course();
		} else {
			$course_ids = $model->course_id;
		}

		$data = [];
		foreach ( $course_ids as $course_id ) {
			if ( ! is_int( $course_id ) ) {
				continue;
			}

			$users = $this->get_users_ids_from_course( $course_id );

			if ( ! count( $users ) ) {
				continue;
			}

			foreach ( $users as $user_id ) {
				if ( ! isset( $data[ $user_id ] ) ) {
					$data[ $user_id ] = [];
				}
				$data[ $user_id ][] = $course_id;
			}
		}

		return $data;
	}

	/**
	 * Return an array of users ids from the course provided.
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return array
	 */
	private function get_users_ids_from_course( int $course_id ): array {
		$query = learndash_get_users_for_course( $course_id, [], false );
		if ( ! $query instanceof \WP_User_Query ) {
			// something was wrong.
			return [];
		}
		$users_ids = $query->get_results();
		foreach ( $users_ids as $key => $user_id ) {
			if ( learndash_course_completed( $user_id, $course_id )
				|| ld_course_access_expired( $course_id, $user_id ) ) {
				unset( $users_ids[ $key ] );
			}
		}

		return $users_ids;
	}

	/**
	 * Loop through all the course and find all users enrolled,
	 * then check for how long they did not logged in
	 *
	 * @return int The number of notifications sent out.
	 */
	public function maybe_send_reminder(): int {
		$models = $this->get_notifications( $this->trigger );
		if ( empty( $models ) ) {
			return 0;
		}
		$affected = 0;
		/**
		 * We going to merge the course so only 1 email per user per notification, not per course.
		 */
		foreach ( $models as $model ) {
			$data = $this->get_users_and_courses( $model );
			foreach ( $data as $user_id => $courses_ids ) {
				$last_login          = get_user_meta( $user_id, $this->meta_key, true );
				$last_login_notified = get_user_meta(
					$user_id,
					'_ld_notifications_last_login_notified_' . $model->post->ID,
					true
				);

				if (
					$last_login_notified > 0
					&& 1 === $model->only_one_time
				) {
					// the user already sent, and we don't want to send more.
					continue;
				}

				if ( empty( $last_login ) ) {
					// we try to look into the LD login.
					$last_login = get_user_meta( $user_id, 'learndash-last-login', true );
					if ( empty( $last_login ) ) {
						continue;
					}
				}

				// if the notification never sent, then just use the login time.
				if ( empty( $last_login_notified ) ) {
					$last_login_notified = $last_login;
				}

				/**
				 * If the last sent time is larger than login time, that mean the
				 * notification already sent, we use the last sent time for the loop check
				 * else we use the last_login
				 */
				$unit = apply_filters( 'learndash_notifications_user_login_track_unit', 'days' );
				if ( $last_login_notified > $last_login ) {
					$last_login = $last_login_notified;
				}
				// sometime, the user have long time session so the login won't happen even if it passed,
				// as they still logged in. We going to check the last activity.
				$last_activity = $this->get_last_activity( $user_id );
				if (
					is_object( $last_activity )
					&& $last_activity->activity_updated >= $last_login
				) {
					// this mean that this user enable "Remember Me".
					$last_login = $last_activity->activity_updated;
				}

				$should_send_at = strtotime( "+ {$model->login_reminder_after} $unit", $last_login );

				if ( $should_send_at <= $this->get_timestamp() ) {
					// the time has come.
					$this->log(
						sprintf(
						// translators: User ID and the last login timestamp.
							__( 'User ID :%1$d - Last login: %2$s', 'learndash' ),
							$user_id,
							$this->get_current_time_from( $last_login )
						)
					);

					// the user and the admins can see all the courses, however the group leader
					// should only see the course they not participate in.
					// send email.
					$emails = [];
					foreach ( $courses_ids as $course_id ) {
						if ( ! $this->is_valid(
							$model,
							[
								'user_id'   => $user_id,
								'course_id' => $course_id,
							]
						) ) {
							continue;
						}

						$clone  = clone $model;
						$gl_key = array_search( 'group_leader', $clone->recipients, true );
						if ( $gl_key !== false ) {
							unset( $clone->recipients[ $gl_key ] );
						}
						$emails = array_merge( $emails, $clone->gather_emails( $user_id, $course_id ) );
					}

					$emails = array_unique( $emails );
					$args   = [
						'user_id'    => $user_id,
						'course_ids' => $courses_ids,
					];
					$this->send( $emails, $model, $args );

					$gl_mapping = [];
					if ( false !== array_search( 'group_leader', $model->recipients, true ) ) {
						foreach ( $courses_ids as $course_id ) {
							$clone                      = clone $model;
							$clone->addition_recipients = '';

							$user_key = array_search( 'user', $clone->recipients, true );
							if ( $user_key !== false ) {
								unset( $clone->recipients[ $user_key ] );
							}

							$admin_key = array_search( 'admin', $clone->recipients, true );
							if ( $admin_key !== false ) {
								unset( $clone->recipients[ $admin_key ] );
							}

							$emails = $clone->gather_emails( $user_id, $course_id );

							foreach ( $emails as $email ) {
								if ( ! isset( $gl_mapping[ $email ] ) ) {
									$gl_mapping[ $email ] = [];
								}
								$gl_mapping[ $email ][] = $course_id;
								$gl_mapping[ $email ]   = array_unique( $gl_mapping[ $email ] );
							}
						}
					}
					// now do the sending to group leader.
					foreach ( $gl_mapping as $email => $course_ids ) {
						$args = [
							'user_id'    => $user_id,
							'course_ids' => $course_ids,
						];
						$this->send( [ $email ], $model, $args );
					}

					update_user_meta(
						$user_id,
						'_ld_notifications_last_login_notified_' . $model->post->ID,
						$this->get_timestamp()
					);
					++$affected;
				}
			}
		}

		return $affected;
	}

	/**
	 * Get the last activity of an user, in greedy way.
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return array|null
	 */
	public function get_last_activity( int $user_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . esc_sql( \LDLMS_DB::get_table_name( 'user_activity' ) ) . ' WHERE user_id=%d ORDER BY activity_updated DESC LIMIT 1',
				$user_id
			)
		);
	}

	/**
	 * A base point for monitoring the events
	 *
	 * @return void
	 */
	public function listen() {
		add_action( 'wp_login', [ $this, 'track_logged_time' ], 10, 2 );
		add_action( 'learndash_notifications_cron', [ $this, 'maybe_send_reminder' ] );
	}

	/**
	 * This one have no delay
	 *
	 * @param Notification $model The notification.
	 * @param array        $args $args.
	 *
	 * @return bool
	 */
	protected function can_send_delayed_email( Notification $model, $args ) {
		return false;
	}
}
