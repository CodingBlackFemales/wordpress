<?php
/**
 * Lesson available trigger.
 *
 * @package LearnDash\Notifications
 *
 * cspell:ignore leanrdash
 */

namespace LearnDash_Notification\Trigger;

use LearnDash\Core\Models\Product;
use LearnDash\Core\Utilities\Cast;
use LearnDash_Notification\Notification;
use LearnDash_Notification\Trigger;

/**
 * Listen when a lesson is available for the enrolled users, then send email for notification
 * The cron will base on per lesson, each lesson have each own cron for dispatching emails.
 * 1. Cron will start when a new drip lesson created
 * 2. When an user enroll to a course that have drip lesson, check if the cron for that lesson exist, if not, create new, if yes
 * do nothing, as both option, the cron for that user surely will start later than existing
 * 3. When a notification get created, make sure all the drip lesson already have a cron monitoring
 * Class Drip_Lesson_Available
 */
class Drip_Lesson_Available extends Trigger {
	/**
	 * The trigger name.
	 *
	 * @var string
	 */
	protected $trigger = 'lesson_available';

	/**
	 * The hook name.
	 *
	 * @var string
	 */
	protected $hook_name = 'learndash_notifications_drip_lesson';

	/**
	 * Listen for the signal when a new lesson is added or updated, then we going to queue an event for further processing
	 *
	 * @param int    $meta_id     ID of metadata entry to update.
	 * @param int    $object_id   Post ID.
	 * @param string $meta_key    Metadata key.
	 * @param mixed  $_meta_value Metadata value. This will be a PHP-serialized string representation of the value
	 *                            if the value is an array, an object, or itself a PHP-serialized string.
	 */
	public function listen_for_lesson_update( $meta_id, $object_id, $meta_key, $_meta_value ) {
		if ( ! function_exists( 'learndash_get_post_type_slug' ) ) {
			// this mean the LD core is not on.
			return;
		}
		$post_type = learndash_get_post_type_slug( 'lesson' );
		// not this, return.
		if ( '_' . $post_type !== $meta_key ) {
			return;
		}
		// make sure the values fully provided.
		$_meta_value = maybe_unserialize( $_meta_value );
		if (
			( ! isset( $_meta_value['sfwd-lessons_visible_after'] ) || empty( $_meta_value['sfwd-lessons_visible_after'] ) ) &&
			( ! isset( $_meta_value['sfwd-lessons_visible_after_specific_date'] ) || empty( $_meta_value['sfwd-lessons_visible_after_specific_date'] ) )
		) {
			return;
		}

		$args      = [
			$object_id,
		];
		$timestamp = $this->get_next_send( $object_id );
		if ( false === $timestamp ) {
			// no user enroll to this course, so do nothing.
			return;
		}
		if ( wp_next_scheduled( $this->hook_name, $args ) ) {
			$this->log( 'Restart the schedule', $this->trigger );
			// if this is queued, means something was changed, so remove and start over.
			wp_clear_scheduled_hook( $this->hook_name, $args );
		}
		wp_schedule_single_event( $timestamp, $this->hook_name, $args );
	}

	/**
	 * Get the nearest time this lesson available for the users
	 *
	 * @param int  $lesson_id lesson ID.
	 * @param bool $get_past  If user set the enroll date to the past, then we set this to true.
	 *
	 * @return int|bool
	 */
	public function get_next_send( int $lesson_id, bool $get_past = false, ?int $user_id = null ) {
		$course_id = learndash_get_course_id( $lesson_id );
		$user_ids  = $this->get_users( $course_id );

		if ( empty( $user_ids ) && ! empty( $user_id ) ) {
			return false;
		}

		$current = $this->get_timestamp();

		if ( ! empty( $user_id ) ) {
			$user_timestamps = [];

			$timestamp = $this->ld_lesson_access_from( $lesson_id, $user_id, $course_id, true );

			$user_timestamps[] = $timestamp;

			$user_timestamps = array_unique( $user_timestamps );
			$user_timestamps = array_filter( $user_timestamps );

			if ( empty( $user_timestamps ) ) {
				return false;
			}
		} else {
			$timestamps = [];

			foreach ( $user_ids as $c_user_id ) {
				$timestamp = $this->ld_lesson_access_from( $lesson_id, $c_user_id, $course_id, true );

				// This should always in future.
				if ( $current > $timestamp && false === $get_past ) {
					continue;
				}

				$timestamps[] = $timestamp;
			}

			$timestamps = array_unique( $timestamps );
			$timestamps = array_filter( $timestamps );

			if ( empty( $timestamps ) ) {
				return false;
			}
		}

		return ! empty( $user_id ) ? min( $user_timestamps ) : min( $timestamps );
	}

