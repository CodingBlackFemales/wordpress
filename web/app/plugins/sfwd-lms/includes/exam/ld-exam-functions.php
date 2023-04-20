<?php
/**
 * Exam functions
 *
 * @since 4.0.0
 *
 * @package LearnDash\Exams
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LEARNDASH_EXAM_CHALLENGE_POST_META_KEY' ) ) {
	define( 'LEARNDASH_EXAM_CHALLENGE_POST_META_KEY', '_ld_exam_challenge' );
}

/**
 * Redirect the user to the associate challenge exam from the course.
 *
 * Processing wil redirect the user to the Exam URL or return to
 * calling function.
 *
 * @since 4.0.0
 * @param int $course_id Course ID.
 * @return void
 */
function learndash_course_exam_challenge_redirect( $course_id = 0 ) {
	if ( empty( $course_id ) ) {
		return;
	}

	// We check the price_type because "open" courses do not requires the user to login.
	$course_price_type = learndash_get_setting( $course_id, 'course_price_type' );
	if ( 'open' === $course_price_type ) {
		return;
	}

	// Ensure the user is logged in.
	$user_id = get_current_user_id();
	if ( empty( $user_id ) ) {
		return;
	}

	// Ensure the user is enrolled in the course.
	$course_access = sfwd_lms_has_access( $course_id, $user_id );
	if ( true !== $course_access ) {
		return;
	}

	// If the user completed the course we can't trigger the exam challenge.
	$course_status = learndash_course_status( $course_id, $user_id, true );
	if ( 'completed' === $course_status ) {
		return;
	}

	if ( ! learndash_is_course_prerequities_completed( $course_id, $user_id ) ) { // cspell:disable-line -- prerequities are prerequisites...
		return;
	}

	if ( ! learndash_check_user_course_points_access( $course_id, $user_id ) ) {
		return;
	}

	$exam_id = (int) learndash_get_course_exam_challenge( $course_id );
	if ( ! empty( $exam_id ) ) {

		if ( ! is_post_publicly_viewable( $exam_id ) ) {
			return;
		}

		if ( true === learndash_can_user_bypass(
			$user_id,
			'learndash_course_exam_challenge_redirect',
			array(
				'user_id'       => $user_id,
				'course_id'     => $course_id,
				'exam_id'       => $exam_id,
				'course_status' => $course_status,
			)
		) ) {
			return;
		}

		$exam_status = learndash_get_user_course_exam_challenge_status( $user_id, $course_id );
		if ( 'not_taken' === $exam_status ) {

			$exam_show_new_enroll = learndash_get_setting( $exam_id, 'show_new_enroll' );
			if ( ( 'on' === $exam_show_new_enroll ) && ( 'not_started' !== $course_status ) ) {
				return;
			}

			$exam_link = get_permalink( $exam_id );

			/**
			 * Filters the Exam redirect URL.
			 *
			 * @param string $exam_link The Exam URL.
			 * @param int    $course_id Course Post ID.
			 * @param int    $exam_id   Exam Post ID.
			 * @param int    $user_id   User ID.
			 */
			$exam_link = apply_filters( 'learndash_course_to_exam_challenge_redirect', $exam_link, $course_id, $exam_id, $user_id );
			if ( ! empty( $exam_link ) ) {
				learndash_safe_redirect( $exam_link );
			}
		}
	}
}

/**
 * Check user access to Exam posts.
 *
 * This function will check if the user has access to the Exam post.
 *
 * @since 4.0.0
 *
 * @param int $exam_id Exam Post ID.
 */
