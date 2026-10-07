<?php
/**
 * Notification functions.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Notifications
 *
 * cspell:ignore notif
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/****************************
 * ***** UPDATE FUNCTIONS ****
 ****************************/

/**
 * Delete sent notification records if user is unenrolled
 *
 * @param int   $user_id        ID of user who enroll
 * @param int   $course_id      ID of course enrolled into
 * @param array $access_list    List of users who have access to the course
 * @param bool  $remove         True if remove user access from a course | false otherwise
 */
function learndash_notifications_delete_sent_emails_record( $user_id, $course_id, $access_list, $remove ) {
	// Exit if user is not removed from a course
	if ( $remove !== true ) {
		return;
	}

	// delete enrolled course trigger record
	delete_user_meta( $user_id, 'ld_sent_notification_enroll_course_' . $course_id );

	// delete lesson available trigger record
	$lessons = learndash_get_lesson_list( $course_id );
	foreach ( $lessons as $lesson ) {
		delete_user_meta( $user_id, 'ld_sent_notification_lesson_available_' . $lesson->ID );
	}
}

// add_action( 'learndash_update_course_access', 'learndash_notifications_delete_sent_emails_record', 10, 4 );

/**
 * Delete sent notification records if user is unenrolled from group
 *
 * @param int   $user_id        ID of user who enroll
 * @param int   $course_id      ID of course enrolled into
 * @param array $access_list    List of users who have access to the course
 * @param bool  $remove         True if remove user access from a course | false otherwise
 */
function learndash_notifications_delete_sent_emails_record_on_unenrolled_group( $user_id, $group_id ) {
	$courses = learndash_group_enrolled_courses( $group_id );

	foreach ( $courses as $course_id ) {
		// delete enrolled course trigger record
		delete_user_meta( $user_id, 'ld_sent_notification_enroll_course_' . $course_id );

		// delete lesson available trigger record
		$lessons = learndash_get_lesson_list( $course_id );
		foreach ( $lessons as $lesson ) {
			delete_user_meta( $user_id, 'ld_sent_notification_lesson_available_' . $lesson->ID );
		}
	}
}

add_action( 'ld_removed_group_access', 'learndash_notifications_delete_sent_emails_record_on_unenrolled_group', 10, 2 );

/**
 * Check for new scheduled notification when a notification is created or updated
 *
 * @param int    $n_id    ID of the post
 * @param object $post    WP Post object
 * @param bool   $update  Post is being updated or not
 */
function learndash_notifications_update_lesson_available_notification_notif_hook( $n_id, $post, $update ) {
	if ( $post->post_type != 'ld-notification' ) {
		return;
	}

	$notification_type = get_post_meta( $n_id, '_ld_notifications_trigger', true );
	$delay_days        = (int) get_post_meta( $n_id, '_ld_notifications_delay', true );

	if ( $notification_type != 'lesson_available' ) {
		return;
	}

	$course_id = intval( $_POST['_ld_notifications_course_id'] );

	if ( ! empty( $course_id ) && is_numeric( $course_id ) ) {
		learndash_notifications_helper_update_lesson_available_notification( $post, $course_id );
	} else {
		// Get courses
		$courses = learndash_notifications_get_all_courses();

		foreach ( $courses as $course ) {
			learndash_notifications_helper_update_lesson_available_notification( $post, $course->ID );
		}
	}
}

// add_action( 'save_post', 'learndash_notifications_update_lesson_available_notification_notif_hook', 99, 3 );

/**
 * Helper function to update lesson available notification
 *
 * @param object $post      WP_Post object
 * @param int    $course_id Course ID
 */
function learndash_notifications_helper_update_lesson_available_notification( $post, $course_id ) {
	$n_id        = $post->ID;
	$n_lesson_id = get_post_meta( $n_id, '_ld_notifications_lesson_id', true );

	// Get course access user list
	$c_meta = get_post_meta( $course_id, '_sfwd-courses', true );
	$c_meta = maybe_unserialize( $c_meta );

	// Course access list
	$c_access_list = isset( $c_meta['sfwd-courses_course_access_list'] ) ? $c_meta['sfwd-courses_course_access_list'] : [];

	// If course has no access list, continue
	if ( empty( $c_access_list ) ) {
		return;
	}

	$c_access_list = explode( ',', trim( $c_access_list ) );

	// Loop through user
	foreach ( $c_access_list as $u_id ) {
		$access_from = get_user_meta( $u_id, 'course_' . $course_id . '_access_from', true );

		// Add or update the delayed notification emails
		// New notification
		if ( $post->post_modified_gmt == $post->post_date_gmt ) {
			if ( isset( $n_lesson_id ) && is_numeric( $n_lesson_id ) ) {
				// Save new email
				$course_access_from = ld_course_access_from( $course_id, $u_id );
				$lesson_access_from = ld_lesson_access_from( $n_lesson_id, $u_id, $course_id, $bypass_transient = true );

				if ( ! is_null( $lesson_access_from ) ) {
					// Exit if notification already sent
					// $sent = get_user_meta( $u_id, 'ld_sent_notification_lesson_available_' . $n_lesson_id, true );

					// if ( $sent == 1 ) {
					// continue;
					// }

					learndash_notifications_send_notification( $post, $u_id, $course_id, $n_lesson_id, null, null, null, $lesson_access_from );

					// add_user_meta( $u_id, 'ld_sent_notification_lesson_available_' . $n_lesson_id, 1, true );
				}
			} else {
				$lessons = learndash_get_lesson_list( $course_id );

				foreach ( $lessons as $lesson ) {
					// Save new email
					$course_access_from = ld_course_access_from( $course_id, $u_id );
					$lesson_access_from = ld_lesson_access_from( $lesson->ID, $u_id, $course_id, $bypass_transient = true );

					if ( ! is_null( $lesson_access_from ) ) {
						// Exit if notification already sent
						// $sent = get_user_meta( $u_id, 'ld_sent_notification_lesson_available_' . $lesson->ID, true );

						// if ( $sent == 1 ) {
						// continue;
						// }

						learndash_notifications_send_notification( $post, $u_id, $course_id, $lesson->ID, null, null, null, $lesson_access_from );

						// add_user_meta( $u_id, 'ld_sent_notification_lesson_available_' . $lesson->ID, 1, true );
					}
				}
			}
		} else { // Else if update a notification
			// Get delayed emails
			$emails = learndash_notifications_get_all_delayed_emails(
				[
					'notification_id' => $n_id,
					'user_id'         => $u_id,
				]
			);

			// Loop through the emails
			foreach ( $emails as $email ) {
				$data = maybe_unserialize( $email['shortcode_data'] );

				if (
					$data['course_id'] != $course_id ||
					$data['lesson_id'] != $n_lesson_id ||
					$data['user_id'] != $u_id ||
					$data['notification_id'] != $n_id
				) {
					continue;
				}

				if ( isset( $n_lesson_id ) && is_numeric( $n_lesson_id ) ) {
					if ( isset( $data['user_id'] ) && $data['user_id'] == $u_id && isset( $data['lesson_id'] ) && $data['lesson_id'] == $n_lesson_id ) {
						// Remove the email
						learndash_notifications_delete_delayed_email_by_id( $email['id'] );

						delete_user_meta( $u_id, 'ld_sent_notification_lesson_available_' . $n_lesson_id );
					}

					// Save new email
					$course_access_from = ld_course_access_from( $course_id, $u_id );
					$lesson_access_from = ld_lesson_access_from( $n_lesson_id, $u_id, $course_id, $bypass_transient = true );

					if ( ! is_null( $lesson_access_from ) ) {
						// Exit if notification already sent
						// $sent = get_user_meta( $u_id, 'ld_sent_notification_lesson_available_' . $n_lesson_id, true );

						// if ( $sent == 1 ) {
						// continue;
						// }

						learndash_notifications_send_notification( $post, $u_id, $course_id, $n_lesson_id, null, null, null, $lesson_access_from );

						// add_user_meta( $u_id, 'ld_sent_notification_lesson_available_' . $n_lesson_id, 1, true );
					}
				} else // else lesson is not numeric
				{
					$lessons = learndash_get_lesson_list( $course_id );

					foreach ( $lessons as $lesson ) {
						if ( isset( $data['user_id'] ) && $data['user_id'] == $u_id && isset( $data['lesson_id'] ) && $data['lesson_id'] == $lesson->ID ) {
							// Remove the email
							learndash_notifications_delete_delayed_email_by_id( $email['id'] );

							delete_user_meta( $u_id, 'ld_sent_notification_lesson_available_' . $lesson->ID );
						}

						// Save new email
						$course_access_from = ld_course_access_from( $course_id, $u_id );
						$lesson_access_from = ld_lesson_access_from( $lesson->ID, $u_id, $course_id, $bypass_transient = true );

						if ( ! is_null( $lesson_access_from ) ) {
							// Exit if notification already sent
							// $sent = get_user_meta( $u_id, 'ld_sent_notification_lesson_available_' . $lesson->ID, true );

							// if ( $sent == 1 ) {
							// continue;
							// }

							learndash_notifications_send_notification( $post, $u_id, $course_id, $lesson->ID, null, null, null, $lesson_access_from );

							// add_user_meta( $u_id, 'ld_sent_notification_lesson_available_' . $lesson->ID, 1, true );
						}
					}
				}
			}
		}
	}
}

/**
 * Update lesson available notifications when a lesson is created or updated
 *
 * @param int    $lesson_id Lesson ID
 * @param object $post      Lesson WP Post object
 * @param bool   $update    Update post or not
 */
function learndash_notifications_update_lesson_available_notification_lesson_hook( $lesson_id, $post, $update ) {
	if ( $post->post_type != 'sfwd-lessons' ) {
		return;
	}

	$courses = learndash_get_courses_for_step( $lesson_id, true );

	foreach ( $courses as $course_id => $course_title ) {
		$course_id = learndash_get_course_id( $lesson_id );

		$c_meta = get_post_meta( $course_id, '_sfwd-courses', true );
		$c_meta = maybe_unserialize( $c_meta );

		// Course access list
		$c_access_list = isset( $c_meta['sfwd-courses_course_access_list'] ) ? $c_meta['sfwd-courses_course_access_list'] : '';

		// If course has no access list, continue
		if ( empty( $c_access_list ) ) {
			return;
		}

		$c_access_list = explode( ',', trim( $c_access_list ) );

		// Loop through user
		foreach ( $c_access_list as $user_id ) {
			learndash_notifications_delete_delayed_emails_by_user_id_lesson_id( $user_id, $lesson_id );

			delete_user_meta( $user_id, 'ld_sent_notification_lesson_available_' . $lesson_id );

			$lesson_access_from = ld_lesson_access_from( $lesson_id, $user_id, $course_id, $bypass_transient = true );

			if ( ! is_null( $lesson_access_from ) ) {
				// Exit if notification already sent
				// $sent = get_user_meta( $user_id, 'ld_sent_notification_lesson_available_' . $lesson_id, true );
				// if ( $sent == 1 ) {
				// continue;
				// }

				learndash_notifications_send_notifications( 'lesson_available', $user_id, $course_id, $lesson_id, null, null, null, $lesson_access_from );

				// add_user_meta( $user_id, 'ld_sent_notification_lesson_available_' . $lesson_id, 1, true );
			}
		}
	}
}