	/**
	 *
	 * This function will be triggered when the time come, if a lesson is available for the users,
	 * it will send the emails.
	 *
	 * @param int $lesson_id The Lesson ID.
	 */
	public function maybe_dispatch_emails( $lesson_id ) {
		$models = $this->get_notifications( $this->trigger );

		if ( empty( $models ) ) {
			return;
		}

		if ( false === $this->is_shared_course() ) {
			$course_id = learndash_get_course_id( $lesson_id );
			$this->dispatching_email( (int) $course_id, (int) $lesson_id, $models );
		} else {
			$course_ids = $this->get_course_ids_for_shared_coursed( (int) $lesson_id );

			foreach ( $course_ids as $course_id ) {
				$this->dispatching_email( (int) $course_id, (int) $lesson_id, $models );
			}
		}

		$timestamp = $this->get_next_send( $lesson_id );

		if ( false !== $timestamp ) {
			wp_clear_scheduled_hook( $this->hook_name, [ $lesson_id ] );
			wp_schedule_single_event( $timestamp, $this->hook_name, [ $lesson_id ] );
		} else {
			wp_clear_scheduled_hook( $this->hook_name, [ $lesson_id ] );
			$this->log( 'All sent' );
		}
		$this->log( '====Cron End====' );
	}

	/**
	 * Checks and dispatches an email for this lesson.
	 *
	 * @since 5.2.0
	 *
	 * @param int                 $course_id The course ID we are sending for.
	 * @param int                 $lesson_id The lesson ID we are sending for.
	 * @param array<Notification> $models    List of Notifications.
	 *
	 * @return void
	 */
	private function dispatching_email( int $course_id, int $lesson_id, array $models ): void {
		$user_ids = $this->get_users( $course_id );
		$this->log( '====Cron Start====' );

		foreach ( $user_ids as $user_id ) {
			$user_id = absint( $user_id );
			if ( ! $this->should_send( $user_id, $lesson_id, $course_id ) ) {
				continue;
			}
			$timestamp = $this->ld_lesson_access_from( $lesson_id, $user_id, $course_id );
			$current   = $this->get_timestamp();
			if ( ! empty( $timestamp ) && $current < $timestamp ) {
				$this->log( 'Cron was trigger manually, however, the time was not right' );
				// this is not touch yet.
				continue;
			}

			$args = [
				'user_id'   => $user_id,
				'course_id' => $course_id,
				'lesson_id' => $lesson_id,
			];

			foreach ( $models as $model ) {
				if ( ! $this->is_valid( $model, $args ) ) {
					continue;
				}

				if ( $model->is_sent( $user_id, $this->trigger, $model->post->ID, $course_id, $lesson_id ) ) {
					$this->log( sprintf( 'ERR_SENT_%s_%s_%s', $args['user_id'], $args['course_id'], $args['lesson_id'] ) );
					continue;
				}

				$this->log(
					sprintf(
						'Expected to send a notification "%s" for the lesson %d at %s for the user %d',
						$model->post->post_title,
						$lesson_id,
						$this->get_current_time_from( $timestamp ),
						$user_id
					)
				);

				$emails = $model->gather_emails( $user_id, $course_id );

				if ( absint( $model->delay ) ) {
					// check if this already queued.
					$queued = learndash_notifications_get_all_delayed_emails(
						array_merge( [ 'notification_id' => $model->post->ID ], $args )
					);

					if ( count( $queued ) ) {
						// this already be queued.
						$this->log( sprintf( 'ERR_QUEUED_%s_%s_%s', $args['user_id'], $args['course_id'], $args['lesson_id'] ) );
						continue;
					}

					$this->queue_use_db( $emails, $model, $args );
				} else {
					$this->send( $emails, $model, $args );
					$model->mark_sent( $user_id, $this->trigger, $model->post->ID, $course_id, $lesson_id );
				}
			}
		}
	}