function learndash_exam_challenge_view_permission( $exam_id = 0 ) {
	$user_id = get_current_user_id();
	if ( ( ! empty( $user_id ) ) && ( ! empty( $exam_id ) ) ) {

		// Allow Admin to bypass redirect and view exam.
		if ( true === learndash_can_user_bypass(
			$user_id,
			'learndash_exam_challenge_view_permission',
			array(
				'user_id' => $user_id,
				'exam_id' => $exam_id,
			)
		) ) {
			return;
		}

		// Check if the Exam has an associated "show" course, we send the user back to the course archive.
		$course_id_show = (int) learndash_get_setting( $exam_id, 'exam_challenge_course_show' );
		if ( ! empty( $course_id_show ) ) {
			$exam_status = learndash_get_user_course_exam_challenge_status( $user_id, $course_id_show );
			// If the user has not taken the exam we pass them one to it.
			if ( 'not_taken' === $exam_status ) {
				return;
			} elseif ( 'passed' === $exam_status ) {
				$course_id_passed = (int) learndash_get_setting( $exam_id, 'exam_challenge_course_passed' );
				if ( ( ! empty( $course_id_passed ) ) && ( $course_id_passed !== $course_id_show ) && ( is_post_publicly_viewable( $course_id_passed ) ) ) {
					$course_passed_link = get_permalink( $course_id_passed );
					if ( ! empty( $course_passed_link ) ) {
						/**
						 * Filters the Exam to Passed Course redirect URL.
						 *
						 * @param string $course_link The Course Passed URL.
						 * @param int    $course_id   Course Post ID.
						 * @param int    $exam_id     Exam Post ID.
						 * @param int    $user_id     User ID.
						 * @param string $exam_status Exam Status slug.
						*/
						$course_passed_link = apply_filters( 'learndash_exam_challenge_to_course_passed_redirect', $course_passed_link, $course_id_passed, $exam_id, $user_id, $exam_status );
						if ( ! empty( $course_passed_link ) ) {
							learndash_safe_redirect( $course_passed_link );
						}
					}
				}

				if ( is_post_publicly_viewable( $course_id_show ) ) {
					$course_show_link = get_permalink( $course_id_show );
					if ( ! empty( $course_show_link ) ) {
						/**
						 * Filters the Exam to Show Course redirect URL.
						 *
						 * @param string $course_link The Course Show URL.
						 * @param int    $course_id   Course Post ID.
						 * @param int    $exam_id     Exam Post ID.
						 * @param int    $user_id     User ID.
						 * @param string $exam_status Exam Status slug.
						*/
						$course_show_link = apply_filters( 'learndash_exam_challenge_to_course_show_redirect', $course_show_link, $course_id_show, $exam_id, $user_id, $exam_status );
						if ( ! empty( $course_show_link ) ) {
							learndash_safe_redirect( $course_show_link );
						}
					}
				}
			} elseif ( 'failed' === $exam_status ) {
				if ( is_post_publicly_viewable( $course_id_show ) ) {
					$course_show_link = get_permalink( $course_id_show );
					if ( ! empty( $course_show_link ) ) {
						/**
						 * Filters the Exam to Show Course redirect URL.
						 *
						 * @param string $course_link The Course Show URL.
						 * @param int    $course_id   Course Post ID.
						 * @param int    $exam_id     Exam Post ID.
						 * @param int    $user_id     User ID.
						 * @param string $exam_status Exam Status slug.
						*/
						$course_show_link = apply_filters( 'learndash_exam_challenge_to_course_show_redirect', $course_show_link, $course_id_show, $exam_id, $user_id, $exam_status );
						if ( ! empty( $course_show_link ) ) {
							learndash_safe_redirect( $course_show_link );
						}
					}
				}
			}
		}

		// Allow viewing of the exam if the user has access to the course.
		if ( current_user_can( 'edit_post', $exam_id ) ) {
			return;
		}
	}

	// If not returned via any of the above logic, we redirect the user to the course archive or home URL.
	$course_archive_link = get_post_type_archive_link( learndash_get_post_type_slug( 'course' ) );
	if ( ! empty( $course_archive_link ) ) {
		learndash_safe_redirect( $course_archive_link );
	}
	learndash_safe_redirect( get_home_url() );
}

/**
 * Gets the list of enrolled courses for a Challenge Exam.
 *
 * @since 4.0.0
 *
 * @param int $exam_id Optional. Exam ID. Default 0.
 *
 * @return array An array of course IDs.
 */
function learndash_get_exam_challenge_courses( $exam_id = 0 ) {
	$course_ids = array();

	$exam_id = absint( $exam_id );
	if ( ! empty( $exam_id ) ) {

		$query_args = array(
			'post_type'      => learndash_get_post_type_slug( 'course' ),
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => LEARNDASH_EXAM_CHALLENGE_POST_META_KEY,
					'value'   => $exam_id,
					'compare' => '=',
				),
			),
		);

		$query = new WP_Query( $query_args );
		if ( ( is_a( $query, 'WP_Query' ) ) && ( property_exists( $query, 'posts' ) ) ) {
			$course_ids = $query->posts;
		}
	}

	return $course_ids;
}


/**
 * Gets the list of available courses for a Challenge Exam.
 *
 * This is a list of Courses not associated with a Challenge Exam.
 *
 * @since 4.0.0
 *
 * @return array An array of course IDs.
 */
function learndash_get_exam_challenge_available_courses() {
	$query_args = array(
		'post_type'      => learndash_get_post_type_slug( 'course' ),
		'fields'         => 'ids',
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'     => LEARNDASH_EXAM_CHALLENGE_POST_META_KEY,
				'compare' => 'NOT EXISTS',
			),
		),
	);

	$query = new WP_Query( $query_args );

	return $query->posts;
}