// add_action( 'save_post', 'learndash_notifications_update_lesson_available_notification_lesson_hook', 99, 3 );

/**
 * Update lesson available scheduled notifications in database
 *
 * Executed in cron.php
 */
function learndash_notifications_update_scheduled_lesson_available_notifications_cron() {
	error_reporting( E_ALL );
	ini_set( 'display_errors', 1 );

	// check existing "lesson_available" notifications
	$notifications = learndash_notifications_get_notifications( 'lesson_available' );

	// loop for each notification
	foreach ( $notifications as $notification ) {
		// get course ID and lesson ID
		$course_id = get_post_meta( $notification->ID, '_ld_notifications_course_id', true );
		$lesson_id = get_post_meta( $notification->ID, '_ld_notifications_lesson_id', true );

		// if isset course ID and lesson ID
		if ( isset( $course_id ) && is_numeric( $course_id ) && ! empty( $course_id ) && isset( $lesson_id ) && is_numeric( $lesson_id ) && ! empty( $lesson_id ) ) {
			// get enrolled user
			$users_ids = learndash_get_users_for_course( $course_id );

			if ( is_array( $users_ids ) ) {
				continue;
			}

			foreach ( $users_ids->get_results() as $user_id ) {
				$lesson_access_from = ld_lesson_access_from( $lesson_id, $user_id, $course_id, $bypass_transient = true );

				// Continue if access timestamp lower than current time
				if ( $lesson_access_from < time() ) {
					learndash_notifications_delete_delayed_emails_by_multiple_shortcode_data_key(
						[
							'notification_id' => $notification->ID,
							'user_id'         => $user_id,
							'course_id'       => $course_id,
							'lesson_id'       => $lesson_id,
						]
					);

					continue;
				}

				$update_where = [
					'notification_id' => $notification->ID,
					'user_id'         => $user_id,
					'course_id'       => $course_id,
					'lesson_id'       => $lesson_id,
				];

				// update queued notifications in DB
				learndash_notifications_send_notification( $notification, $user_id, $course_id, $lesson_id, $topic_id = null, $quiz_id = null, $assignment_id = null, $lesson_access_from, $question_id = null, $group_id = null, $update_where );
			}
			// elseif isset only course ID
		} elseif ( isset( $course_id ) && is_numeric( $course_id ) && ! empty( $course_id ) && ( ! isset( $lesson_id ) || ! is_numeric( $lesson_id ) || empty( $lesson_id ) ) ) {
			// get all lessons of the course
			$lessons = learndash_get_lesson_list( $course_id );

			// get enrolled user
			$users_ids = learndash_get_users_for_course( $course_id );

			if ( is_array( $users_ids ) ) {
				continue;
			}

			// loop through each lesson
			foreach ( $lessons as $lesson ) {
				foreach ( $users_ids->get_results() as $user_id ) {
					$lesson_access_from = ld_lesson_access_from( $lesson->ID, $user_id, $course_id, $bypass_transient = true );

					// Continue if access timestamp lower than current time
					if ( $lesson_access_from < time() ) {
						learndash_notifications_delete_delayed_emails_by_multiple_shortcode_data_key(
							[
								'notification_id' => $notification->ID,
								'user_id'         => $user_id,
								'course_id'       => $course_id,
								'lesson_id'       => $lesson->ID,
							]
						);

						continue;
					}

					$update_where = [
						'notification_id' => $notification->ID,
						'user_id'         => $user_id,
						'course_id'       => $course_id,
						'lesson_id'       => $lesson->ID,
					];

					// update queued notifications in DB
					learndash_notifications_send_notification( $notification, $user_id, $course_id, $lesson->ID, $topic_id = null, $quiz_id = null, $assignment_id = null, $lesson_access_from, $question_id = null, $group_id = null, $update_where );
				}
			}
			// else
		} else {
			// get all courses
			$courses = learndash_notifications_get_all_courses();

			// loop through each course
			foreach ( $courses as $course ) {
				// get all lessons of the course
				$lessons = learndash_get_lesson_list( $course->ID );

				// get enrolled user
				$users_ids = learndash_get_users_for_course( $course->ID );

				if ( is_array( $users_ids ) ) {
					continue;
				}

				// loop through each lesson
				foreach ( $lessons as $lesson ) {
					foreach ( $users_ids->get_results() as $user_id ) {
						$lesson_access_from = ld_lesson_access_from( $lesson->ID, $user_id, $course->ID, $bypass_transient = true );

						// Continue if access timestamp lower than current time
						if ( $lesson_access_from < time() ) {
							learndash_notifications_delete_delayed_emails_by_multiple_shortcode_data_key(
								[
									'notification_id' => $notification->ID,
									'user_id'         => $user_id,
									'course_id'       => $course->ID,
									'lesson_id'       => $lesson->ID,
								]
							);

							continue;
						}

						$update_where = [
							'notification_id' => $notification->ID,
							'user_id'         => $user_id,
							'course_id'       => $course->ID,
							'lesson_id'       => $lesson->ID,
						];

						// update queued notifications in
						learndash_notifications_send_notification( $notification, $user_id, $course->ID, $lesson->ID, $topic_id = null, $quiz_id = null, $assignment_id = null, $lesson_access_from, $question_id = null, $group_id = null, $update_where );
					}
				}
			}
		}
	} // endforeach $notifications
}

/**
 * Delete delayed emails stored in DB if Notification, Course, Lesson, etc is deleted
 *
 * @param int $post_id  WP Post ID
 */
function learndash_notifications_delete_delayed_emails_when_post_deleted( $post_id ) {
	$post      = get_post( $post_id );
	$post_type = $post->post_type;

	if ( $post_type != 'ld-notification' && $post_type != 'sfwd-courses' && $post_type != 'sfwd-lessons' && $post_type != 'sfwd-topic' && $post_type != 'sfwd-quiz' && $post_type != 'sfwd-assignment' ) {
		return;
	}

	switch ( $post_type ) {
		case 'ld-notification':
			learndash_notifications_delete_delayed_emails_by( 'notification_id', $post->ID );
			break;

		case 'sfwd-courses':
			learndash_notifications_delete_delayed_emails_by( 'course_id', $post->ID );
			break;

		case 'sfwd-lessons':
			learndash_notifications_delete_delayed_emails_by( 'lesson_id', $post->ID );
			break;

		case 'sfwd-topic':
			learndash_notifications_delete_delayed_emails_by( 'topic_id', $post->ID );
			break;

		case 'sfwd-quiz':
			learndash_notifications_delete_delayed_emails_by( 'quiz_id', $post->ID );
			break;

		case 'sfwd-assignment':
			learndash_notifications_delete_delayed_emails_by( 'assignment_id', $post->ID );
			break;
	}
}

// add_action( 'wp_trash_post', 'learndash_notifications_delete_delayed_emails_when_post_deleted', 10, 1 );
// add_action( 'before_delete_post', 'learndash_notifications_delete_delayed_emails_when_post_deleted', 10, 1 );

/**
 * Update delayed emails in DB if user details are updated
 *
 * @since 5.2.0
 *
 * @param int     $user_id       The ID of the updated user.
 * @param WP_User $old_user_data The user object as it was before the update.
 *
 * @return void
 */
function learndash_notifications_update_delayed_emails_when_user_updated( $user_id, $old_user_data ) {
	$user      = get_user_by( 'id', $user_id );
	$old_email = $old_user_data->user_email;

	if ( $user->user_email == $old_email ) {
		return;
	}

	// Get all emails first before deleted
	$emails = learndash_notifications_get_all_delayed_emails_by_recipient( $old_email );

	// Delete all delayed emails with old email as recipient
	learndash_notifications_delete_delayed_emails_by_email( $old_email );

	if ( is_array( $emails ) ) {
		foreach ( $emails as $email ) {
			$recipient = maybe_unserialize( $email['recipient'] );
			$key       = array_search( $old_email, $recipient );

			if ( $key !== false ) {
				array_splice( $recipient, $key, 1 );
			}

			// Add new email to recipient array
			$recipient[] = $user->user_email;

			// Insert new delayed email with new recipient
			learndash_notifications_insert_delayed_email( $email['title'], $email['message'], $recipient, maybe_unserialize( $email['shortcode_data'] ), $email['sent_on'], maybe_unserialize( $email['bcc'] ) );
		}
	}
}

// add_action( 'profile_update', 'learndash_notifications_update_delayed_emails_when_user_updated', 10, 2 );

/***************************
 * ** NOTIFICATION TRIGGER **
 ***************************/

// GROUP ENROLLMENT ///////
function learndash_notifications_enroll_group( $user_id, $group_id ) {
	learndash_notifications_send_notifications( 'enroll_group', $user_id, $course_id = null, $lesson_id = null, $topic_id = null, $quiz_id = null, $assignment_id = null, $lesson_access_from = null, $question_id = null, $group_id );
}

// add_action( 'ld_added_group_access', 'learndash_notifications_enroll_group', 10, 2 );

/**
 * Send learndash notification email when user enrolls into a course
 *
 * @param int   $user_id        ID of user who enroll
 * @param int   $course_id      ID of course enrolled into
 * @param array $access_list    List of users who have access to the course
 * @param bool  $remove         True if remove user access from a course | false otherwise
 */
function learndash_notifications_enroll_course( $user_id, $course_id, $access_list, $remove ) {
	// Exit if user removed from a course
	if ( $remove === true ) {
		return;
	}

	// Exit if notification already sent
	$sent = get_user_meta( $user_id, 'ld_sent_notification_enroll_course_' . $course_id, true );
	if ( $sent == 1 ) {
		return;
	}

	// Check if a course is already completed
	if ( learndash_course_completed( $user_id, $course_id ) ) {
		return;
	}

	learndash_notifications_send_notifications( 'enroll_course', $user_id, $course_id );

	add_user_meta( $user_id, 'ld_sent_notification_enroll_course_' . $course_id, 1, true );
}

// add_action( 'learndash_update_course_access', 'learndash_notifications_enroll_course', 10, 4 );

/**
 * Queue course enrollment trigger in cron for user added via group
 *
 * @param int $user_id      WP_User ID
 * @param int $group_id     Group WP_Post ID
 */