	/**
	 * Query to the database for retrieving the course_ids if shared course mode enabled.
	 *
	 * @since 5.2.0
	 *
	 * @param int $lesson_id The lesson ID that is attached to a course.
	 *
	 * @return array
	 */
	protected function get_course_ids_for_shared_coursed( $lesson_id ) {
		global $wpdb;
		$sql = $wpdb->prepare(
			'SELECT meta_value FROM ' . $wpdb->postmeta . ' WHERE post_id = %d AND meta_key LIKE %s',
			$lesson_id,
			$wpdb->esc_like( 'ld_course_' ) . '%'
		);

		return $wpdb->get_col( $sql );
	}

	/**
	 * A shorthand for check if the shared course is enabled.
	 *
	 * @return bool
	 */
	protected function is_shared_course() {
		if ( 'yes' === \LearnDash_Settings_Section::get_section_setting(
			'LearnDash_Settings_Courses_Builder',
			'shared_steps'
		) ) {
			return true;
		}

		return false;
	}

	/**
	 * Determine if this should be send to the user, this mostly for backward compatibility, we should not
	 * send the email again
	 *
	 * @param int $user_id   The user ID.
	 * @param int $lesson_id The lesson ID.
	 * @param int $course_id The course ID.
	 */
	protected function should_send( $user_id, $lesson_id, $course_id ) {
		if ( learndash_is_lesson_complete( $user_id, $lesson_id ) || ld_course_access_expired(
			$course_id,
			$user_id
		) ) {
			// the user already finish this, do nothing.
			$this->log( sprintf( 'ERR_LC_%s_%s', $user_id, $course_id ) );

			return false;
		}

		$product = Product::find( $course_id );

		if (
			! $product
			|| ! $product->user_has_access( $user_id )
		) {
			$this->log( sprintf( 'ERR_UA_%s_%s', $user_id, $course_id ) );

			return false;
		}

		$timestamp = $this->ld_lesson_access_from( $lesson_id, $user_id, $course_id );
		// if the timestamp is smaller than upgrade time then do nothing, this is for prevent double email send from older version.
		$init_time = get_option( 'ld_notifications_init' );
		if ( $init_time && $timestamp < $init_time ) {
			$this->log( sprintf( 'ERR_Expire_%s_%s_%s', $user_id, $course_id, $timestamp ) );

			return false;
		}

		return true;
	}

	/**
	 * When an user enroll to a course, and if the course having a drip lesson, then restart the cron
	 *
	 * @param int   $user_id     The user ID.
	 * @param int   $course_id   The course ID.
	 * @param array $access_list The access list.
	 * @param bool  $remove      Status.
	 */
	public function maybe_kick_start( $user_id, $course_id, $access_list, $remove ) {
		if ( $remove ) {
			$this->clear_queued_notifications( $user_id, $course_id );

			return;
		}

		$lesson_ids = $this->get_course_lesson_ids( intval( $course_id ) );
		foreach ( $lesson_ids as $lesson_id ) {
			if ( is_object( $lesson_id ) ) {
				$lesson_id = $lesson_id->ID;
			}
			if ( ! $this->is_dripped_lesson( $lesson_id ) ) {
				continue;
			}
			// if the lesson is not able to access now for this user, means this is queued for future.
			$timestamp = $this->ld_lesson_access_from( $lesson_id, $user_id );
			if ( absint( $timestamp ) > 0 && ! wp_next_scheduled( $this->hook_name, [ $lesson_id ] ) ) {
				// this is drip and no cron for it, start now.
				// we don't need to re-queue if something already running, as it should trigger before this.
				$timestamp = $this->get_next_send( $lesson_id );
				wp_schedule_single_event( $timestamp, $this->hook_name, [ $lesson_id ] );
			}
		}
	}