/**
 * Sets the list of enrolled courses for an exam.
 *
 * @since 4.0.0
 *
 * @param int   $exam_id          Optional. Exam ID. Default 0.
 * @param array $exam_courses_new Optional. An array of courses to enroll an exam. Default empty array.
 */
function learndash_set_exam_challenge_courses( $exam_id = 0, $exam_courses_new = array() ) {
	$exam_id = absint( $exam_id );
	if ( ! empty( $exam_id ) ) {

		$exam_courses_old = learndash_get_exam_challenge_courses( $exam_id, true );

		$exam_courses_intersect = array_intersect( $exam_courses_new, $exam_courses_old );

		$exam_courses_add = array_diff( $exam_courses_new, $exam_courses_intersect );
		if ( ! empty( $exam_courses_add ) ) {
			foreach ( $exam_courses_add as $course_id ) {
				learndash_update_course_exam_challenge( $course_id, $exam_id, false );
			}
		}

		$exam_courses_remove = array_diff( $exam_courses_old, $exam_courses_intersect );
		if ( ! empty( $exam_courses_remove ) ) {
			foreach ( $exam_courses_remove as $course_id ) {
				learndash_update_course_exam_challenge( $course_id, $exam_id, true );
			}
		}
	}
}


/**
 * Get the Course to Exam challenge association.
 *
 * @since 4.0.0
 *
 * @param int $course_id Course ID.
 *
 * @return int The Exam ID if found, zero if not.
 */
function learndash_get_course_exam_challenge( $course_id = 0 ) {

	$course_id = absint( $course_id );
	$exam_id   = 0;

	if ( ! empty( $course_id ) ) {
		$exam_id        = get_post_meta( $course_id, LEARNDASH_EXAM_CHALLENGE_POST_META_KEY, true );
		$exam_id        = absint( $exam_id );
		$exam_post_type = get_post_type( $exam_id );

		if ( learndash_get_post_type_slug( 'exam' ) !== get_post_type( $exam_id ) ) {
			$exam_id = 0;
		}
	}

	return $exam_id;
}

/**
 * Updates the Course to Exam challenge association.
 *
 * @since 4.0.0
 *
 * @param int     $course_id Course ID.
 * @param int     $exam_id   Exam ID.
 * @param boolean $remove    Optional. Whether to remove the exam from the course. Default false.
 *
 * @return boolean true on action success otherwise false.
 */
function learndash_update_course_exam_challenge( $course_id = 0, $exam_id = 0, $remove = false ) {
	$action_success = false;

	$course_id = absint( $course_id );
	$exam_id   = absint( $exam_id );

	if ( ( empty( $exam_id ) ) && ( true !== $remove ) ) {
		$remove = true;
	}

	if ( true === $remove ) {
		$exam_enrolled = learndash_get_course_exam_challenge( $course_id );
		$exam_enrolled = absint( $exam_enrolled );
		if ( ! empty( $exam_enrolled ) ) {
			$action_success = (bool) delete_post_meta( $course_id, LEARNDASH_EXAM_CHALLENGE_POST_META_KEY );
			if ( true === $action_success ) {
				/**
				 * Fires after the exam challenge is removed from the course meta.
				 *
				 * @since 4.0.0
				 *
				 * @param int $course_id Course ID.
				 * @param int $exam_id   Exam ID.
				 */
				do_action( 'learndash_removed_course_exam_challenge', $course_id, $exam_enrolled );
			}
		}
	} elseif ( ( true !== $remove ) && ( ! empty( $course_id ) ) && ( ! empty( $exam_id ) ) ) {
		$action_success = (bool) update_post_meta( $course_id, LEARNDASH_EXAM_CHALLENGE_POST_META_KEY, $exam_id );
		if ( true === $action_success ) {
			/**
			 * Fires after the course is added to the exam challenge meta.
			 *
			 * @since 4.0.0
			 *
			 * @param int $course_id Course ID.
			 * @param int $exam_id   Exam ID.
			 */
			do_action( 'learndash_added_course_exam_challenge', $course_id, $exam_id );
		}
	}

	return $action_success;
}

/**
 * Get the Course Exam Challenge Status label from slug.
 *
 * @since 4.0.0
 *
 * @param string $status_slug Course Exam Challenge Status slug.
 *
 * @return string|null.
 */