function learndash_notifications_enroll_course_via_group_enrollment( $user_id, $group_id ) {
	$group_courses = learndash_group_enrolled_courses( $group_id );

	if ( empty( $group_courses ) ) {
		return;
	}

	$queue = get_option( '_ld_notifications_enroll_group_queue', [] );

	if ( ! isset( $queue['group_id'] ) ) {
		$queue[ $group_id ] = [
			'users'   => [ $user_id ],
			'courses' => $group_courses,
		];
	} else {
		$queue[ $group_id ]['users'][] = $user_id;
	}

	update_option( '_ld_notifications_enroll_group_queue', $queue );
}

// add_action( 'ld_added_group_access', 'learndash_notifications_enroll_course_via_group_enrollment', 10, 2 );

/**
 * Queue course enrollment trigger in cron for user added via group
 *
 * @param int   $group_id      Post ID of a group
 * @param array $group_leaders Array of post ID of the object
 * @param array $group_users   Array of post ID of the object
 * @param array $group_courses Array of post ID of the object
 */
function learndash_notifications_enroll_course_via_group( $group_id, $group_leaders, $group_users, $group_courses ) {
	// Retrieve from database to get the latest data
	$group_users   = learndash_get_groups_user_ids( $group_id );
	$group_courses = learndash_group_enrolled_courses( $group_id );

	if ( empty( $group_users ) || empty( $group_courses ) ) {
		return;
	}

	$queue              = get_option( '_ld_notifications_enroll_group_queue', [] );
	$queue[ $group_id ] = [
		'users'   => $group_users,
		'courses' => $group_courses,
	];

	update_option( '_ld_notifications_enroll_group_queue', $queue );
}

// add_action( 'ld_group_postdata_updated', 'learndash_notifications_enroll_course_via_group', 9999, 4 ); // Big priority argument so it can correctly get group data

/**
 * Send course enrollment notification when group is added via course edit page
 *
 * @since 5.2.0
 * @param int $course_id WP_Post course id
 * @param int $group_id  WP_Post group id
 * @return void
 */
function learndash_notifications_enroll_course_via_course_group_update( $course_id, $group_id ) {
	$group_users = learndash_get_groups_user_ids( $group_id );

	if ( empty( $group_users ) ) {
		return;
	}

	$queue = get_option( '_ld_notifications_enroll_group_queue', [] );

	if ( isset( $queue[ $group_id ]['courses'] ) ) {
		$group_courses = $queue[ $group_id ]['courses'];
	} else {
		$group_courses = [];
	}

	$queue[ $group_id ] = [
		'users'   => $group_users,
		'courses' => array_merge( $group_courses, [ $course_id ] ),
	];

	update_option( '_ld_notifications_enroll_group_queue', $queue );
}

// add_action( 'ld_added_course_group_access', 'learndash_notifications_enroll_course_via_course_group_update', 10, 2 );

/**
 * Remove DB record for sent group course enrollment notifications
 *
 * @param int $course_id  ID of a course
 * @param int $group_id   ID of a group
 */
function learndash_notification_delete_sent_group_emails_record_by_course( $course_id, $group_id ) {
	$users = learndash_get_groups_user_ids( $group_id );
	foreach ( $users as $user_id ) {
		delete_user_meta( $user_id, 'ld_sent_notification_enroll_course_' . $course_id );
	}
}

add_action( 'ld_removed_course_group_access', 'learndash_notification_delete_sent_group_emails_record_by_course', 10, 2 );

/**
 * Send learndash notification email when user completes a course
 *
 * @param array $data Course data with keys: 'user' (user object), 'course' (post object),
 *                    'progress' (array)
 */
function learndash_notifications_complete_course( $data ) {
	$course_progress_old = get_user_meta( $data['user']->ID, '_sfwd-course_progress', true );
	$course_id           = $data['course']->ID;

	// Exit if user already has completed the course
	if ( isset( $course_progress_old[ $course_id ]['total'] ) && isset( $course_progress_old[ $course_id ]['completed'] ) && $course_progress_old[ $course_id ]['total'] == $course_progress_old[ $course_id ]['completed'] ) {
		return;
	}

	learndash_notifications_send_notifications( 'complete_course', $data['user']->ID, $data['course']->ID );
}
// Let learndash_course_completed_store_time() fired first
// add_action( 'learndash_before_course_completed', 'learndash_notifications_complete_course', 15, 1 );

/**
 * Send learndash notification email when user completes a lesson
 *
 * @param array $args learndash_update_user_activity action hook arguments
						e.g. Array (
							[activity_id] => 769
							[course_id] => 1248
							[post_id] => 1260
							[user_id] => 2
							[activity_type] => lesson
							[activity_status] => 1
							[activity_started] => 1579503611
							[activity_completed] => 1579503611
							[activity_updated] =>
							[activity_action] => update
							[activity_meta] =>
						)
 */
function learndash_notifications_complete_lesson( $args ) {
	if ( $args['activity_type'] === 'lesson' && in_array( $args['activity_action'], [ 'insert', 'update' ] ) && $args['activity_status'] == 1 && ! empty( $args['activity_completed'] ) ) {
		learndash_notifications_send_notifications( 'complete_lesson', $args['user_id'], $args['course_id'], $args['post_id'] );
	}
}

// add_action( 'learndash_update_user_activity', 'learndash_notifications_complete_lesson', 10, 1 );

/**
 * Send learndash notification email when a scheduled lesson is available to user
 *
 * @param int   $user_id        ID of user who enroll
 * @param int   $course_id      ID of course enrolled into
 * @param array $access_list    List of users who have access to the course
 * @param bool  $remove         True if remove user access from a course | false otherwise
 */
function learndash_notifications_lesson_available( $user_id, $course_id, $access_list, $remove ) {
	// Exit if user removed from a course
	if ( $remove === true ) {
		return;
	}

	$lessons = learndash_get_lesson_list( $course_id );

	foreach ( $lessons as $lesson ) {
		$lesson_access_from = ld_lesson_access_from( $lesson->ID, $user_id, $course_id, $bypass_transient = true );

		if ( ! is_null( $lesson_access_from ) ) {
			learndash_notifications_send_notifications(
				'lesson_available',
				$user_id,
				$course_id,
				$lesson->ID,
				null,
				null,
				null,
				$lesson_access_from,
				null,
				null,
				[
					'user_id'   => $user_id,
					'course_id' => $course_id,
					'lesson_id' => $lesson->ID,
				]
			);
		}
	}
}

// add_action( 'learndash_update_course_access', 'learndash_notifications_lesson_available', 10, 4 );

/**
 * Schedule lesson available notification in DB for users added via group
 *
 * @since 5.2.0
 * @param int   $group_id      Post ID of a group
 * @param array $group_leaders Array of post ID of the object.
 *                              If no new leader is added in group,
 *                              this will be empty.
 * @param array $group_users   Array of post ID of the object.
 *                              If no new user is added in group,
 *                              this will be empty.
 * @param array $group_courses Array of post ID of the object.
 *                              If no new course is added in group,
 *                              this will be empty.
 */
function learndash_notifications_lesson_available_via_group( $group_id, $group_leaders, $group_users, $group_courses ) {
	// Retrieve from database to get the latest data
	$group_users   = learndash_get_groups_user_ids( $group_id );
	$group_courses = learndash_group_enrolled_courses( $group_id );

	if ( empty( $group_users ) || empty( $group_courses ) ) {
		return;
	}

	foreach ( $group_courses as $course_id ) {
		foreach ( $group_users as $user_id ) {
			$lessons = learndash_get_lesson_list( $course_id );

			foreach ( $lessons as $lesson ) {
				$lesson_access_from = ld_lesson_access_from( $lesson->ID, $user_id, $course_id, $bypass_transient = true );

				if ( ! is_null( $lesson_access_from ) ) {
					learndash_notifications_send_notifications(
						'lesson_available',
						$user_id,
						$course_id,
						$lesson->ID,
						null,
						null,
						null,
						$lesson_access_from,
						null,
						null,
						[
							'user_id'   => $user_id,
							'course_id' => $course_id,
							'lesson_id' => $lesson->ID,
						]
					);
				}
			}
		}
	}
}

// add_action( 'ld_group_postdata_updated', 'learndash_notifications_lesson_available_via_group', 9999, 4 ); // Big priority argument so it can correctly get group data

/**
 * Schedule lesson available notification in DB for users whose group is added via course edit page
 *
 * @since 5.2.0
 * @param int $course_id WP_Post Course id
 * @param int $group_id  WP_Post Group id
 * @return void
 */
function learndash_notifications_lesson_available_via_course_group_update( $course_id, $group_id ) {
	$group_users = learndash_get_groups_user_ids( $group_id );

	if ( empty( $group_users ) ) {
		return;
	}

	foreach ( $group_users as $user_id ) {
		$lessons = learndash_get_lesson_list( $course_id );

		foreach ( $lessons as $lesson ) {
			$lesson_access_from = ld_lesson_access_from( $lesson->ID, $user_id, $course_id, $bypass_transient = true );

			if ( ! is_null( $lesson_access_from ) ) {
				learndash_notifications_send_notifications(
					'lesson_available',
					$user_id,
					$course_id,
					$lesson->ID,
					null,
					null,
					null,
					$lesson_access_from,
					null,
					null,
					[
						'user_id'   => $user_id,
						'course_id' => $course_id,
						'lesson_id' => $lesson->ID,
					]
				);
			}
		}
	}
}

// add_action( 'ld_added_course_group_access', 'learndash_notifications_lesson_available_via_course_group_update', 10, 2 );

/**
 * Send learndash notification email when user completes a topic
 *
 * @param array $data Topic data with array keys: 'user' (int), 'course' (post object),
 *                    'lesson' (post object), 'topic' (post object), 'progress' (array)
 */
function learndash_notifications_complete_topic( $data ) {
	learndash_notifications_send_notifications( 'complete_topic', $data['user']->ID, $data['course']->ID, $data['lesson']->ID, $data['topic']->ID );
}

// add_action( 'learndash_topic_completed', 'learndash_notifications_complete_topic', 10, 1 );


/*************************************
 * ********* QUIZ TRIGGERS ************
 *************************************/

/**
 * Send learndash notification email when user passes a quiz
 *
 * @param array  $quiz_data      Data of the quiz taken
 * @param object $current_user   Current user WP object who take the quiz
 */
function learndash_notifications_pass_quiz( $quiz_data, $current_user ) {
	learndash_notifications_set_global_quiz_result( $quiz_data );

	if ( $quiz_data['has_graded'] ) {
		foreach ( $quiz_data['graded'] as $id => $essay ) {
			if ( $essay['status'] == 'not_graded' ) {
				return;
			}
		}
	}

	if ( is_object( $quiz_data['course'] ) ) {
		$course_id = $quiz_data['course']->ID;
	} else {
		$course_id = $quiz_data['course'];
	}

	if ( is_object( $quiz_data['lesson'] ) ) {
		$lesson_id = $quiz_data['lesson']->ID;
	} else {
		$lesson_id = $quiz_data['lesson'];
	}

	if ( is_object( $quiz_data['topic'] ) ) {
		$topic_id = $quiz_data['topic']->ID;
	} else {
		$topic_id = $quiz_data['topic'];
	}

	if ( is_object( $quiz_data['quiz'] ) ) {
		$quiz_id = $quiz_data['quiz']->ID;
	} else {
		$quiz_id = $quiz_data['quiz'];
	}

	// If user passes the quiz
	if ( $quiz_data['pass'] == 1 ) {
		learndash_notifications_send_notifications( 'pass_quiz', $current_user->ID, $course_id, $lesson_id, $topic_id, $quiz_id );
	}
}