	/**
	 * Make a list of when the email should send.
	 *
	 * @param int $lesson_id Lesson ID.
	 *
	 * @deprecated 5.2.0
	 */
	private function make_detail_log( $lesson_id ) {
		$course_id = learndash_get_course_id( $lesson_id );
		$user_ids  = $this->get_users( $course_id );
		if ( empty( $user_ids ) ) {
			return;
		}
		$strings = [ sprintf( 'Lesson %d:', $lesson_id ) ];
		foreach ( $user_ids as $user_id ) {
			$timestamp = $this->ld_lesson_access_from( $lesson_id, $user_id );
			if ( $timestamp > $this->get_timestamp() ) {
				// this one will be send in the future.
				$strings[ $timestamp ] = sprintf(
					'- User: %d will be notified at %s',
					$user_id,
					$this->get_current_time_from( $timestamp )
				);
			}
		}
		ksort( $strings );
		$strings = implode( PHP_EOL, $strings );
		$this->log( $strings );
	}

	/**
	 * Clear the single schedule if the course have drip lesson and no one enroll into it
	 *
	 * @param int $user_id   The user ID.
	 * @param int $course_id The course ID.
	 */
	private function clear_queued_notifications( $user_id, $course_id ) {
		$lesson_ids = $this->get_course_lesson_ids( intval( $course_id ) );
		$user_ids   = $this->get_users( $course_id );
		$init_time  = get_option( 'ld_notifications_init' );

		foreach ( $lesson_ids as $lesson_id ) {
			if ( is_object( $lesson_id ) ) {
				$lesson_id = $lesson_id->ID;
			}
			if ( ! $this->is_dripped_lesson( $lesson_id ) ) {
				continue;
			}
			$remove = true;
			foreach ( $user_ids as $user_id ) {
				$time_access = $this->ld_lesson_access_from( $lesson_id, $user_id );
				if ( $time_access > $init_time && $time_access > $this->get_timestamp() ) {
					// this mean the queue still going on.
					$remove = false;
					break;
				}
			}
			if ( $remove && wp_next_scheduled( $this->hook_name, [ $lesson_id ] ) ) {
				$this->log( sprintf( 'Clear the queue for lesson %d', $lesson_id ) );
				wp_clear_scheduled_hook( $this->hook_name, [ $lesson_id ] );
			}
		}
	}

	/**
	 * Get all the users who has access to the course.
	 *
	 * @param int $course_id The course ID.
	 *
	 * @return array
	 */
	protected function get_users( $course_id ) {
		$query = learndash_get_users_for_course( $course_id, [], false );
		if ( $query instanceof \WP_User_Query ) {
			return $query->get_results();
		}

		return [];
	}

	/**
	 * A base point for monitoring the events
	 *
	 * So when a drip lesson was created, or update, we going to schedule an event at nearest time
	 *
	 * @return mixed
	 */
	public function listen() {
		add_action( 'updated_postmeta', [ &$this, 'listen_for_lesson_update' ], 99, 4 );
		add_action( 'learndash_update_course_access', [ &$this, 'maybe_kick_start' ], 10, 4 );
		add_action( 'updated_user_meta', [ $this, 'requeue_cron_when_enroll_date_changed' ], 10, 4 );
		add_action( $this->hook_name, [ &$this, 'maybe_dispatch_emails' ] );
		// kick start.
		add_action( 'learndash_notifications_cron', [ $this, 'ensure_cron_queued' ] );
		add_action( 'leanrdash_notifications_send_delayed_email', [ &$this, 'send_db_delayed_email' ] );
		if ( get_option( 'learndash_notifications_drips_check' ) ) {
			$this->ensure_cron_queued();
			delete_option( 'learndash_notifications_drips_check' );
		}
	}

