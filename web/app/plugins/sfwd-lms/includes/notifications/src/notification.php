<?php
/**
 * Notification model.
 *
 * @package LearnDash\Notifications
 */

declare( strict_types=1 );

namespace LearnDash_Notification;

use LearnDash\Core\Utilities\Cast;

/**
 *  A base class for handling database relates stuff in a notification
 *
 * Class Notification
 */
class Notification {
	/**
	 * Self ID, it is a WP post type ID
	 *
	 * @var int
	 */
	public $id;
	/**
	 * The event name
	 *
	 * @var string
	 */
	public $trigger;

	/**
	 * The course ID that this should be listen on
	 * If this is 0, then it means any course will work
	 *
	 * @var array
	 */
	public $course_id;

	/**
	 * The group ID this should be listen on
	 * If this is 0, then it means any
	 *
	 * @var array
	 */
	public $group_id;

	/**
	 * The lesson ID that would be listen on, if this is 0 then it means any.
	 *
	 * @var array
	 */
	public $lesson_id;

	/**
	 * The topic ID that would be listen on, if this is 0 then it means any.
	 *
	 * @var array
	 */
	public $topic_id;

	/**
	 * The quiz ID that would be listen on, if this is 0 then it means any.
	 *
	 * @var array
	 */
	public $quiz_id;

	/**
	 * Conditions to check before notifications are sent.
	 *
	 * @var array
	 */
	public $conditions;

	/**
	 * This is use for User hasn't logged in for "X" days
	 *
	 * @var int
	 */
	public $login_reminder_after;

	/**
	 * This is use for the "X" days before course expire
	 *
	 * @var int
	 */
	public $before_course_expiry;

	/**
	 * This is use for the "X" days after course expire
	 *
	 * @var int
	 */
	public $after_course_expiry;

	/**
	 * The recipients that we should send the user
	 *
	 * @var array
	 */
	public $recipients;

	/**
	 * Addition recipients, separate by commas
	 *
	 * @var string
	 */
	public $addition_recipients;

	/**
	 * A value for delay the notification, unit is days
	 *
	 * @var int
	 */
	public $delay;

	/**
	 * The unit that combine with the @delay value
	 *
	 * @var string
	 */
	public $delay_unit;

	/**
	 * This is for the User hasn't logged in for "X" days, if user want to make it trigger forever or one time only.
	 *
	 * @var bool
	 */
	public $only_one_time = 1;

	/**
	 * This is for the enroll_course trigger which if it's enabled, will exclude pre-ordered course.
	 *
	 * @var bool
	 */
	public $exclude_pre_ordered_course;

	/**
	 * Contains the Notification post type
	 *
	 * @var \WP_Post
	 */
	public $post;