// add_action( 'learndash_quiz_completed', 'learndash_notifications_pass_quiz', 10, 2 );

/**
 * Send learndash notification email when user fail a quiz
 *
 * @param array  $quiz_data      Data of the quiz taken
 * @param object $current_user   Current user WP object who take the quiz
 */
function learndash_notifications_fail_quiz( $quiz_data, $current_user ) {
	learndash_notifications_set_global_quiz_result( $quiz_data );

	if ( $quiz_data['has_graded'] ) {
		foreach ( $quiz_data['graded'] as $id => $essay ) {
			if ( $essay['status'] == 'not_graded' ) {
				return;
			}
		}
	}

	if ( is_object( $quiz_data['course'] ) ) {
		$course_id = $quiz_data['course']->ID;
	} else {
		$course_id = $quiz_data['course'];
	}

	if ( is_object( $quiz_data['lesson'] ) ) {
		$lesson_id = $quiz_data['lesson']->ID;
	} else {
		$lesson_id = $quiz_data['lesson'];
	}

	if ( is_object( $quiz_data['topic'] ) ) {
		$topic_id = $quiz_data['topic']->ID;
	} else {
		$topic_id = $quiz_data['topic'];
	}

	if ( is_object( $quiz_data['quiz'] ) ) {
		$quiz_id = $quiz_data['quiz']->ID;
	} else {
		$quiz_id = $quiz_data['quiz'];
	}

	// If user fails the quiz
	if ( $quiz_data['pass'] == 0 ) {
		learndash_notifications_send_notifications( 'fail_quiz', $current_user->ID, $course_id, $lesson_id, $topic_id, $quiz_id );
	}
}

// add_action( 'learndash_quiz_completed', 'learndash_notifications_fail_quiz', 10, 2 );

/**
 * Send learndash notification email when user completes a quiz
 *
 * @param array  $quiz_data      Data of the quiz taken
 * @param object $current_user   Current user WP object who take the quiz
 */
function learndash_notifications_complete_quiz( $quiz_data, $current_user ) {
	learndash_notifications_set_global_quiz_result( $quiz_data );

	if ( is_object( $quiz_data['course'] ) ) {
		$course_id = $quiz_data['course']->ID;
	} else {
		$course_id = $quiz_data['course'];
	}

	if ( is_object( $quiz_data['lesson'] ) ) {
		$lesson_id = $quiz_data['lesson']->ID;
	} else {
		$lesson_id = $quiz_data['lesson'];
	}

	if ( is_object( $quiz_data['topic'] ) ) {
		$topic_id = $quiz_data['topic']->ID;
	} else {
		$topic_id = $quiz_data['topic'];
	}

	if ( is_object( $quiz_data['quiz'] ) ) {
		$quiz_id = $quiz_data['quiz']->ID;
	} else {
		$quiz_id = $quiz_data['quiz'];
	}

	learndash_notifications_send_notifications( 'complete_quiz', $current_user->ID, $course_id, $lesson_id, $topic_id, $quiz_id );
}

// add_action( 'learndash_quiz_completed', 'learndash_notifications_complete_quiz', 10, 2 );


/**
 * Send notifications when user submits an essay
 *
 * @param int   $id   Essay ID
 * @param array $args Essay arguments
 * @return void
 */
function learndash_notifications_submit_essay( $id, $args ) {
	$user_id     = $args['post_author'];
	$course_id   = get_post_meta( $id, 'course_id', true );
	$lesson_id   = get_post_meta( $id, 'lesson_id', true );
	$quiz_id     = get_post_meta( $id, 'quiz_id', true );
	$question_id = get_post_meta( $id, 'question_id', true );

	learndash_notifications_send_notifications( 'submit_essay', $user_id, $course_id, $lesson_id, $topic_id = null, $quiz_id, $assignment_id = null, $lesson_access_from = null, $question_id );
}

// add_action( 'learndash_new_essay_submitted', 'learndash_notifications_submit_essay', 10, 2 );


/**
 * Send learndash notification email when user submits a quiz
 *
 * @param array  $quiz_data      Data of the quiz taken
 * @param object $current_user   Current user WP object who take the quiz
 */
function learndash_notifications_submit_quiz( $quiz_data, $current_user ) {
	learndash_notifications_set_global_quiz_result( $quiz_data );

	if ( is_object( $quiz_data['course'] ) ) {
		$course_id = $quiz_data['course']->ID;
	} else {
		$course_id = $quiz_data['course'];
	}

	if ( is_object( $quiz_data['lesson'] ) ) {
		$lesson_id = $quiz_data['lesson']->ID;
	} else {
		$lesson_id = $quiz_data['lesson'];
	}

	if ( is_object( $quiz_data['topic'] ) ) {
		$topic_id = $quiz_data['topic']->ID;
	} else {
		$topic_id = $quiz_data['topic'];
	}

	if ( is_object( $quiz_data['quiz'] ) ) {
		$quiz_id = $quiz_data['quiz']->ID;
	} else {
		$quiz_id = $quiz_data['quiz'];
	}

	learndash_notifications_send_notifications( 'submit_quiz', $current_user->ID, $course_id, $lesson_id, $topic_id, $quiz_id );
}
// add_action( 'learndash_quiz_submitted', 'learndash_notifications_submit_quiz', 10, 2 );

/**
 * Send learndash notification email when essay question is graded
 *
 * @param int    $quiz_id             Quiz ID
 * @param int    $question_id         Question ID
 * @param object $updated_scoring  Essay object
 * @param object $essay            Submitted essay object
 */
function learndash_notifications_essay_graded( $quiz_id, $question_id, $updated_scoring, $essay ) {
	// If essay has been graded
	if ( $essay->post_status == 'graded' ) {
		$user_id      = $essay->post_author;
		$real_quiz_id = learndash_get_quiz_id_by_pro_quiz_id( $quiz_id );
		$course_id    = learndash_get_course_id( $real_quiz_id );
		$lesson_id    = learndash_get_lesson_id( $real_quiz_id );

		learndash_notifications_send_notifications( 'essay_graded', $user_id, $course_id, $lesson_id, $topic_id = null, $real_quiz_id, $assignment_id = null, $lesson_access_from = null, $question_id );

		$users_quiz_data = get_user_meta( $essay->post_author, '_sfwd-quizzes', true );

		foreach ( $users_quiz_data as $quiz_key => $quiz_data ) {
			if ( $quiz_id == $quiz_data['pro_quizid'] ) {
				if ( $quiz_data['has_graded'] ) {
					foreach ( $quiz_data['graded'] as $id => $essay ) {
						if ( $essay['status'] == 'not_graded' ) {
							return;
						}
					}
				}

				if ( $quiz_data['pass'] == 1 ) {
					learndash_notifications_send_notifications( 'pass_quiz', $user_id, $course_id, null, null, $real_quiz_id );
				} elseif ( $quiz_data['pass'] == 0 ) {
					learndash_notifications_send_notifications( 'fail_quiz', $user_id, $course_id, null, null, $real_quiz_id );
				}
			}
		}
	}
}

// add_action( 'learndash_essay_all_quiz_data_updated', 'learndash_notifications_essay_graded', 10, 4 );

/**
 * Send learndash notification email when user upload an assignment
 *
 * @param int   $assignment_id      ID of assignment post object
 * @param array $assignment_meta    Meta data of the assignment
 */
function learndash_notifications_upload_assignment( $assignment_id, $assignment_meta ) {
	$post_type = get_post_type( $assignment_meta['lesson_id'] );

	if ( 'sfwd-lessons' == $post_type ) {
		learndash_notifications_send_notifications( 'upload_assignment', $assignment_meta['user_id'], $assignment_meta['course_id'], $assignment_meta['lesson_id'], null, null, $assignment_id );
	} elseif ( 'sfwd-topic' == $post_type ) {
		learndash_notifications_send_notifications( 'upload_assignment', $assignment_meta['user_id'], $assignment_meta['course_id'], null, $assignment_meta['lesson_id'], null, $assignment_id );
	}
}

// add_action( 'learndash_assignment_uploaded', 'learndash_notifications_upload_assignment', 10, 2 );

/**
 * Send learndash notification email when admin approves an assignment
 *
 * @param int $assignment_id ID of assignment post object
 */
function learndash_notifications_approve_assignment( $assignment_id ) {
	$user_id   = get_post_meta( $assignment_id, 'user_id', true );
	$course_id = get_post_meta( $assignment_id, 'course_id', true );
	$lesson_id = get_post_meta( $assignment_id, 'lesson_id', true );
	$post_type = get_post_type( $lesson_id );

	if ( 'sfwd-lessons' == $post_type ) {
		learndash_notifications_send_notifications( 'approve_assignment', $user_id, $course_id, $lesson_id, null, null, $assignment_id );
	} elseif ( 'sfwd-topic' == $post_type ) {
		$topic_id = $lesson_id;
		learndash_notifications_send_notifications( 'approve_assignment', $user_id, $course_id, null, $topic_id, null, $assignment_id );
	}
}

// add_action( 'learndash_assignment_approved', 'learndash_notifications_approve_assignment', 10, 1 );

/**********************
 * ** CRON FUNCTIONS ***
 **********************/

/**
 * Send learndash notification email when user hasn't logged in for X days
 */