	/**
	 * We update the cron job time when an enrollment date changed.
	 *
	 * @param int    $meta_id     ID of updated metadata entry.
	 * @param int    $object_id   ID of the object metadata is for.
	 * @param string $meta_key    Metadata key.
	 * @param mixed  $_meta_value Metadata value. Serialized if non-scalar.
	 */
	public function requeue_cron_when_enroll_date_changed( $meta_id, $object_id, $meta_key, $_meta_value ) {
		$pattern = '/course_([0-9]+)_access_from/';
		if ( preg_match( $pattern, $meta_key, $matches ) ) {
			$course_id = $matches[1];
			// get the drip lessons.
			$lessons = $this->get_course_lesson_ids( intval( $course_id ) );
			if ( count( $lessons ) && is_array( $lessons ) ) {
				foreach ( $lessons as $lesson_id ) {
					$timestamp = $this->get_next_send( $lesson_id, true, $object_id );

					if ( false === $timestamp ) {
						// no user enroll to this course, so do nothing.
						continue;
					}
					$args = [
						$lesson_id,
					];
					if ( wp_next_scheduled( $this->hook_name, $args ) ) {
						wp_clear_scheduled_hook( $this->hook_name, $args );
					}
					wp_schedule_single_event( $timestamp, $this->hook_name, $args );
				}
			}
		}
	}

	/**
	 * Determine if we can send delayed email.
	 *
	 * @since 5.2.0
	 *
	 * @param Notification         $model The notification model.
	 * @param array<string, mixed> $args  The arguments the email was queued with.
	 *
	 * @return bool
	 */
	protected function can_send_delayed_email( Notification $model, $args ) {
		$user_id   = Cast::to_int( $args['user_id'] );
		$course_id = Cast::to_int( $args['course_id'] );
		$lesson_id = Cast::to_int( $args['lesson_id'] );

		$product = Product::find( $course_id );

		if (
			! $product
			|| ! $product->user_has_access( $user_id )
		) {
			// this user not in this course anymore.
			$this->log( sprintf( 'Won\'t send because user not in the course anymore, course id: %d', $course_id ) );

			return false;
		}

		$lesson = get_post( $lesson_id );
		if ( ! is_object( $lesson ) ) {
			$this->log( 'Won\'t send because lesson doesn\'t exist anymore' );

			return false;
		}

		if ( ! $this->is_valid(
			$model,
			[
				'user_id'   => $user_id,
				'course_id' => $course_id,
				'lesson_id' => $lesson_id,
			]
		) ) {
			// specific course and this is not the one, return.
			$this->log(
				sprintf(
					"Won't send cause the ID is different from the settings. Expected: %s - Current:%d",
					implode( ',', $model->lesson_id ),
					$lesson_id
				)
			);

			return false;
		}

		/**
		 * Because, this email can be created by legacy version, and it maybe sent before it reach into this,
		 * so we have to check
		 */
		if ( $model->is_sent( $user_id, $this->trigger, $model->post->ID, $course_id, $lesson_id ) ) {
			$this->log( 'The email already sent.' );

			return false;
		}

		return true;
	}

	/**
	 * When plugin activated, we need to check if any cron missing
	 */
	public function ensure_cron_queued() {
		$models = $this->get_notifications( $this->trigger );

		if ( empty( $models ) ) {
			// nothing to do.
			return;
		}

		$lesson_ids = $this->get_all_lessons();

		foreach ( $lesson_ids as $lesson_id ) {
			if ( ! $this->is_dripped_lesson( $lesson_id ) ) {
				continue;
			}
			$timestamp = $this->get_next_send( $lesson_id );
			if ( ! $timestamp ) {
				continue;
			}
			if ( ! wp_next_scheduled( $this->hook_name, [ $lesson_id ] ) ) {
				wp_schedule_single_event( $timestamp, $this->hook_name, [ $lesson_id ] );
			}
		}
	}