	/**
	 * Notification constructor.
	 *
	 * @param \WP_Post $post The WP Post instance.
	 */
	public function __construct( \WP_Post $post ) {
		if ( ! $post instanceof \WP_Post ) {
			$post = get_post( $post );
		}
		$this->post                = $post;
		$this->trigger             = get_post_meta( $post->ID, '_ld_notifications_trigger', true );
		$this->recipients          = $this->filter_array_prop(
			apply_filters(
				'learndash_notification_recipients',
				get_post_meta( $post->ID, '_ld_notifications_recipient', true ),
				$post->ID
			)
		);
		$this->addition_recipients = get_post_meta( $post->ID, '_ld_notifications_bcc', true );
		$this->delay               = absint( get_post_meta( $post->ID, '_ld_notifications_delay', true ) );
		$this->delay_unit          = get_post_meta( $post->ID, '_ld_notifications_delay_unit', true );
		if ( ! in_array( $this->delay_unit, [ 'days', 'hours', 'minutes', 'seconds' ], true ) ) {
			// Fallback.
			$this->delay_unit = 'days';
		}
		$this->course_id                  = $this->filter_trigger_object_prop( get_post_meta( $post->ID, '_ld_notifications_course_id', true ) );
		$this->group_id                   = $this->filter_trigger_object_prop( get_post_meta( $post->ID, '_ld_notifications_group_id', true ) );
		$this->lesson_id                  = $this->filter_trigger_object_prop( get_post_meta( $post->ID, '_ld_notifications_lesson_id', true ) );
		$this->topic_id                   = $this->filter_trigger_object_prop( get_post_meta( $post->ID, '_ld_notifications_topic_id', true ) );
		$this->quiz_id                    = $this->filter_trigger_object_prop( get_post_meta( $post->ID, '_ld_notifications_quiz_id', true ) );
		$conditions                       = get_post_meta( $post->ID, '_ld_notifications_conditions', true );
		$this->conditions                 = ! is_array( $conditions ) ? [] : $conditions;
		$this->login_reminder_after       = absint(
			get_post_meta(
				$post->ID,
				'_ld_notifications_not_logged_in_days',
				true
			)
		);
		$this->before_course_expiry       = absint(
			get_post_meta(
				$post->ID,
				'_ld_notifications_course_expires_days',
				true
			)
		);
		$this->after_course_expiry        = absint(
			get_post_meta(
				$post->ID,
				'_ld_notifications_course_expires_after_days',
				true
			)
		);
		$this->only_one_time              = absint( get_post_meta( $post->ID, '_ld_notifications_send_only_once', true ) );
		$this->exclude_pre_ordered_course = absint( get_post_meta( $post->ID, '_ld_notifications_exclude_pre_ordered_course', true ) );
	}

	/**
	 * Filter recipients property to always return array.
	 *
	 * @since 5.2.0
	 *
	 * @param mixed $value The raw property value.
	 *
	 * @return array<int, mixed>
	 */
	private function filter_array_prop( $value ): array {
		if ( ! is_array( $value ) ) {
			if ( trim( $value ) === '' ) {
				$value = [];
			} else {
				$value = [ $value ];
			}
		}

		return $value;
	}

	/**
	 * Filter trigger object data such as course_id, lesson_id, etc to always return new array format.
	 *
	 * @since 5.2.0
	 *
	 * @param mixed $value Raw meta value for the trigger object prop.
	 *
	 * @return array
	 */
	private function filter_trigger_object_prop( $value ): array {
		if ( ! is_array( $value ) ) {
			if ( intval( $value ) === 0 ) {
				$value = [ 'all' ];
			} elseif ( ! empty( $value ) ) {
				$value = [ $value ];
			} else {
				$value = [];
			}
		}

		$value = array_map(
			/**
			 * Normalize a single stored value to its canonical type.
			 *
			 * @param string|int $v Raw stored element of a trigger object prop.
			 *
			 * @return string|int
			 */
			function ( $v ) {
				if ( empty( Cast::to_int( $v ) ) ) {
					return 'all';
				}

				return is_numeric( $v ) ? Cast::to_int( $v ) : Cast::to_string( $v );
			},
			$value
		);

		return array_values( array_unique( $value ) );
	}

	/**
	 * Populate the shortcode data
	 *
	 * @param array $args Shortcode args.
	 */
	public function populate_shortcode_data( array $args = [] ) {
		global $ld_notifications_shortcode_data;
		$args['notification_id']         = $this->post->ID;
		$ld_notifications_shortcode_data = $args;
	}

	/**
	 * Check if the email is already sent
	 *
	 * @param int   $user_id The user ID.
	 * @param mixed ...$args The args.
	 *
	 * @return bool
	 */
	public function is_sent( int $user_id, ...$args ) {
		$args = array_map( 'sanitize_title', $args );
		$meta = 'ld_sent_notification_' . implode( '_', $args );
		$sent = get_user_meta( $user_id, $meta, true );

		// if it was sent, then should be a timestamp.
		return filter_var( $sent, FILTER_VALIDATE_INT );
	}