function learndash_notifications_not_logged_in() {
	// Fired in cron.php
	$notifications = learndash_notifications_get_notifications( 'not_logged_in' );

	foreach ( $notifications as $n ) {
		$n_days = get_post_meta( $n->ID, '_ld_notifications_not_logged_in_days', true );

		if ( ! ( $n_days > 0 ) ) {
			continue;
		}

		$course_id  = get_post_meta( $n->ID, '_ld_notifications_course_id', true );
		$recipients = learndash_notifications_get_recipients( $n->ID );

		$roles = [];
		foreach ( $recipients as $r ) {
			switch ( $r ) {
				case 'user':
					// Need user.php because sometimes the function triggers
					// undefined function error
					include_once ABSPATH . 'wp-admin/includes/user.php';
					$wp_roles = get_editable_roles();
					unset( $wp_roles['administrator'] );
					unset( $wp_roles['group_leader'] );

					foreach ( $wp_roles as $key => $role ) {
						$roles[] = $key;
					}
					break;

				case 'group_leader':
					$roles[] = 'group_leader';
					break;

				case 'admin':
					$roles[] = 'administrator';
					break;
			}
		}

		$users = get_users(
			[
				'role__in' => $roles,
			]
		);

		foreach ( $users as $u ) {
			$last_login = (int) get_user_meta( $u->ID, '_ld_notifications_last_login', true );

			if ( empty( $last_login ) || ! isset( $last_login ) ) {
				continue;
			}

			$courses = ld_get_mycourses( $u->ID );

			if ( isset( $course_id ) && is_numeric( $course_id ) && $course_id > 0 ) {
				if ( ! in_array( $course_id, $courses ) ) {
					continue;
				}

				// Exit if user already has completed the course
				if ( learndash_course_completed( $u->ID, $course_id ) || ld_course_access_expired( $course_id, $u->ID ) ) {
					continue;
				}

				if ( ! empty( $last_login ) && date( 'Y-m-d H' ) == date( 'Y-m-d H', strtotime( '+' . $n_days . ' days', $last_login ) ) ) {
					learndash_notifications_send_notification( $n, $u->ID, $course_id );
				}
			} else {
				if ( apply_filters( 'learndash_notifications_disable_not_logged_in_notification_for_all_courses', false, $n, $u, $courses ) ) {
					if ( ! empty( $last_login ) && date( 'Y-m-d H' ) == date( 'Y-m-d H', strtotime( '+' . $n_days . ' days', $last_login ) ) ) {
						learndash_notifications_send_notification( $n, $u->ID );
						continue;
					}
				}

				foreach ( $courses as $c_id ) {
					// Exit if user already has completed the course
					if ( learndash_course_completed( $u->ID, $c_id ) || ld_course_access_expired( $c_id, $u->ID ) ) {
						continue;
					}

					if ( ! empty( $last_login ) && date( 'Y-m-d H' ) == date( 'Y-m-d H', strtotime( '+' . $n_days . ' days', $last_login ) ) ) {
						learndash_notifications_send_notification( $n, $u->ID, $c_id );
					}
				}
			}
		}
	}
}

/**
 * Set user last login time
 *
 * @param string $user_login User's username to log in
 * @param object $user       WP_User object
 */
function learndash_notifications_set_last_login() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$user_id = get_current_user_id();

	update_user_meta( $user_id, '_ld_notifications_last_login', time() );
}

// add_action( 'init', 'learndash_notifications_set_last_login', 10, 2 );

/**
 * Send learndash notification email when user's course is about to expire in X days
 */
function learndash_notifications_course_expires() {
	// Fired in cron.php
	// Get all courses
	$courses = learndash_notifications_get_all_courses();

	// Get all notifications
	$notifications = learndash_notifications_get_notifications( 'course_expires' );

	// Foreach courses
	foreach ( $courses as $c ) {
		$c_meta = get_post_meta( $c->ID, '_sfwd-courses', true );
		$c_meta = maybe_unserialize( $c_meta );

		// If course doesn't has expiration setting, continue
		if ( ( ! isset( $c_meta['sfwd-courses_expire_access'] ) || ( isset( $c_meta['sfwd-courses_expire_access'] ) && $c_meta['sfwd-courses_expire_access'] != 'on' ) )
			||
			( ! isset( $c_meta['sfwd-courses_expire_access_days'] ) || ( isset( $c_meta['sfwd-courses_expire_access_days'] ) && $c_meta['sfwd-courses_expire_access_days'] == 0 ) ) ) {
			continue;
		}

		// Course access list
		$c_access_list = learndash_get_users_for_course( $c->ID );
		$c_access_list = ! empty( $c_access_list ) && is_a( $c_access_list, 'WP_User_Query' ) ? $c_access_list->get_results() : [];

		// If course has no access list, continue
		if ( empty( $c_access_list ) ) {
			continue;
		}

		$c_access_days = (int) $c_meta['sfwd-courses_expire_access_days'];

		// Foreach users who have access
		foreach ( $c_access_list as $u_id ) {
			$allow = apply_filters( 'learndash_notifications_send_course_expires_notification_for_completed_users', true, $u_id, $c );

			if ( ! $allow && learndash_course_completed( $u_id, $c->ID ) ) {
				continue;
			}

			$access_from = (int) get_user_meta( $u_id, 'course_' . $c->ID . '_access_from', true );

			// Foreach notifications
			foreach ( $notifications as $n ) {
				$n_days = get_post_meta( $n->ID, '_ld_notifications_course_expires_days', true );

				// If users' course access is equal to setting, send notifications
				if ( ! empty( $access_from ) && date( 'Y-m-d H' ) == date( 'Y-m-d H', strtotime( '-' . $n_days . ' days', strtotime( '+' . $c_access_days . ' days', $access_from ) ) ) ) {
					learndash_notifications_send_notification( $n, $u_id, $c->ID );
				}
			}
		}
	}
}

/**
 * Send learndash notification email X days after course expires
 */
function learndash_notifications_course_expires_after() {
	// Fired in cron.php
	// Get all courses
	$courses = learndash_notifications_get_all_courses();

	// Get all notifications
	$notifications = learndash_notifications_get_notifications( 'course_expires_after' );

	// Foreach courses
	foreach ( $courses as $c ) {
		$c_meta = get_post_meta( $c->ID, '_sfwd-courses', true );
		$c_meta = maybe_unserialize( $c_meta );

		// If course doesn't has expiration setting, continue
		if ( ( ! isset( $c_meta['sfwd-courses_expire_access'] ) || ( isset( $c_meta['sfwd-courses_expire_access'] ) && $c_meta['sfwd-courses_expire_access'] != 'on' ) )
			||
			( ! isset( $c_meta['sfwd-courses_expire_access_days'] ) || ( isset( $c_meta['sfwd-courses_expire_access_days'] ) && $c_meta['sfwd-courses_expire_access_days'] == 0 ) ) ) {
			continue;
		}

		// Course access list
		$c_access_list = learndash_get_users_for_course( $c->ID );
		$c_access_list = ! empty( $c_access_list ) && is_a( $c_access_list, 'WP_User_Query' ) ? $c_access_list->get_results() : [];

		// If course has no access list, continue
		if ( empty( $c_access_list ) ) {
			continue;
		}

		$c_access_days = (int) $c_meta['sfwd-courses_expire_access_days'];

		// Foreach users who have access
		foreach ( $c_access_list as $u_id ) {
			$access_from = (int) get_user_meta( $u_id, 'course_' . $c->ID . '_access_from', true );

			if ( ! empty( $access_from ) ) {
				// Foreach notifications
				foreach ( $notifications as $n ) {
					$n_days = get_post_meta( $n->ID, '_ld_notifications_course_expires_after_days', true );

					// If users' course access is equal to setting, send notifications
					if ( ! empty( $access_from ) && date( 'Y-m-d H' ) == date( 'Y-m-d H', strtotime( '+' . $n_days . ' days', strtotime( '+' . $c_access_days . ' days', $access_from ) ) ) ) {
						learndash_notifications_send_notification( $n, $u_id, $c->ID );
					}
				}
			}
		}
	}
}

function learndash_notifications_send_delayed_emails() {
	global $wpdb;
	$date            = date( 'Y-m-d H', time() );
	$timestamp       = time();
	$timestamp_1hour = strtotime( '-1 hour' );

	$emails = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}ld_notifications_delayed_emails WHERE `sent_on` <= {$timestamp} AND `sent_on` >= {$timestamp_1hour}",
		ARRAY_A
	);

	foreach ( $emails as $e ) {
		$sent_on = date( 'Y-m-d H', $e['sent_on'] );

		if ( $sent_on != $date || time() < $e['sent_on'] ) {
			continue;
		}

		$e['shortcode_data'] = unserialize( $e['shortcode_data'] );

		global $ld_notifications_shortcode_data;
		$ld_notifications_shortcode_data = [
			'user_id'         => ! empty( $e['shortcode_data']['user_id'] ) ? $e['shortcode_data']['user_id'] : '',
			'course_id'       => ! empty( $e['shortcode_data']['course_id'] ) ? $e['shortcode_data']['course_id'] : '',
			'lesson_id'       => ! empty( $e['shortcode_data']['lesson_id'] ) ? $e['shortcode_data']['lesson_id'] : '',
			'topic_id'        => ! empty( $e['shortcode_data']['topic_id'] ) ? $e['shortcode_data']['topic_id'] : '',
			'assignment_id'   => ! empty( $e['shortcode_data']['assignment_id'] ) ? $e['shortcode_data']['assignment_id'] : '',
			'quiz_id'         => ! empty( $e['shortcode_data']['quiz_id'] ) ? $e['shortcode_data']['quiz_id'] : '',
			'question_id'     => ! empty( $e['shortcode_data']['question_id'] ) ? $e['shortcode_data']['question_id'] : '',
			'notification_id' => ! empty( $e['shortcode_data']['notification_id'] ) ? $e['shortcode_data']['notification_id'] : '',
			'group_id'        => ! empty( $e['shortcode_data']['group_id'] ) ? $e['shortcode_data']['group_id'] : '',
			'quiz_result'     => ! empty( $e['shortcode_data']['quiz_result'] ) ? $e['shortcode_data']['quiz_result'] : '',
		];
		$shortcode_data                  = $ld_notifications_shortcode_data;

		$e['recipient'] = unserialize( $e['recipient'] );
		$bcc            = isset( $e['bcc'] ) ? unserialize( $e['bcc'] ) : [];

		/**
		 * Filter hook to allow admin to decide whether to send notification or not
		 *
		 * @param bool                  True to send|false to disable
		 * @param array $shortcode_data Data for this notification
		 * @return bool                 True to send|false to disable
		 */
		if ( apply_filters( 'learndash_notifications_send_notification', true, $shortcode_data ) ) {
			$send = learndash_notifications_send_email( $e['recipient'], $e['title'], $e['message'], $bcc, $shortcode_data['notification_id'] );

			// Delete record after delivery is successful
			if ( $send === true ) {
				do_action( 'learndash_notifications_after_send_delayed_email', $shortcode_data );

				$wpdb->delete(
					"{$wpdb->prefix}ld_notifications_delayed_emails",
					[
						'id' => $e['id'],
					],
					[
						'%d',
					]
				);
			}
		}
	}
}

/**
 * Resend delayed emails that were missed during previous cron triggers
 *
 * This function runs in hourly cron trigger.
 *
 * @since 5.2.0
 * @return void
 */