function learndash_course_exam_challenge_status_label( $status_slug = '' ) {
	global $learndash_exam_challenge_statuses;

	if ( ( ! empty( $status_slug ) ) && ( isset( $learndash_exam_challenge_statuses[ $status_slug ] ) ) ) {
		return $learndash_exam_challenge_statuses[ $status_slug ];
	}
}

/**
 * Get the user's course exam challenge status.
 *
 * @since 4.0.0
 *
 * @param int $user_id      User ID.
 * @param int $course_id    Course ID.
 *
 * @return string key from $learndash_exam_challenge_statuses or empty.
 */
function learndash_get_user_course_exam_challenge_status( $user_id = 0, $course_id = 0 ) {
	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );

	if ( ( empty( $user_id ) ) || ( empty( $course_id ) ) ) {
		return '';
	}

	$exam_id = (int) learndash_get_course_exam_challenge( $course_id );
	if ( empty( $exam_id ) ) {
		return '';
	}

	$exam_activity = learndash_get_user_course_exam_activity( $user_id, $course_id, $exam_id );
	return learndash_grade_user_course_exam_activity( $exam_activity );
}

/**
 * Grade the user's course exam activity record.
 *
 * @since 4.0.0
 * @param array $exam_activity LDLMS_Model_Activity instance or null.
 * @return string status slug of activity. VAlues 'passed', 'failed', 'not_taken'.
 */
function learndash_grade_user_course_exam_activity( $exam_activity ) {
	if ( is_object( $exam_activity ) && ( is_a( $exam_activity, 'LDLMS_Model_Activity' ) ) ) {
		if ( true === $exam_activity->activity_status ) {
			return 'passed';
		} else {
			return 'failed';
		}
	} else {
		return 'not_taken';
	}
}

/**
 * Get the User Course Exam Challenge Activity and Meta record.
 *
 * @since 4.0.0
 *
 * @param int $user_id      User ID.
 * @param int $course_id    Course ID.
 * @param int $exam_id      Exam ID.
 *
 * return object|null The activity object (LDLMS_Model_Activity) or null if not found.
 */
function learndash_get_user_course_exam_activity( $user_id = 0, $course_id = 0, $exam_id = 0 ) {
	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );
	$exam_id   = absint( $exam_id );

	$activity = null;

	if ( ( ! empty( $user_id ) ) && ( ! empty( $exam_id ) ) ) {

		$args = array(
			'course_id'     => $course_id,
			'user_id'       => $user_id,
			'post_id'       => $exam_id,
			'activity_type' => 'exam',
		);

		$activity = learndash_get_user_activity( $args );
		if ( ( is_object( $activity ) ) && ( property_exists( $activity, 'activity_id' ) ) && ( ! empty( $activity->activity_id ) ) ) {
			$activity = new LDLMS_Model_Activity( $activity );

			// If we have an existing activity record we include the meta.
			$activity->activity_meta = (array) learndash_get_user_activity_meta( $activity->activity_id );
		}
	}

	return $activity;
}

/**
 * Removes blocks from showing on Exam post type
 *
 * @since 4.0.0
 **/
function learndash_exam_deregister_post_type_blocks() {
	$post_type = get_post_type( get_the_ID() );
	if ( learndash_get_post_type_slug( 'exam' ) !== $post_type ) {
		wp_enqueue_script(
			'learndash-deregister-post-type-blocks',
			plugins_url( 'gutenberg/blocks/deregister-exam-question-block.js', dirname( __FILE__ ) ),
			array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post' ),
			LEARNDASH_SCRIPT_VERSION_TOKEN,
			false
		);
	}
}
add_action( 'enqueue_block_editor_assets', 'learndash_exam_deregister_post_type_blocks' );

/**
 * Returns message if current active theme is set to Legacy
 *
 * @since 4.0.0
 */
function learndash_exam_legacy_theme_warning_message() {
	$general_setting_link = '<a href="' . esc_url( add_query_arg( array( 'page' => 'learndash_lms_settings' ), admin_url( 'admin.php' ) ) . '#' ) . '">' . esc_html__( 'General Settings', 'learndash' ) . '</a>';

	// translators: placeholders: Challenge Exams.
	$message = '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html_x( 'You are using the Legacy LearnDash theme which does not support %1$s, please visit %2$s to select a newer theme.', 'placeholders: Challenge Exams', 'learndash' ), esc_html( learndash_get_custom_label_lower( 'exams' ) ), $general_setting_link ) . '</p></div>';

	/**
	 * Filters message when Legacy theme is set for the exam CPT
	 *
	 * @since 4.0.0
	 *
	 * @param string $message The message when the theme is set to Legacy
	 * @return string $message The message when the theme is set to Legacy
	 */
	return apply_filters( 'learndash_exam_legacy_theme_warning_message', $message );
}