	/**
	 * Adding a flag when an email is sent/queued, preventing duplicate email send
	 *
	 * @param int   $user_id The user ID.
	 * @param mixed ...$args The slug data.
	 *
	 * @return bool
	 */
	public function mark_sent( int $user_id, ...$args ) {
		$args = array_map( 'sanitize_title', $args );
		$meta = 'ld_sent_notification_' . implode( '_', $args );

		return update_user_meta( $user_id, $meta, time() );
	}

	/**
	 * Mark the current notification as unsent status.
	 *
	 * @param int   $user_id The user ID.
	 * @param mixed ...$args The slug data.
	 *
	 * @return bool
	 */
	public function mark_unsent( int $user_id, ...$args ) {
		$args = array_map( 'sanitize_title', $args );
		$meta = 'ld_sent_notification_' . implode( '_', $args );

		return delete_user_meta( $user_id, $meta );
	}

	/**
	 * Base on the condition of the settings, we maybe get
	 * 1. The current user's email
	 * 2. Group owners' emails
	 * 3. Admin's emails
	 *
	 * @since 5.2.0
	 *
	 * @param int      $user_id   The user ID.
	 * @param int|null $course_id The course ID.
	 * @param int|null $group_id  The group ID.
	 *
	 * @return array
	 */
	public function gather_emails( int $user_id, ?int $course_id = null, ?int $group_id = null ) {
		$this->addition_recipients = apply_filters(
			'learndash_notification_bcc',
			$this->addition_recipients,
			$this->post->ID
		);
		$emails                    = explode( ',', str_replace( ' ', '', $this->addition_recipients ) );
		foreach ( $this->recipients as $recipient ) {
			switch ( $recipient ) {
				case 'user':
					$user = get_user_by( 'id', $user_id );
					if ( is_object( $user ) ) {
						$emails[] = $user->user_email;
					}
					break;
				case 'group_leader':
					/**
					 * In this context, a group leaders should be the leader of this user, if any
					 */
					$group_ids = [];
					if ( ! is_null( $group_id ) ) {
						$group_ids[] = absint( $group_id );
					}
					if ( ! is_null( $course_id ) ) {
						$course_group_ids = learndash_get_course_groups( $course_id );
						// we have a list of groups, but it can be different from the current user, so need a check.
						foreach ( $course_group_ids as $key => $course_group_id ) {
							if ( ! learndash_is_user_in_group( $user_id, $course_group_id ) ) {
								unset( $course_group_ids[ $key ] );
							}
						}
						$group_ids = array_merge( $group_ids, $course_group_ids );
					}
					$group_ids = array_unique( $group_ids );
					$group_ids = array_filter( $group_ids );
					foreach ( $group_ids as $group_id ) {
						$users = learndash_get_groups_administrators( $group_id );
						foreach ( $users as $user ) {
							// Make sure the user has group leader or administrator role.
							if ( in_array( 'group_leader', $user->roles, true ) || in_array( 'administrator', $user->roles, true ) ) {
								$emails[] = $user->user_email;
							}
						}
					}
					break;
				case 'admin':
					$users = get_users(
						[
							'role' => 'administrator',
						]
					);
					foreach ( $users as $user ) {
						$emails[] = $user->user_email;
					}
					break;
			}
		}
		$emails = array_unique( $emails );
		$emails = array_filter( $emails );
		// have to validate the emails as it user input.
		foreach ( $emails as $key => $email ) {
			if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				unset( $emails[ $key ] );
			}
		}

		/**
		 * Filter hook for recipients emails
		 *
		 * @param array $emails     Returned email addresses
		 * @param array $recipients Recipients type of a notification
		 * @param int   $user_id    User ID which trigger a notification
		 * @param int   $course_id  Course ID which trigger a notification
		 */

		return apply_filters(
			'learndash_notification_recipients_emails',
			$emails,
			$this->recipients,
			$user_id,
			$course_id,
			$group_id
		);
	}
}