function learndash_notifications_resend_missed_delayed_emails() {
	global $wpdb;
	$timestamp = strtotime( '-1 hour' );

	$emails = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}ld_notifications_delayed_emails WHERE `sent_on` <= {$timestamp}",
		ARRAY_A
	);

	foreach ( $emails as $e ) {
		$sent_on = date( 'Y-m-d H', $e['sent_on'] );

		$e['shortcode_data'] = unserialize( $e['shortcode_data'] );

		global $ld_notifications_shortcode_data;
		$ld_notifications_shortcode_data = [
			'user_id'         => ! empty( $e['shortcode_data']['user_id'] ) ? $e['shortcode_data']['user_id'] : '',
			'course_id'       => ! empty( $e['shortcode_data']['course_id'] ) ? $e['shortcode_data']['course_id'] : '',
			'lesson_id'       => ! empty( $e['shortcode_data']['lesson_id'] ) ? $e['shortcode_data']['lesson_id'] : '',
			'topic_id'        => ! empty( $e['shortcode_data']['topic_id'] ) ? $e['shortcode_data']['topic_id'] : '',
			'assignment_id'   => ! empty( $e['shortcode_data']['assignment_id'] ) ? $e['shortcode_data']['assignment_id'] : '',
			'quiz_id'         => ! empty( $e['shortcode_data']['quiz_id'] ) ? $e['shortcode_data']['quiz_id'] : '',
			'question_id'     => ! empty( $e['shortcode_data']['question_id'] ) ? $e['shortcode_data']['question_id'] : '',
			'notification_id' => ! empty( $e['shortcode_data']['notification_id'] ) ? $e['shortcode_data']['notification_id'] : '',
			'group_id'        => ! empty( $e['shortcode_data']['group_id'] ) ? $e['shortcode_data']['group_id'] : '',
			'quiz_result'     => ! empty( $e['shortcode_data']['quiz_result'] ) ? $e['shortcode_data']['quiz_result'] : '',
		];
		$shortcode_data                  = $ld_notifications_shortcode_data;

		$e['recipient'] = unserialize( $e['recipient'] );
		$bcc            = isset( $e['bcc'] ) ? unserialize( $e['bcc'] ) : [];

		/**
		 * Filter hook to allow admin to decide whether to send notification or not
		 *
		 * @param bool                  True to send|false to disable
		 * @param array $shortcode_data Data for this notification
		 * @return bool                 True to send|false to disable
		 */
		if ( apply_filters( 'learndash_notifications_send_notification', true, $shortcode_data ) ) {
			$send = learndash_notifications_send_email( $e['recipient'], $e['title'], $e['message'], $bcc, $shortcode_data['notification_id'] );

			// Delete record after delivery is successful
			if ( $send === true ) {
				do_action( 'learndash_notifications_after_send_delayed_email', $shortcode_data );

				$wpdb->delete(
					"{$wpdb->prefix}ld_notifications_delayed_emails",
					[
						'id' => $e['id'],
					],
					[
						'%d',
					]
				);
			}
		}
	}
}

/**
 * Send course enrollment notification via cron
 *
 * @return void
 */
function learndash_notifications_send_enroll_course_via_group_queue() {
	$queue = get_option( '_ld_notifications_enroll_group_queue', [] );

	foreach ( $queue as $group_id => $data ) {
		foreach ( $data['users'] as $user_id ) {
			foreach ( $data['courses'] as $course_id ) {
				// Exit if notification already sent
				$sent = get_user_meta( $user_id, 'ld_sent_notification_enroll_course_' . $course_id, true );
				if ( $sent == 1 ) {
					continue;
				}

				learndash_notifications_send_notifications( 'enroll_course', $user_id, $course_id );

				add_user_meta( $user_id, 'ld_sent_notification_enroll_course_' . $course_id, 1, true );
			}
		}

		unset( $queue[ $group_id ] );
	}

	update_option( '_ld_notifications_enroll_group_queue', $queue );
}

/**
 * Send notification when a comment is left on an assignment
 *
 * @param int   $id       Comment ID
 * @param mixed $approved 1 = approved|0 = not approved|spam = SPAM
 * @param array $data     Comment data
 */