/**
 * Handle Exam actions via AJAX requests from WP Profile page.
 *
 * @since 4.0.0
 */
function learndash_exam_process_ajax() {
	if ( ! isset( $_POST['action'] ) ) {
		return;
	}
	$exam_action = esc_attr( $_POST['action'] );

	if ( ! isset( $_POST['user_id'] ) ) {
		return;
	}
	$user_id = absint( $_POST['user_id'] );
	if ( empty( $user_id ) ) {
		return;
	}

	if ( ! isset( $_POST['exam_id'] ) ) {
		return;
	}
	$exam_id = absint( $_POST['exam_id'] );
	if ( empty( $user_id ) ) {
		return;
	}

	if ( ! isset( $_POST['course_id'] ) ) {
		return;
	}
	$course_id = absint( $_POST['course_id'] );
	if ( empty( $course_id ) ) {
		return;
	}

	if ( ! isset( $_POST['exam_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( esc_attr( $_POST['exam_nonce'] ), 'learndash-exam_nonce-' . $user_id . '-' . $exam_id . '-' . $course_id ) ) {
		return;
	}

	$user = get_user_by( 'id', $user_id );
	if ( ( ! $user ) || ( ! is_a( $user, 'WP_User' ) ) ) {
		return;
	}

	$exam = get_post( $exam_id );
	if ( ( ! $exam ) || ( ! is_a( $exam, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'exam' ) !== $exam->post_type ) ) {
		return;
	}

	$course = get_post( $course_id );
	if ( ( ! $course ) || ( ! is_a( $course, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'course' ) !== $course->post_type ) ) {
		return;
	}

	$course_exam_challenge = (int) learndash_get_course_exam_challenge( $course_id );
	if ( $course_exam_challenge !== $exam_id ) {
		return;
	}

	$exam_activity_args = array(
		'course_id'     => $course_id,
		'user_id'       => $user_id,
		'post_id'       => $exam_id,
		'activity_type' => 'exam',
	);

	$json_data = array();

	switch ( $exam_action ) {
		case 'learndash_exam_process_reset':
			$exam_activity = learndash_get_user_activity( $exam_activity_args );
			if ( ( is_a( $exam_activity, 'LDLMS_Model_Activity' ) ) && ( property_exists( $exam_activity, 'activity_id' ) ) && ( ! empty( $exam_activity->activity_id ) ) ) {
				learndash_delete_user_activity( $exam_activity->activity_id );

				$json_data['link_text'] = esc_html__( '(pending)', 'learndash' );
				wp_send_json_success( $json_data );
			}

			break;

		case 'learndash_exam_process_complete':
			$exam_activity = learndash_get_user_activity( $exam_activity_args, true );
			if ( ( is_a( $exam_activity, 'LDLMS_Model_Activity' ) ) && ( property_exists( $exam_activity, 'activity_id' ) ) && ( ! empty( $exam_activity->activity_id ) ) ) {
				$exam_activity = (array) $exam_activity;

				if ( true !== (bool) $exam_activity['activity_status'] ) {
					$exam_activity['activity_status'] = 1;

					$activity_timestamp = time();
					if ( empty( $exam_activity['activity_started'] ) ) {
						$exam_activity['activity_started'] = $activity_timestamp;
					}
					if ( empty( $exam_activity['activity_completed'] ) ) {
						$exam_activity['activity_completed'] = $activity_timestamp;
					}

					learndash_update_user_activity( $exam_activity );

					// Set an activity meta field so we know the activity was completed manually.
					learndash_update_user_activity_meta( $exam_activity['activity_id'], 'manual_complete_user_id', get_current_user_id() );
					learndash_update_user_activity_meta( $exam_activity['activity_id'], 'manual_complete_time', $activity_timestamp );

					// Now set the associated course as complete.
					$course_exam_challenge_passed = (int) learndash_get_setting( $exam_id, 'exam_challenge_course_passed' );
					if ( empty( $course_exam_challenge_passed ) ) {
						$course_exam_challenge_passed = $course_id;
					}
					learndash_user_course_complete_all_steps( $user_id, $course_exam_challenge_passed );

					$json_data['link_text'] = esc_html__( '(pending)', 'learndash' );
					wp_send_json_success( $json_data );
				}
			}

			break;

		default:
			break;
	}
}
add_action( 'wp_ajax_learndash_exam_process_reset', 'learndash_exam_process_ajax' );
add_action( 'wp_ajax_learndash_exam_process_complete', 'learndash_exam_process_ajax' );