	/**
	 * Trigger this for flag a notification is sent.
	 *
	 * @param Notification $model The Notification model.
	 * @param array        $args  Misc Data.
	 */
	protected function after_email_sent( Notification $model, array $args ) {
		$user_id   = $args['user_id'];
		$course_id = $args['course_id'];
		$lesson_id = $args['lesson_id'];
		$model->mark_sent( $user_id, $this->trigger, $model->post->ID, $course_id, $lesson_id );
	}

	/**
	 * Shorthand for checking if a lesson is drip.
	 *
	 * @param int $lesson_id The lesson ID.
	 *
	 * @return bool
	 */
	public function is_dripped_lesson( int $lesson_id ) {
		$visible_after               = learndash_get_setting( $lesson_id, 'visible_after' );
		$visible_after_specific_date = learndash_get_setting( $lesson_id, 'visible_after_specific_date' );
		if ( empty( $visible_after ) && empty( $visible_after_specific_date ) ) {
			// this is not a drip lesson.
			return false;
		}

		return true;
	}

	/**
	 * Gets the timestamp of when a user can access the lesson.
	 *
	 * @param int      $lesson_id        Lesson ID.
	 * @param int      $user_id          User ID.
	 * @param int|null $course_id        Optional. Course ID. Default null.
	 * @param boolean  $bypass_transient Optional. Whether to bypass transient cache. Default false.
	 *
	 * @return int|void The timestamp of when the user can access the lesson.
	 * @since 5.2.0
	 */
	public function ld_lesson_access_from( $lesson_id, $user_id, $course_id = null, $bypass_transient = false ) {
		$return = null;

		if ( is_null( $course_id ) ) {
			$course_id = learndash_get_course_id( $lesson_id );
		}

		$courses_access_from = ld_course_access_from( $course_id, $user_id );

		if ( empty( $courses_access_from ) ) {
			$courses_access_from = learndash_user_group_enrolled_to_course_from(
				$user_id,
				$course_id,
				$bypass_transient
			);
		}

		$visible_after = learndash_get_setting( $lesson_id, 'visible_after' );
		if ( $visible_after > 0 ) {
			// Adjust the Course access from by the number of days. Use abs() to ensure no negative days.
			$lesson_access_from = $courses_access_from + abs( $visible_after ) * 24 * 60 * 60;
			/**
			 * Filters the timestamp of when lesson will be visible after.
			 *
			 * @param int $lesson_access_from The timestamp of when the lesson will be available after a specific date.
			 * @param int $lesson_id          Lesson ID.
			 * @param int $user_id            User ID.
			 */
			$lesson_access_from = apply_filters(
				'ld_lesson_access_from__visible_after',
				$lesson_access_from,
				$lesson_id,
				$user_id
			);

			$return = $lesson_access_from;
		} else {
			$visible_after_specific_date = learndash_get_setting( $lesson_id, 'visible_after_specific_date' );
			if ( ! empty( $visible_after_specific_date ) ) {
				if ( ! is_numeric( $visible_after_specific_date ) ) {
					// If we a non-numeric value like a date stamp Y-m-d hh:mm:ss we want to convert it to a GMT timestamp
					$visible_after_specific_date = learndash_get_timestamp_from_date_string(
						$visible_after_specific_date,
						true
					);
				}

				$return = apply_filters(
					'ld_lesson_access_from__visible_after_specific_date',
					$visible_after_specific_date,
					$lesson_id,
					$user_id
				);
			}
		}

		/**
		 * Filters the timestamp of when the user will have access to the lesson.
		 *
		 * @param int $timestamp The timestamp of when the lesson can be accessed.
		 * @param int $lesson_id Lesson ID.
		 * @param int $user_id   User ID.
		 */
		return apply_filters( 'ld_lesson_access_from', $return, $lesson_id, $user_id );
	}
}