function learndash_notifications_assignment_essay_comment_left( $id, $approved, $data ) {
	if ( 'spam' == $approved ) {
		return;
	}

	$post = get_post( $data['comment_post_ID'] );
	if ( $post->post_type != 'sfwd-assignment' && $post->post_type != 'sfwd-essays' ) {
		return;
	}

	$course_id = learndash_get_course_id( $data['comment_post_ID'] );

	if ( $post->post_type == 'sfwd-assignment' ) {
		$title   = __( 'Assignment', 'learndash' );
		$trigger = 'assignment_comment';
	} else {
		$title   = __( 'Essay', 'learndash' );
		$trigger = 'essay_comment';
	}

	$label = strtolower( $title );

	$comment               = get_comment( $id );
	$user                  = get_user_by( 'ID', $data['user_id'] );
	$blogname              = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	$comment_author_domain = @gethostbyaddr( $comment->comment_author_IP );
	$comment_content       = wp_specialchars_decode( $comment->comment_content );
	$wp_email              = 'wordpress@' . preg_replace( '#^www\.#', '', strtolower( $_SERVER['SERVER_NAME'] ) );

	/* translators: 1: post type label, 2: post title */
	$notify_message = sprintf( __( 'New comment on %1$s "%2$s"', 'learndash' ), $label, $post->post_title ) . "\r\n";
	/* translators: 1: comment author, 2: author IP, 3: author domain */
	$notify_message .= sprintf( __( 'Author: %1$s (IP: %2$s, %3$s)', 'learndash' ), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
	/* translators: %s: comment author email */
	$notify_message .= sprintf( __( 'Email: %s', 'learndash' ), $comment->comment_author_email ) . "\r\n";
	/* translators: %s: comment content */
	$notify_message .= sprintf( __( 'Comment: %s', 'learndash' ), "\r\n" . $comment_content ) . "\r\n\r\n";
	/* translators: %s: post type label */
	$notify_message .= sprintf( __( 'You can see all comments on this %s here:', 'learndash' ), $label ) . "\r\n";
	$notify_message .= get_permalink( $comment->comment_post_ID ) . "#comments\r\n\r\n";
	/* translators: %s: comment permalink */
	$notify_message .= sprintf( __( 'Permalink: %s', 'learndash' ), get_comment_link( $comment ) ) . "\r\n";

	/* translators: 1: title, 2: post title */
	$subject = sprintf( __( '%1$s Comment: "%2$s"', 'learndash' ), $title, $post->post_title );

	if ( '' == $comment->comment_author ) {
		$from = "From: \"$blogname\" <$wp_email>";
		if ( '' != $comment->comment_author_email ) {
			$reply_to = "Reply-To: $comment->comment_author_email";
		}
	} else {
		$from = "From: \"$comment->comment_author\" <$wp_email>";
		if ( '' != $comment->comment_author_email ) {
			$reply_to = "Reply-To: \"$comment->comment_author_email\" <$comment->comment_author_email>";
		}
	}

	$headers = "$from\n"
		. 'Content-Type: text/plain; charset="' . get_option( 'blog_charset' ) . "\";\n";

	if ( isset( $reply_to ) ) {
		$headers .= $reply_to . ";\n";
	}

	// Add custom header to indicated notification trigger
	$headers .= "X-LearnDash-Notification-Trigger: {$trigger};\n";

	if ( in_array( 'administrator', $user->roles ) || ( in_array( 'group_leader', $user->roles ) && learndash_is_group_leader_of_user( $user->ID, $post->post_author ) ) ) {
		$recipients = [ 'user' ];
	} else {
		$recipients = [ 'group_leader' ];
	}

	$recipients = apply_filters( 'learndash_notifications_comment_notification_recipients', $recipients );
	$emails     = learndash_notifications_get_recipients_emails( $recipients, $post->post_author, $course_id );

	if ( ! empty( $emails ) ) {
		wp_mail( $emails, $subject, $notify_message, $headers );
	}
}

add_action( 'comment_post', 'learndash_notifications_assignment_essay_comment_left', 10, 3 );

/**
 * Disable default assignment/essay comment notification
 *
 * @param bool $notify     Whether send comment notification or not
 * @param int  $comment_id Comment ID
 * @return bool               Modified $notify
 */
function learndash_notifications_notify_post_author( $notify, $comment_id ) {
	$comment = get_comment( $comment_id );
	$post    = get_post( $comment->comment_post_ID );

	if ( 'sfwd-essays' == $post->post_type || 'sfwd-assignment' == $post->post_type ) {
		$notify = false;
	}

	return $notify;
}

add_filter( 'notify_post_author', 'learndash_notifications_notify_post_author', 10, 2 );

/************************************
 * *** HELPERS AND SEND FUNCTIONS ****
 ************************************/

/**
 * Decide to send notifications to user or not based on user notification subscription settings
 *
 * @param bool  $send True to send|false otherwise
 * @param array $data Notifications data
 * @param int   $notification_id ID of LD Notification WP_Post
 * @return bool         True to send|false otherwise
 */
function learndash_notifications_apply_notifications_subscription( $send, $email, $notification_id ) {
	$user = get_user_by( 'email', $email );

	if ( $user ) {
		$subscription = get_user_meta( $user->ID, 'learndash_notifications_subscription', true );
		$trigger      = get_post_meta( $notification_id, '_ld_notifications_trigger', true );

		if ( ! isset( $subscription[ $trigger ] ) || $subscription[ $trigger ] == 1 ) {
			return true;
		} else {
			return false;
		}
	}

	return $send;
}

add_filter( 'learndash_notifications_send_email', 'learndash_notifications_apply_notifications_subscription', 9999, 3 );

/**
 * Returns the email triggers a notification can be sent on, keyed by trigger.
 *
 * @since 5.2.0
 *
 * @return array<string,string> Trigger labels keyed by trigger.
 */
function learndash_notifications_get_triggers() {
	$triggers = [
		'enroll_group'         => __( 'User enrolls into a group', 'learndash' ),
		'enroll_course'        => __( 'User enrolls into a course', 'learndash' ),
		'complete_course'      => __( 'User completes a course', 'learndash' ),
		'complete_lesson'      => __( 'User completes a lesson', 'learndash' ),
		'lesson_available'     => __( 'A scheduled lesson is available to user', 'learndash' ),
		'complete_topic'       => __( 'User completes a topic', 'learndash' ),
		'pass_quiz'            => __( 'User passes a quiz', 'learndash' ),
		'fail_quiz'            => __( 'User fails a quiz', 'learndash' ),
		'submit_quiz'          => __( 'User submits a quiz', 'learndash' ),
		'complete_quiz'        => __( 'User completes a quiz', 'learndash' ),
		'submit_essay'         => __( 'Essay has just been submitted', 'learndash' ),
		'essay_graded'         => __( 'Essay question has been put into graded status', 'learndash' ),
		'upload_assignment'    => __( 'An assignment is uploaded', 'learndash' ),
		'approve_assignment'   => __( 'An assignment is approved', 'learndash' ),
		'not_logged_in'        => __( 'User hasn\'t logged in for "X" days', 'learndash' ),
		'course_expires'       => __( '"X" days before course expires', 'learndash' ),
		'course_expires_after' => __( '"X" days after course expires', 'learndash' ),
	];

	return apply_filters( 'learndash_notifications_triggers', $triggers );
}

/**
 * Get conditions key and label pair.
 *
 * @since 5.2.0
 *
 * @return array
 */
function learndash_notifications_get_conditions(): array {
	$conditions = learndash_notifications_get_triggers();

	$conditions = array_merge(
		$conditions,
		[
			'incomplete_quiz' => __( 'User has not completed a quiz.', 'learndash' ),
		]
	);

	foreach ( $conditions as $key => $label ) {
		switch ( $key ) {
			case 'enroll_group':
				$label = __( 'User is enrolled to a group', 'learndash' );
				break;

			case 'enroll_course':
				$label = __( 'User is enrolled to a course', 'learndash' );
				break;

			case 'complete_course':
				$label = __( 'User has completed a course', 'learndash' );
				break;

			case 'complete_lesson':
				$label = __( 'User has completed a lesson', 'learndash' );
				break;

			case 'lesson_available':
				$label = null;
				break;

			case 'complete_topic':
				$label = __( 'User has completed a topic', 'learndash' );
				break;

			case 'pass_quiz':
				$label = null;
				break;

			case 'fail_quiz':
				$label = null;
				break;

			case 'submit_quiz':
				$label = __( 'User has submitted a quiz', 'learndash' );
				break;

			case 'complete_quiz':
				$label = __( 'User has completed a quiz', 'learndash' );
				break;

			case 'upload_assignment':
				$label = __( 'User has uploaded an assignment.', 'learndash' );
				break;

			case 'approve_assignment':
				$label = __( 'User\'s assignment has been approved.', 'learndash' );
				break;

			case 'not_logged_in':
				$label = null;
				break;

			case 'course_expires':
				$label = null;
				break;

			case 'course_expires_after':
				$label = null;
				break;
		}

		if ( $label ) {
			$conditions[ $key ] = $label;
		} else {
			unset( $conditions[ $key ] );
		}
	}

	return apply_filters( 'learndash_notifications_conditions', $conditions );
}

/**
 * Get trigger and condition objects.
 *
 * @since 5.2.0
 *
 * @return array
 */
function learndash_notifications_get_object_fields(): array {
	$group_label  = LearnDash_Custom_Label::get_label( 'group' );
	$course_label = LearnDash_Custom_Label::get_label( 'course' );
	$lesson_label = LearnDash_Custom_Label::get_label( 'lesson' );
	$topic_label  = LearnDash_Custom_Label::get_label( 'topic' );
	$quiz_label   = LearnDash_Custom_Label::get_label( 'quiz' );

	return apply_filters(
		'learndash_notifications_object_fields',
		[
			'group_id'  => [
				'type'            => 'dropdown',
				'title'           => $group_label,
				// translators: %s: Group label.
				'help_text'       => sprintf( _x( '%s that the notification is assigned to.', 'Group label', 'learndash' ), $group_label ),
				'hide'            => 1,
				'disabled'        => 0,
				'parent'          => [ 'enroll_group' ],
				'value'           => [],
				'dynamic_options' => 1,
				'trigger_object'  => 1,
				'multiple'        => 1,
			],
			'course_id' => [
				'type'            => 'dropdown',
				'title'           => $course_label,
				// translators: %s: Course label.
				'help_text'       => sprintf( _x( '%s that the notification is assigned to.', 'Course label', 'learndash' ), $course_label ),
				'hide'            => 1,
				'disabled'        => 0,
				'class'           => 'parent_field',
				'parent'          => [
					'enroll_course',
					'complete_course',
					'course_expires',
					'course_expires_after',
					'not_logged_in',
					'complete_lesson',
					'lesson_available',
					'complete_topic',
					'submit_quiz',
					'complete_quiz',
					'incomplete_quiz',
					'pass_quiz',
					'fail_quiz',
					'upload_assignment',
					'approve_assignment',
				],
				'value'           => [],
				'dynamic_options' => 1,
				'trigger_object'  => 1,
				'multiple'        => 1,
			],
			'lesson_id' => [
				'type'            => 'dropdown',
				'title'           => $lesson_label,
				// translators: %s: Lesson label.
				'help_text'       => sprintf( _x( '%s that the notification is assigned to.', 'Lesson label', 'learndash' ), $lesson_label ),
				'hide'            => 1,
				'disabled'        => 0,
				'class'           => 'parent_field child_field',
				'parent'          => [
					'complete_lesson',
					'lesson_available',
					'complete_topic',
					'submit_quiz',
					'complete_quiz',
					'incomplete_quiz',
					'pass_quiz',
					'fail_quiz',
					'upload_assignment',
					'approve_assignment',
				],
				'value'           => [],
				'dynamic_options' => 1,
				'disabled_child'  => 1,
				'trigger_object'  => 1,
				'multiple'        => 1,
			],
			'topic_id'  => [
				'type'            => 'dropdown',
				'title'           => $topic_label,
				// translators: %s: Topic label.
				'help_text'       => sprintf( _x( '%s that the notification is assigned to.', 'Topic label', 'learndash' ), $topic_label ),
				'hide'            => 1,
				'disabled'        => 0,
				'class'           => 'parent_field child_field',
				'parent'          => [
					'complete_topic',
					'submit_quiz',
					'complete_quiz',
					'incomplete_quiz',
					'pass_quiz',
					'fail_quiz',
					'upload_assignment',
					'approve_assignment',
				],
				'value'           => [],
				'dynamic_options' => 1,
				'disabled_child'  => 1,
				'trigger_object'  => 1,
				'multiple'        => 1,
			],
			'quiz_id'   => [
				'type'            => 'dropdown',
				'title'           => $quiz_label,
				// translators: %s: Quiz label.
				'help_text'       => sprintf( _x( '%s that the notification is assigned to.', 'Quiz label', 'learndash' ), $quiz_label ),
				'hide'            => 1,
				'disabled'        => 0,
				'class'           => 'child_field',
				'parent'          => [
					'pass_quiz',
					'fail_quiz',
					'submit_quiz',
					'complete_quiz',
					'incomplete_quiz',
				],
				'value'           => [],
				'dynamic_options' => 1,
				'disabled_child'  => 1,
				'trigger_object'  => 1,
				'multiple'        => 1,
			],
		]
	);
}

/**
 * Returns the recipients a notification can be sent to, keyed by recipient.
 *
 * @since 5.2.0
 *
 * @return array<string,string> Recipient labels keyed by recipient.
 */
function learndash_notifications_get_default_recipients() {
	$recipients = [
		'user'         => __( 'User', 'learndash' ),
		'group_leader' => __( 'Group Leader', 'learndash' ),
		'admin'        => __( 'Admin', 'learndash' ),
	];

	return apply_filters( 'learndash_notifications_recipients', $recipients );
}

/**
 * Get notifications
 *
 * @param string $notification_type Notification trigger type
 * @return Array                     Notifications posts object
 */
function learndash_notifications_get_notifications( $notification_type ) {
	$args = [
		'meta_key'       => '_ld_notifications_trigger',
		'meta_value'     => $notification_type,
		'post_type'      => 'ld-notification',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	];

	$notifications = get_posts( $args );

	return apply_filters( 'learndash_notifications_posts', $notifications, $notification_type );
}

/**
 * Get recipient from notification
 *
 * @param int $notification_id Post ID of a notification
 * @return array                     List of recipient (user, group_leader, administrator)
 */
function learndash_notifications_get_recipients( $notification_id ) {
	$recipients = get_post_meta( $notification_id, '_ld_notifications_recipient', true );
	$recipients = maybe_unserialize( $recipients );
	$recipients = ! empty( $recipients ) ? $recipients : [];

	return apply_filters( 'learndash_notification_recipients', $recipients, $notification_id );
}

/**
 * Get group leaders of a group
 *
 * @param int $group_id LD group ID
 * @return array
 */
function learndash_notifications_get_group_leaders( $group_id ) {
	$group_leaders = wp_cache_get( 'group_leaders_' . $group_id, 'learndash_notifications' );

	if ( false === $group_leaders ) {
		global $wpdb;
		$query = "SELECT user_id FROM {$wpdb->prefix}usermeta WHERE meta_key = 'learndash_group_leaders_%d'";

		$group_leaders = $wpdb->get_col( $wpdb->prepare( $query, $group_id ) );

		wp_cache_set( 'group_leaders_' . $group_id, $group_leaders, 'learndash_notifications', HOUR_IN_SECONDS );
	}

	return isset( $group_leaders ) ? $group_leaders : [];
}

/**
 * Get recipients emails
 *
 * @param array  $recipients List of recipients
 * @param int    $user_id    ID of a user. Default is null
 * @param int    $course_id  ID of a course that triggers notification
 * @param object $notification Notification WP_Post object that triggers notification
 * @return array             List of email addresses
 */
function learndash_notifications_get_recipients_emails( $recipients, $user_id = null, $course_id = null, $notification = null, $group_id = null ) {
	$emails = [];
	foreach ( $recipients as $r ) {
		switch ( $r ) {
			case 'user':
				$user = get_user_by( 'ID', $user_id );

				if ( false !== $user ) {
					$emails[] = $user->user_email;
				}

				break;

			case 'group_leader':
				$trigger = '';
				if ( $notification != null ) {
					$trigger = get_post_meta( $notification->ID, '_ld_notifications_trigger', true );
				}

				if ( isset( $group_id ) && is_numeric( $group_id ) ) {
					$group_leaders = learndash_notifications_get_group_leaders( $group_id );

					foreach ( $group_leaders as $group_leader_id ) {
						$group_leader = get_user_by( 'ID', $group_leader_id );
						$emails[]     = $group_leader->user_email;
					}
				} else {
					$groups = learndash_get_users_group_ids( $user_id, true );
					if ( is_array( $groups ) ) {
						foreach ( $groups as $group_id ) {
							$group_leaders = [];

							// Enroll_group trigger doesn't have course id to check against
							if ( 'enroll_group' == $trigger ) {
								$group_leaders = learndash_notifications_get_group_leaders( $group_id );
							} else {
								// Get course ids
								$courses_ids = learndash_group_enrolled_courses( $group_id, true );

								// If course id match
								if ( in_array( $course_id, $courses_ids ) ) {
									// Get group leaders
									$group_leaders = learndash_notifications_get_group_leaders( $group_id );
								}
							}

							// Loop group leaders
							foreach ( $group_leaders as $group_leader_id ) {
								$group_leader = get_user_by( 'ID', $group_leader_id );
								$emails[]     = $group_leader->user_email;
							}
						}
					}
				}

				break;

			case 'admin':
				$args  = [
					'role' => 'administrator',
				];
				$users = get_users( $args );

				foreach ( $users as $u ) {
					$emails[] = $u->user_email;
				}

				break;
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
	return apply_filters( 'learndash_notification_recipients_emails', $emails, $recipients, $user_id, $course_id, $group_id );
}

function learndash_notifications_get_bcc( $notification_id ) {
	$bcc = get_post_meta( $notification_id, '_ld_notifications_bcc', true );
	$bcc = array_map( 'trim', explode( ',', $bcc ) );
	return apply_filters( 'learndash_notification_bcc', $bcc, $notification_id );
}

/**
 * Set global $ld_notifications_quiz_data
 *
 * @param array $quiz_data Quiz data
 * @return void
 */
function learndash_notifications_set_global_quiz_result( $quiz_data ) {
	global $ld_notifications_quiz_result;
	$ld_notifications_quiz_result = [
		'cats'       => @$_POST['results']['comp']['cats'],
		'pro_quizid' => $quiz_data['pro_quizid'],
	];
}

/**
 * Send all learndash notifications
 *
 * @param string $notification_type Notification type/trigger set for the notification
 * @param int    $user_id           ID of a user
 * @param int    $course_id         ID of a course
 * @param int    $lesson_id         ID of a lesson
 * @param int    $topic_id          ID of a topic
 * @param int    $quiz_id           ID of a quiz
 * @param int    $assignment_id     ID of a assignment
 * @param int    $lesson_access_from Timestamp (only for 'lesson_available' type)
 */
function learndash_notifications_send_notifications( $notification_type = '', $user_id = null, $course_id = null, $lesson_id = null, $topic_id = null, $quiz_id = null, $assignment_id = null, $lesson_access_from = null, $question_id = null, $group_id = null, $update_where = [] ) {
	// Get notifications with enroll course type
	$notifications = learndash_notifications_get_notifications( $notification_type );

	foreach ( $notifications as $n ) {
		learndash_notifications_send_notification( $n, $user_id, $course_id, $lesson_id, $topic_id, $quiz_id, $assignment_id, $lesson_access_from, $question_id, $group_id, $update_where );
	}
}

/**
 * Send one learndash notification
 *
 * @param object $notification      Notification WP Post object
 * @param int    $user_id           ID of a user
 * @param int    $course_id         ID of a course
 * @param int    $lesson_id         ID of a lesson
 * @param int    $topic_id          ID of a topic
 * @param int    $quiz_id           ID of a quiz
 * @param int    $assignment_id     ID of a assignment
 * @param int    $lesson_access_from Timestamp (only for 'lesson_available' type)
 */
function learndash_notifications_send_notification( $notification, $user_id = null, $course_id = null, $lesson_id = null, $topic_id = null, $quiz_id = null, $assignment_id = null, $lesson_access_from = null, $question_id = null, $group_id = null, $update_where = [] ) {
	$n = $notification;

	// Exit if group ID setting doesn't match
	$n_group_id = get_post_meta( $n->ID, '_ld_notifications_group_id', true );
	if ( isset( $group_id ) && $group_id != $n_group_id && $n_group_id != 'all' && ! empty( $n_group_id ) && is_numeric( $n_group_id ) ) {
		return;
	}
	// Exit if course ID setting doesn't match
	$n_course_id = get_post_meta( $n->ID, '_ld_notifications_course_id', true );
	if ( isset( $course_id ) && $course_id != $n_course_id && $n_course_id != 'all' && ! empty( $n_course_id ) && is_numeric( $n_course_id ) ) {
		return;
	}
	// Exit if lesson ID setting doesn't match
	$n_lesson_id = get_post_meta( $n->ID, '_ld_notifications_lesson_id', true );
	if ( isset( $lesson_id ) && $lesson_id != $n_lesson_id && $n_lesson_id != 'all' && ! empty( $n_lesson_id ) && is_numeric( $n_lesson_id ) ) {
		return;
	}
	// Exit if topic ID setting doesn't match
	$n_topic_id = get_post_meta( $n->ID, '_ld_notifications_topic_id', true );
	if ( isset( $topic_id ) && $topic_id != $n_topic_id && $n_topic_id != 'all' && ! empty( $n_topic_id ) && is_numeric( $n_topic_id ) ) {
		return;
	}
	// Exit if quiz ID setting doesn't match
	$n_quiz_id = get_post_meta( $n->ID, '_ld_notifications_quiz_id', true );
	if ( isset( $quiz_id ) && $quiz_id != $n_quiz_id && $n_quiz_id != 'all' && ! empty( $n_quiz_id ) && is_numeric( $n_quiz_id ) ) {
		return;
	}

	$trigger = get_post_meta( $n->ID, '_ld_notifications_trigger', true );

	// Specific logic for assignment notification
	if ( in_array( $trigger, [ 'approve_assignment', 'upload_assignment' ] ) ) {
		if ( ! empty( $n_lesson_id ) && empty( $lesson_id ) && empty( $n_topic_id ) && ! empty( $topic_id ) ) {
			return;
		}
	}

	// Get recipient
	$recipients = learndash_notifications_get_recipients( $n->ID );

	// Get recipients emails
	$emails = learndash_notifications_get_recipients_emails( $recipients, $user_id, $course_id, $notification, $group_id );

	$bcc = learndash_notifications_get_bcc( $n->ID );

	// bail if both emails and bcc are empty
	if ( empty( $emails ) && empty( $bcc ) ) {
		return;
	}

	global $ld_notifications_shortcode_data, $ld_notifications_quiz_result;
	$ld_notifications_shortcode_data = [
		'user_id'         => $user_id,
		'course_id'       => $course_id,
		'lesson_id'       => $lesson_id,
		'topic_id'        => $topic_id,
		'assignment_id'   => $assignment_id,
		'quiz_id'         => $quiz_id,
		'question_id'     => $question_id,
		'notification_id' => $n->ID,
		'group_id'        => $group_id,
		'quiz_result'     => isset( $ld_notifications_quiz_result ) && ! empty( $ld_notifications_quiz_result ) ? $ld_notifications_quiz_result : [],
	];

	$shortcode_data = $ld_notifications_shortcode_data;

	if ( is_array( $update_where ) && ! empty( $update_where ) ) {
		$update_where['notification_id'] = $n->ID;
	}

	// Set to delayed emails if $n has delay option
	$delay = (int) get_post_meta( $n->ID, '_ld_notifications_delay', true );
	if ( is_int( $delay ) && $delay > 0 && ! isset( $lesson_access_from ) ) {
		$sent_on = strtotime( '+' . $delay . ' days', time() );
		learndash_notifications_save_delayed_email( $n, $emails, $sent_on, $shortcode_data, $bcc, $update_where );
	} elseif ( isset( $lesson_access_from ) && $lesson_access_from > time() ) {
		if ( is_int( $delay ) && $delay > 0 ) {
			$sent_on = strtotime( '+' . $delay . ' days', $lesson_access_from );
		} else {
			$sent_on = $lesson_access_from;
		}
		learndash_notifications_save_delayed_email( $n, $emails, $sent_on, $shortcode_data, $bcc, $update_where );
	} elseif ( ( ! isset( $delay ) || $delay == 0 ) && ( ! isset( $lesson_access_from ) || $lesson_access_from == 0 ) ) {
		/**
		 * Filter hook to allow admin to decide whether to send notification or not
		 *
		 * @param bool                  True to send|false to disable
		 * @param array $shortcode_data Data for this notification
		 * @return bool                 True to send|false to disable
		 */
		if ( apply_filters( 'learndash_notifications_send_notification', true, $shortcode_data ) ) {
			/**
			 * Action hook before sending out notification or save it to database
			 *
			 * @param array $shortcode_data Notification trigger data that trigger this notification sending
			 */
			do_action( 'learndash_notification_before_send_notification', $shortcode_data );

			learndash_notifications_send_email( $emails, $n->post_title, $n->post_content, $bcc, $n->ID );

			/**
			 * Action hook after sending out notification or save it to database
			 *
			 * @param array $shortcode_data Notification trigger data that trigger this notification sending
			 */
			do_action( 'learndash_notification_after_send_notification', $shortcode_data );
		}
	}
}

/**
 * Send learndash notification email
 *
 * @param array  $emails         List of email addresses
 * @param string $title          Title of message
 * @param string $content        Content of message
 * @param array  $bcc            List of email address as BCC
 * @param object $notification_id ID of WP_Post of LD Notification
 * @return bool                     True if mail sent|false otherwise
 */
function learndash_notifications_send_email( $emails, $title, $content, $bcc = [], $notification_id = null ) {
	$content = do_shortcode( $content );
	if ( ! strstr( $content, '<!DOCTYPE' ) && ! strstr( $content, '<p' ) && ! strstr( $content, '<div' ) ) {
		$content = wpautop( $content );
	}

	$title   = apply_filters( 'learndash_notifications_email_subject', do_shortcode( $title ), $notification_id );
	$content = apply_filters( 'learndash_notifications_email_content', $content, $notification_id );

	$emails = array_merge( $emails, $bcc );
	$emails = array_unique( $emails );

	// Send email to each address separately to prevent recipient
	// knowing other recipient's email addresses
	$send = [];
	foreach ( $emails as $email ) {
		// Continue if $email is blank
		$email = trim( $email );
		if ( empty( $email ) ) {
			continue;
		}

		if ( apply_filters( 'learndash_notifications_send_email', true, $email, $notification_id ) ) {
			// Change mail content type to HTML
			add_filter( 'wp_mail_content_type', 'learndash_notifications_set_html_mail_content_type' );

			$send[] = wp_mail( $email, $title, $content );

			// Reset mail content type back to plain
			remove_filter( 'wp_mail_content_type', 'learndash_notifications_set_html_mail_content_type' );
		}
	}

	if ( in_array( false, $send, true ) ) {
		return false;
	} else {
		return true;
	}
}

function learndash_notifications_set_html_mail_content_type() {
	return 'text/html';
}

/**
 * Get all LearnDash courses
 *
 * @return array Array of course WP_Post objet
 */
function learndash_notifications_get_all_courses() {
	$args = [
		'post_type'      => 'sfwd-courses',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	];

	return get_posts( $args );
}

/**
 * TEST FUNCTION
 */
// function learndash_notifications_send_email( $emails, $title, $content ) {
// Change mail content type to HTML
// add_filter( 'wp_mail_content_type', 'learndash_notifications_set_html_mail_content_type' );

// $content = wpautop( do_shortcode( $content ) );

// $send = wp_mail( $emails, $title, $content );

// Reset mail content type back to plain
// remove_filter( 'wp_mail_content_type', 'learndash_notifications_set_html_mail_content_type' );

// return $send;
// }

// function test_phpmailer_init( $phpmailer )
// {
// echo '<pre>';
// var_dump( $phpmailer );
// echo '</pre>';
// return $phpmailer;
// }
// add_action( 'phpmailer_init', 'test_phpmailer_init' );
