<?php
/**
 * User functions
 *
 * @since 2.1.0
 *
 * @package LearnDash\Users
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deletes the user data.
 *
 * Fires on `delete_user` hook.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.1.0
 *
 * @param int|void $user_id User ID.
 */
function learndash_delete_user_data( $user_id ) {
	global $wpdb;

	if ( ! current_user_can( 'edit_users' ) ) {
		return;
	}

	$user_id = intval( $user_id );
	if ( ! empty( $user_id ) ) {
		$user = get_user_by( 'id', $user_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ref_ids = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT statistic_ref_id FROM ' . esc_sql( LDLMS_DB::get_table_name( 'quiz_statistic_ref' ) ) . ' WHERE  user_id = %d ',
				absint( $user->ID )
			)
		);

		if ( ! empty( $ref_ids[0] ) ) {

			$ref_ids = array_map( 'absint', $ref_ids );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared -- IN clause
				$wpdb->prepare( 'DELETE FROM ' . esc_sql( LDLMS_DB::get_table_name( 'quiz_statistic' ) ) . ' WHERE statistic_ref_id IN (' . LDLMS_DB::escape_IN_clause_placeholders( $ref_ids ) . ')', LDLMS_DB::escape_IN_clause_values( $ref_ids ) )
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			LDLMS_DB::get_table_name( 'quiz_statistic_ref' ),
			array(
				'user_id' => $user->ID,
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->usermeta,
			array(
				'meta_key' => '_sfwd-quizzes', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'user_id'  => $user->ID,
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->usermeta,
			array(
				'meta_key' => '_sfwd-course_progress', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'user_id'  => $user->ID,
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s AND user_id = %d",
				'completed_%',
				absint( $user->ID )
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s AND user_id = %d",
				'course_%_access_from',
				absint( $user->ID )
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s AND user_id = %d",
				'course_completed_%',
				absint( $user->ID )
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s AND user_id = %d",
				'learndash_course_expired_%',
				absint( $user->ID )
			)
		);

		// Added in v2.3.1 to remove the quiz locks for user.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . esc_sql( LDLMS_DB::get_table_name( 'quiz_lock' ) ) . ' WHERE user_id = %d',
				absint( $user->ID )
			)
		);

		learndash_report_clear_user_activity_by_types( $user_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( LDLMS_DB::get_table_name( 'quiz_lock' ), array( 'user_id' => $user->ID ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( LDLMS_DB::get_table_name( 'quiz_toplist' ), array( 'user_id' => $user->ID ) );

		// Move user uploaded Assignments to Trash.
		$user_assignments_query_args = array(
			'post_type'   => 'sfwd-assignment',
			'post_status' => 'publish',
			'nopaging'    => true,
			'author'      => $user->ID,
		);

		$user_assignments_query = new WP_Query( $user_assignments_query_args );
		if ( $user_assignments_query->have_posts() ) {

			foreach ( $user_assignments_query->posts as $assignment_post ) {
				wp_trash_post( $assignment_post->ID );
			}
		}
		wp_reset_postdata();

		// Move user uploaded Essay to Trash.
		$user_essays_query_args = array(
			'post_type' => 'sfwd-essays',
			'nopaging'  => true,
			'author'    => $user->ID,
		);

		$user_essays_query = new WP_Query( $user_essays_query_args );
		if ( $user_essays_query->have_posts() ) {

			foreach ( $user_essays_query->posts as $essay_post ) {
				wp_trash_post( $essay_post->ID );
			}
		}
		wp_reset_postdata();

		/**
		 * Fires after deleting user data.
		 *
		 * @param int $user_id User ID.
		 */
		do_action( 'learndash_delete_user_data', $user_id );
	}
}

add_action( 'delete_user', 'learndash_delete_user_data' );


/**
 * Gets the list of all courses enrolled by the user.
 *
 * @since 2.2.1
 *
 * @param int     $user_id           Optional. User ID. Default 0.
 * @param array   $course_query_args Optional. An array of `WP_Query` arguments. Default empty array.
 * @param boolean $bypass_transient  Optional. Whether to bypass the transient cache or not. Default false.
 *
 * @return array An array of courses enrolled by user.
 */
function learndash_user_get_enrolled_courses( $user_id = 0, $course_query_args = array(), $bypass_transient = false ) {

	$course_ids = array();

	if ( empty( $user_id ) ) {
		return $course_ids;
	}

	$bypass_transient = true;
	$transient_key    = 'learndash_user_courses_' . $user_id;

	if ( ! $bypass_transient ) {
		$course_ids_transient = LDLMS_Transients::get( $transient_key );
	} else {
		$course_ids_transient = false;
	}

	if ( false === $course_ids_transient ) {
		if ( learndash_can_user_autoenroll_courses( $user_id ) ) {
			$defaults = array(
				'post_type' => 'sfwd-courses',
				'fields'    => 'ids',
				'nopaging'  => true,
			);

			$course_query_args = wp_parse_args( $course_query_args, $defaults );
			$course_query      = new WP_Query( $course_query_args );
			if ( ( is_a( $course_query, 'WP_Query' ) ) && ( ! empty( $course_query->posts ) ) ) {
				$course_ids = $course_query->posts;
			}
		} else {

			$course_ids_open = learndash_get_posts_by_price_type( learndash_get_post_type_slug( 'course' ), 'open' );
			if ( ! empty( $course_ids_open ) ) {
				$course_ids = array_merge( $course_ids, $course_ids_open );
			}

			if ( true === learndash_use_legacy_course_access_list() ) {
				$course_ids_access = learndash_get_user_course_access_list( $user_id );
				if ( ! empty( $course_ids_access ) ) {
					$course_ids = array_merge( $course_ids, $course_ids_access );
				}
			}

			$course_ids_meta = learndash_get_user_courses_from_meta( $user_id );
			if ( ! empty( $course_ids_meta ) ) {
				$course_ids = array_merge( $course_ids, $course_ids_meta );
			}

			$course_ids_groups = learndash_get_user_groups_courses_ids( $user_id );
			if ( ! empty( $course_ids_groups ) ) {
				$course_ids = array_merge( $course_ids, $course_ids_groups );
			}

			if ( ! empty( $course_ids ) ) {
				$course_ids = array_unique( $course_ids );

				$defaults = array(
					'post_type' => 'sfwd-courses',
					'fields'    => 'ids',
					'nopaging'  => true,
				);

				$course_query_args             = wp_parse_args( $course_query_args, $defaults );
				$course_query_args['post__in'] = $course_ids;

				$course_query = new WP_Query( $course_query_args );
				if ( property_exists( $course_query, 'posts' ) ) {
					$course_ids = $course_query->posts;
				}
			}
		}

		LDLMS_Transients::set( $transient_key, $course_ids, MINUTE_IN_SECONDS );

	} else {
		$course_ids = $course_ids_transient;
	}

	return $course_ids;
}

/**
 * Enrolls the user in new courses.
 *
 * @since 2.2.1
 *
 * @param int   $user_id          Optional. The ID of user to enroll courses. Default 0.
 * @param array $user_courses_new Optional. An array of new course ids to enroll a user. Default empty array.
 */
function learndash_user_set_enrolled_courses( $user_id = 0, $user_courses_new = array() ) {

	if ( ! empty( $user_id ) ) {

		$user_courses_old = learndash_user_get_enrolled_courses( $user_id, true );
		if ( ( empty( $user_courses_old ) ) && ( ! is_array( $user_courses_old ) ) ) {
			$user_courses_old = array();
		}
		$user_courses_intersect = array_intersect( $user_courses_new, $user_courses_old );

		$user_courses_add = array_diff( $user_courses_new, $user_courses_intersect );
		if ( ! empty( $user_courses_add ) ) {
			foreach ( $user_courses_add as $course_id ) {
				ld_update_course_access( $user_id, $course_id );
			}
		}
		$user_courses_remove = array_diff( $user_courses_old, $user_courses_intersect );
		if ( ! empty( $user_courses_remove ) ) {
			foreach ( $user_courses_remove as $course_id ) {
				ld_update_course_access( $user_id, $course_id, true );
			}
		}

		// Finally clear our cache for other services.
		$transient_key = 'learndash_user_courses_' . $user_id;
		LDLMS_Transients::delete( $transient_key );
	}
}

/**
 * Gets all courses for the user via the user meta 'course_XXX_access_from'.
 *
 * @since 2.2.1
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int $user_id Optional. ID of the user to get meta. Default 0.
 *
 * @return array An array of user's course IDs.
 */
function learndash_get_user_courses_from_meta( $user_id = 0 ) {
	global $wpdb;

	$user_course_ids = array();

	$user_id = intval( $user_id );
	if ( ! empty( $user_id ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$user_course_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT REPLACE( REPLACE(meta_key, 'course_', ''), '_access_from', '' ) FROM " . $wpdb->usermeta . ' as usermeta WHERE user_id=%d AND meta_key LIKE %s ',
				$user_id,
				'course_%_access_from'
			)
		);
		if ( ! empty( $user_course_ids ) ) {
			$user_course_ids = array_map( 'intval', $user_course_ids );
		}
	}
	return $user_course_ids;
}

/**
 * Checks whether to show user course complete.
 *
 * @global string $pagenow
 *
 * @param int $user_id Optional. User ID. Default 0.
 *
 * @return boolean Returns true to show user course complete otherwise false.
 */
function learndash_show_user_course_complete( $user_id = 0 ) {

	$show_options = false;

	if ( ! empty( $user_id ) ) {

		global $pagenow;

		if ( ( ( 'profile.php' == $pagenow ) || ( 'user-edit.php' == $pagenow ) ) && ( current_user_can( 'edit_users' ) ) ) {
			$show_options = true;
		} elseif ( 'admin.php' == $pagenow ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ( isset( $_GET['page'] ) ) && ( 'group_admin_page' == $_GET['page'] ) ) {
				if ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) {
					$show_options = true;
				}
			}
		}
	}

	// See example snippet of this filter https://developers.learndash.com/hook/learndash_show_user_course_complete_options/.
	/**
	 * Filters the status of whether the course is completed for a user or not.
	 *
	 * @since 2.3.0
	 *
	 * @param boolean $show_options Whether the course is completed or not.
	 * @param int     $user_id      ID of the logged in user to check.
	 */
	return apply_filters( 'learndash_show_user_course_complete_options', $show_options, $user_id );
}

/**
 * Saves the date of course completion for a user.
 *
 * @since 2.3.0
 *
 * @param int $user_id Optional. User ID. Default 0.
 */
function learndash_save_user_course_complete( $user_id = 0 ) {

	// Hate this cross-logic. But here it is.
	// If we are clearing out the user's LD data then we abort this function. Now use going through the update.
	if ( ( isset( $_POST['learndash_delete_user_data'] ) ) && ( ! empty( $_POST['learndash_delete_user_data'] ) ) ) {
		return;
	}

	if ( empty( $user_id ) ) {
		return;
	}

	if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
		if ( ! learndash_is_group_leader_of_user( get_current_user_id(), $user_id ) ) {
			return;
		}
	} elseif ( ! current_user_can( 'edit_users' ) ) {
		return;
	}

	if ( ( isset( $_POST['user_progress'] ) ) && ( isset( $_POST['user_progress'][ $user_id ] ) ) && ( ! empty( $_POST['user_progress'][ $user_id ] ) ) ) {
		if ( ( isset( $_POST[ 'user_progress-' . $user_id . '-nonce' ] ) ) && ( ! empty( $_POST[ 'user_progress-' . $user_id . '-nonce' ] ) ) ) {
			if ( wp_verify_nonce( $_POST[ 'user_progress-' . $user_id . '-nonce' ], 'user_progress-' . $user_id ) ) {
				$user_progress = (array) json_decode( stripslashes( $_POST['user_progress'][ $user_id ] ) );
				$user_progress = json_decode( wp_json_encode( $user_progress ), true );
				learndash_process_user_course_progress_update( $user_id, $user_progress );
			}
		}
	}
}

/**
 * Process user course progress changes.
 *
 * @since 4.0.0
 * @param int   $user_id       User ID to update.
 * @param array $user_progress User progress array.
 *
 * @return array Array of processed course IDs.
 *
 * The user_progress structure should be as the following:
 * The top-level nodes are 'course' and 'quiz'. Within each of these there is an array
 * of course IDs. Within the course array there is an array of course steps.
 *
 * array(
 *   [course] => array (
 *      [123] => array(
 *         [completed] => 0
 *         [total] => 6
 *         [lessons] => array(
 *            [111] => 1
 *            [222] => 1
 *         )
 *         [topics] => array (
 *            [111] => array (
 *               [555] => 1
 *               [666] => 1
 *            )
 *         )
 *      )
 *   )
 *   [quiz] => array (
 *      [123] => array (
 *         [888] => 1
 *         [999] => 1
 *      )
 *   )
 * )
 */
function learndash_process_user_course_progress_update( $user_id = 0, $user_progress = array() ) {
	$processed_course_ids = array();

	if ( empty( $user_id ) ) {
		return $processed_course_ids;
	}

	if ( ( isset( $user_progress['course'] ) ) && ( ! empty( $user_progress['course'] ) ) ) {
		$usermeta        = get_user_meta( $user_id, '_sfwd-course_progress', true );
		$course_progress = empty( $usermeta ) ? array() : $usermeta;

		$course_changed = false; // Simple flag to let us know we changed the quiz data so we can save it back to user meta.

		foreach ( $user_progress['course'] as $course_id => $course_data_new ) {

			$processed_course_ids[ intval( $course_id ) ] = intval( $course_id );

			if ( isset( $course_progress[ $course_id ] ) ) {
				$course_data_old = $course_progress[ $course_id ];
			} else {
				$course_data_old = array();
			}

			$course_data_new = learndash_course_item_to_activity_sync( $user_id, $course_id, $course_data_new, $course_data_old );

			$course_progress[ $course_id ] = $course_data_new;

			$course_changed = true;
		}

		if ( true === $course_changed ) {
			update_user_meta( $user_id, '_sfwd-course_progress', $course_progress );
		}
	}

	if ( ( isset( $user_progress['quiz'] ) ) && ( ! empty( $user_progress['quiz'] ) ) ) {

		$usermeta      = get_user_meta( $user_id, '_sfwd-quizzes', true );
		$quiz_progress = empty( $usermeta ) ? array() : $usermeta;
		$quiz_changed  = false; // Simple flag to let us know we changed the quiz data so we can save it back to user meta.

		foreach ( $user_progress['quiz'] as $course_id => $course_quiz_set ) {
			foreach ( $course_quiz_set as  $quiz_id => $quiz_new_status ) {
				$quiz_meta = get_post_meta( $quiz_id, '_sfwd-quiz', true );

				if ( ! empty( $quiz_meta ) ) {
					$quiz_old_status = ! learndash_is_quiz_notcomplete( $user_id, array( $quiz_id => 1 ), false, $course_id );

					// For Quiz if the admin marks a qiz complete we don't attempt to update an existing attempt for the user quiz.
					// Instead we add a new entry. LD doesn't care as it will take the complete one for calculations where needed.
					if ( (bool) true === (bool) $quiz_new_status ) {
						if ( (bool) true !== (bool) $quiz_old_status ) {

							if ( isset( $quiz_meta['sfwd-quiz_lesson'] ) ) {
								$lesson_id = absint( $quiz_meta['sfwd-quiz_lesson'] );
							} else {
								$lesson_id = 0;
							}

							if ( isset( $quiz_meta['sfwd-quiz_topic'] ) ) {
								$topic_id = absint( $quiz_meta['sfwd-quiz_topic'] );
							} else {
								$topic_id = 0;
							}

							// If the admin is marking the quiz complete AND the quiz is NOT already complete...
							// Then we add the minimal quiz data to the user profile.
							$quizdata = array(
								'quiz'                => $quiz_id,
								'score'               => 0,
								'count'               => 0,
								'question_show_count' => 0,
								'pass'                => true,
								'rank'                => '-',
								'time'                => time(),
								'pro_quizid'          => absint( $quiz_meta['sfwd-quiz_quiz_pro'] ),
								'course'              => $course_id,
								'lesson'              => $lesson_id,
								'topic'               => $topic_id,
								'points'              => 0,
								'total_points'        => 0,
								'percentage'          => 0,
								'timespent'           => 0,
								'has_graded'          => false,
								'statistic_ref_id'    => 0,
								'm_edit_by'           => get_current_user_id(), // Manual Edit By ID.
								'm_edit_time'         => time(), // Manual Edit timestamp.
							);

							$quiz_progress[] = $quizdata;

							if ( true === $quizdata['pass'] ) {
								$quizdata_pass = true;
							} else {
								$quizdata_pass = false;
							}

							// Then we add the quiz entry to the activity database.
							learndash_update_user_activity(
								array(
									'course_id'          => $course_id,
									'user_id'            => $user_id,
									'post_id'            => $quiz_id,
									'activity_type'      => 'quiz',
									'activity_action'    => 'insert',
									'activity_status'    => $quizdata_pass,
									'activity_started'   => $quizdata['time'],
									'activity_completed' => $quizdata['time'],
									'activity_meta'      => $quizdata,
								)
							);

							$quiz_changed = true;

							if ( ( isset( $quizdata['course'] ) ) && ( ! empty( $quizdata['course'] ) ) ) {
								$quizdata['course'] = get_post( $quizdata['course'] );
							}

							if ( ( isset( $quizdata['lesson'] ) ) && ( ! empty( $quizdata['lesson'] ) ) ) {
								$quizdata['lesson'] = get_post( $quizdata['lesson'] );
							}

							if ( ( isset( $quizdata['topic'] ) ) && ( ! empty( $quizdata['topic'] ) ) ) {
								$quizdata['topic'] = get_post( $quizdata['topic'] );
							}

							/**
							 * Fires after the quiz is marked as complete.
							 *
							 * @param array   $quizdata An array of quiz data.
							 * @param WP_User $user     WP_User object.
							 */
							do_action( 'learndash_quiz_completed', $quizdata, get_user_by( 'ID', $user_id ) );

						}
					} elseif ( true !== $quiz_new_status ) {
						// If we are un-setting a quiz ( changing from complete to incomplete). We need to do some complicated things...
						if ( true === $quiz_old_status ) {

							if ( ! empty( $quiz_progress ) ) {
								foreach ( $quiz_progress as $quiz_idx => $quiz_item ) {

									if ( ( $quiz_item['quiz'] == $quiz_id ) && ( true === $quiz_item['pass'] ) ) {
										$quiz_progress[ $quiz_idx ]['pass'] = false;

										// We need to update the activity database records for this quiz_id.
										$activity_query_args = array(
											'post_ids' => $quiz_id,
											'user_ids' => $user_id,
											'activity_type' => 'quiz',
										);
										$quiz_activity       = learndash_reports_get_activity( $activity_query_args );
										if ( ( isset( $quiz_activity['results'] ) ) && ( ! empty( $quiz_activity['results'] ) ) ) {
											foreach ( $quiz_activity['results'] as $result ) {
												if ( ( isset( $result->activity_meta['pass'] ) ) && ( true === $result->activity_meta['pass'] ) ) {

													// If the activity meta 'pass' element is set to true we want to update it to false.
													learndash_update_user_activity_meta( $result->activity_id, 'pass', false );

													// Also we need to update the 'activity_status' for this record.
													learndash_update_user_activity(
														array(
															'activity_id' => $result->activity_id,
															'course_id' => $course_id,
															'user_id' => $user_id,
															'post_id' => $quiz_id,
															'activity_type' => 'quiz',
															'activity_action' => 'update',
															'activity_status' => false,
														)
													);
												}
											}
										}

										$quiz_changed = true;
									}

									/**
									 * Remove the quiz lock.
									 *
									 * @since 2.3.1
									 */
									if ( ( isset( $quiz_item['pro_quizid'] ) ) && ( ! empty( $quiz_item['pro_quizid'] ) ) ) {
										learndash_remove_user_quiz_locks( $user_id, $quiz_item['quiz'] );
									}
								}
							}
						}
					}

					$processed_course_ids[ intval( $course_id ) ] = intval( $course_id );
				}
			}
		}

		if ( true === $quiz_changed ) {
			update_user_meta( $user_id, '_sfwd-quizzes', $quiz_progress );
		}
	}

	if ( ! empty( $processed_course_ids ) ) {
		foreach ( array_unique( $processed_course_ids ) as $course_id ) {
			learndash_process_mark_complete( $user_id, $course_id );
			learndash_update_group_course_user_progress( $course_id, $user_id, true );
		}
	}

	return $processed_course_ids;
}

/**
 * Syncs the course date with the user activity.
 *
 * We need to compare the new course item progress array to the existing one. Also, update the new activity DB table
 *
 * @since 2.3.0
 *
 * @param int   $user_id         Optional. The user ID related to this course entry. Default 0.
 * @param int   $course_id       Optional. The course ID related to this user course entry. Default 0.
 * @param array $course_data_new Optional. The new course data item. Default empty array.
 * @param array $course_data_old Optional. The old course data item. Default empty array.
 *
 * @return void|array
 */
function learndash_course_item_to_activity_sync( $user_id = 0, $course_id = 0, $course_data_new = array(), $course_data_old = array() ) {
	if ( ( empty( $user_id ) ) || ( empty( $course_id ) ) || ( empty( $course_data_new ) ) ) {
		return;
	}

	// If we don't have the old course data we can get it.
	if ( empty( $course_data_old ) ) {
		$user_course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );
		if ( isset( $user_course_progress[ $course_id ] ) ) {
			$course_data_old = $user_course_progress[ $course_id ];
		} else {
			$course_data_old = array();
		}
	}

	// First we loop over the new Course data lessons. We add any items not in the old array to the activity table.
	if ( ( isset( $course_data_new['lessons'] ) ) && ( ! empty( $course_data_new['lessons'] ) ) ) {
		foreach ( $course_data_new['lessons'] as $lesson_id => $lesson_complete ) {
			$lesson_complete = (bool) $lesson_complete;
			if ( ( ! isset( $course_data_old['lessons'][ $lesson_id ] ) ) || ( $lesson_complete !== (bool) $course_data_old['lessons'][ $lesson_id ] ) ) {
				$lesson_args = array(
					'course_id'     => $course_id,
					'user_id'       => $user_id,
					'post_id'       => $lesson_id,
					'activity_type' => 'lesson',
				);

				$lesson_activity = learndash_get_user_activity( $lesson_args );
				if ( ! $lesson_activity ) {
					if ( true === $lesson_complete ) {
						$lesson_args['activity_started']   = time();
						$lesson_args['activity_completed'] = time();
					} else {
						$lesson_args['activity_started']   = 0;
						$lesson_args['activity_completed'] = 0;
					}
				} else {
					if ( true === $lesson_complete ) {
						if ( empty( $lesson_activity->activity_started ) ) {
							$lesson_args['activity_started'] = time();
						}
						if ( empty( $lesson_activity->activity_completed ) ) {
							$lesson_args['activity_completed'] = time();
						}
					} else {
						$lesson_args['activity_started']   = 0;
						$lesson_args['activity_completed'] = 0;
					}
				}

				if ( true === $lesson_complete ) {
					$lesson_args['activity_status'] = true;
				} else {
					$lesson_args['activity_status'] = false;
				}
				learndash_update_user_activity( $lesson_args );
			}
		}
	}

	// Next we loop over the lesson topics. We add any new items not in the old array to the activity table.
	if ( ( isset( $course_data_new['topics'] ) ) && ( ! empty( $course_data_new['topics'] ) ) ) {
		foreach ( $course_data_new['topics'] as $lesson_id => $lesson_topics ) {
			if ( ! empty( $lesson_topics ) ) {
				foreach ( $lesson_topics as $topic_id => $topic_complete ) {
					$topic_complete = (bool) $topic_complete;
					if ( ( ! isset( $course_data_old['topics'][ $lesson_id ][ $topic_id ] ) ) || ( $topic_complete !== (bool) $course_data_old['topics'][ $lesson_id ][ $topic_id ] ) ) {

						$topic_args = array(
							'course_id'     => $course_id,
							'user_id'       => $user_id,
							'post_id'       => $topic_id,
							'activity_type' => 'topic',
						);

						$topic_activity = learndash_get_user_activity( $topic_args );
						if ( ! $topic_activity ) {
							if ( true === $topic_complete ) {
								$topic_args['activity_started']   = time();
								$topic_args['activity_completed'] = time();
							} else {
								$topic_args['activity_started']   = 0;
								$topic_args['activity_completed'] = 0;
							}
						} else {
							if ( true === $topic_complete ) {
								if ( empty( $topic_activity->activity_started ) ) {
									$topic_args['activity_started'] = time();
								}
								if ( empty( $topic_activity->activity_completed ) ) {
									$topic_args['activity_completed'] = time();
								}
							} else {
								$topic_args['activity_started']   = 0;
								$topic_args['activity_completed'] = 0;
							}
						}

						if ( true === $topic_complete ) {
							$topic_args['activity_status'] = true;
						} else {
							$topic_args['activity_status'] = false;
						}

						learndash_update_user_activity( $topic_args );
					}
				}
			}
		}
	}

	// Then we loop over the old course lessons. Here if the lesson is NOT in the new course lessons we need to change the 'activity_status' to false.
	if ( ( isset( $course_data_old['lessons'] ) ) && ( ! empty( $course_data_old['lessons'] ) ) ) {
		foreach ( $course_data_old['lessons'] as $lesson_id => $lesson_complete ) {
			if ( ! isset( $course_data_new['lessons'][ $lesson_id ] ) ) {
				learndash_update_user_activity(
					array(
						'course_id'          => $course_id,
						'user_id'            => $user_id,
						'post_id'            => $lesson_id,
						'activity_type'      => 'lesson',
						'activity_status'    => false,
						'activity_started'   => 0,
						'activity_completed' => 0,
						'activity_updated'   => 0,
					)
				);
			}
		}
	}

	// Then we loop over the old course topics. Here if the lesson is NOT in the new course topics we need to change the 'activity_status' to false.
	if ( ( isset( $course_data_old['topics'] ) ) && ( ! empty( $course_data_old['topics'] ) ) ) {
		foreach ( $course_data_old['topics'] as $lesson_id => $lesson_topics ) {
			if ( ! empty( $lesson_topics ) ) {
				foreach ( $lesson_topics as $topic_id => $topic_complete ) {
					if ( ! isset( $course_data_new['topics'][ $lesson_id ][ $topic_id ] ) ) {
						learndash_update_user_activity(
							array(
								'course_id'          => $course_id,
								'user_id'            => $user_id,
								'post_id'            => $topic_id,
								'activity_type'      => 'topic',
								'activity_status'    => false,
								'activity_started'   => 0,
								'activity_completed' => 0,
								'activity_updated'   => 0,
							)
						);
					}
				}
			}
		}
	}

	// Finally we recalculate the course completed steps from the new course data.
	$completed_steps = (int) learndash_course_get_completed_steps( $user_id, $course_id, $course_data_new );
	if ( ( ! isset( $course_data_new['completed'] ) ) || ( $completed_steps != $course_data_new['completed'] ) ) {
		$course_args = array(
			'course_id'     => $course_id,
			'user_id'       => $user_id,
			'post_id'       => $course_id,
			'activity_type' => 'course',
		);

		if ( empty( $completed_steps ) ) {
			$course_args['activity_status']    = false;
			$course_args['activity_started']   = 0;
			$course_args['activity_completed'] = 0;
			$course_args['activity_updated']   = 0;
		} else {
			$course_activity = (array) learndash_get_user_activity( $course_args );
			if ( ! isset( $course_activity['activity_id'] ) ) {
				$course_args['activity_status']    = false;
				$course_args['activity_started']   = learndash_activity_course_get_earliest_started( $user_id, $course_id, time() );
				$course_args['activity_completed'] = 0;
			} else {
				$course_args = $course_activity;
			}
			if ( empty( $course_args['activity_started'] ) ) {
				$course_args['activity_started'] = learndash_activity_course_get_earliest_started( $user_id, $course_id, time() );
			}
		}

		$course_args['activity_meta'] = array(
			'steps_completed' => $completed_steps,
		);

		learndash_update_user_activity( $course_args );
	}

	// Then return the new course data to the caller.
	return $course_data_new;
}

/**
 * Mark all course steps as complete for a user.
 *
 * @since 4.0.0
 *
 * @param int $user_id User ID.
 * @param int $course_id Course ID.
 *
 * @return bool true if course steps marked as complete, false if not.
 */
function learndash_user_course_complete_all_steps( $user_id = 0, $course_id = 0 ) {
	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );

	if ( ( empty( $user_id ) ) || ( empty( $course_id ) ) ) {
		return false;
	}

	// User must have access to the course.
	$course_access = sfwd_lms_has_access( $course_id, $user_id );
	if ( true !== $course_access ) {
		return;
	}

	// If the course is already complete.
	if ( learndash_course_completed( $user_id, $course_id ) ) {
		return;
	}

	$course_progress_object = LDLMS_Factory_User::course_progress( $user_id );
	if ( ! $course_progress_object ) {
		return false;
	}

	$course_progress_steps_legacy = $course_progress_object->get_progress( $course_id, 'legacy' );

	if ( isset( $course_progress_steps_legacy['lessons'] ) ) {
		foreach ( $course_progress_steps_legacy['lessons'] as $lesson_id => &$lesson_completed ) {
			$lesson_completed = 1;
		}
	}

	if ( isset( $course_progress_steps_legacy['topics'] ) ) {
		foreach ( $course_progress_steps_legacy['topics'] as $lesson_id => &$lesson_topics ) {
			foreach ( $lesson_topics as $topic_id => &$topic_complete ) {
				$topic_complete = 1;
			}
		}
	}

	$quiz_progress = array();

	$course_progress_steps_co = $course_progress_object->get_progress( $course_id, 'co' );
	foreach ( $course_progress_steps_co as $key => $value ) {
		list( $step_type, $step_id ) = explode( ':', $key );
		if ( learndash_get_post_type_slug( 'quiz' ) === $step_type ) {
			$quiz_progress[ absint( $step_id ) ] = 1;
		}
	}

	$user_progress         = array(
		'course' => array(
			$course_id => $course_progress_steps_legacy,
		),
		'quiz'   => array(
			$course_id => $quiz_progress,
		),
	);
	$changed_courses_count = learndash_process_user_course_progress_update( $user_id, $user_progress );
	if ( empty( $changed_courses_count ) ) {
		return false;
	}

	return true;
}

/**
 * Gets the list of course IDs accessible by the user.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.3.0
 *
 * @param int $user_id Optional. The ID of the user to get course list. Default 0.
 *
 * @return array An array of course IDs.
 */
function learndash_get_user_course_access_list( $user_id = 0 ) {
	global $wpdb;
	$user_course_ids = array();

	$user_id = intval( $user_id );
	if ( ! empty( $user_id ) ) {
		if ( true === learndash_use_legacy_course_access_list() ) {
			$data_settings_courses = learndash_data_upgrades_setting( 'course-access-lists' );
			if ( ( isset( $data_settings_courses['version'] ) ) && ( version_compare( $data_settings_courses['version'], LEARNDASH_SETTINGS_TRIGGER_UPGRADE_VERSION, '>=' ) ) ) {

				$is_like = " postmeta.meta_value = '" . $user_id . "'
					OR postmeta.meta_value REGEXP '^" . $user_id . ",'
					OR postmeta.meta_value REGEXP '," . $user_id . ",'
					OR postmeta.meta_value REGEXP  '," . $user_id . "$'";

				$sql_str = 'SELECT post_id FROM ' . $wpdb->prefix . 'postmeta as postmeta INNER JOIN ' . $wpdb->prefix . "posts as posts ON posts.ID = postmeta.post_id WHERE posts.post_status='publish' AND posts.post_type='sfwd-courses' AND postmeta.meta_key='course_access_list' AND (" . $is_like . ')';
			} else {
				// OR the access list is not empty.
				$not_like = " postmeta.meta_value NOT REGEXP '\"sfwd-courses_course_access_list\";s:0:\"\";' ";

				// OR the user ID is found in the access list. Note this pattern is four options
				// 1. The user ID is the only entry.
				// 1a. The single entry could be an int
				// 1b. Ot the single entry could be an string
				// 2. The user ID is at the front of the list as in "sfwd-courses_course_access_list";*:"X,*";
				// 3. The user ID is in middle "sfwd-courses_course_access_list";*:"*,X,*";
				// 4. The user ID is at the end "sfwd-courses_course_access_list";*:"*,X";.
				$is_like = "
					postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";i:" . $user_id . ";s:34:\"sfwd-courses_course_lesson_orderby\"'
					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";i:" . $user_id . ";s:40:\"sfwd-courses_course_prerequisite_compare\"'
					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";i:" . $user_id . ";s:35:\"sfwd-courses_course_lesson_per_page\"'

					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"" . $user_id . "\";s:34:\"sfwd-courses_course_lesson_orderby\"'
					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"" . $user_id . "\";s:40:\"sfwd-courses_course_prerequisite_compare\"'
					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"" . $user_id . "\";s:35:\"sfwd-courses_course_lesson_per_page\"'

					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"" . $user_id . ",(.*)\";s:34:\"sfwd-courses_course_lesson_orderby\"'
					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"" . $user_id . ",(.*)\";s:40:\"sfwd-courses_course_prerequisite_compare\"'
					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"" . $user_id . ",(.*)\";s:35:\"sfwd-courses_course_lesson_per_page\"'

					OR postmeta.meta_value REGEXP  's:31:\"sfwd-courses_course_access_list\";s:(.*):\"(.*)," . $user_id . ",(.*)\";s:34:\"sfwd-courses_course_lesson_orderby\"'
					OR postmeta.meta_value REGEXP  's:31:\"sfwd-courses_course_access_list\";s:(.*):\"(.*)," . $user_id . ",(.*)\";s:40:\"sfwd-courses_course_prerequisite_compare\"'
					OR postmeta.meta_value REGEXP  's:31:\"sfwd-courses_course_access_list\";s:(.*):\"(.*)," . $user_id . ",(.*)\";s:35:\"sfwd-courses_course_lesson_per_page\"'

					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"(.*)," . $user_id . "\";s:34:\"sfwd-courses_course_lesson_orderby\"'
					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"(.*)," . $user_id . "\";s:40:\"sfwd-courses_course_prerequisite_compare\"'
					OR postmeta.meta_value REGEXP 's:31:\"sfwd-courses_course_access_list\";s:(.*):\"(.*)," . $user_id . "\";s:35:\"sfwd-courses_course_lesson_per_page\"'
					";

				$sql_str = 'SELECT post_id FROM ' . $wpdb->postmeta . ' as postmeta INNER JOIN ' . $wpdb->posts . " as posts ON posts.ID = postmeta.post_id WHERE posts.post_status='publish' AND posts.post_type='sfwd-courses' AND postmeta.meta_key='_sfwd-courses' AND ( " . $not_like . ' AND (' . $is_like . '))';
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$user_course_ids = $wpdb->get_col( $sql_str ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$user_course_ids = learndash_user_get_enrolled_courses( $user_id );
		}
	}
	return $user_course_ids;
}

/**
 * Gets all courses accessible by the user's groups.
 *
 * @since 2.3.0
 *
 * @param int $user_id Optional. User ID. Default 0.
 *
 * @return array An array of course IDs.
 */
function learndash_get_user_groups_courses_ids( $user_id = 0 ) {
	$user_group_course_ids = array();

	if ( empty( $user_id ) ) {
		return $user_group_course_ids;
	}

	// Next we grab all the groups the user is a member of.
	$users_group_ids = learndash_get_users_group_ids( $user_id );

	if ( ! empty( $users_group_ids ) ) {
		foreach ( $users_group_ids as $group_id ) {
			$group_course_ids = learndash_group_enrolled_courses( $group_id );
			if ( ! empty( $group_course_ids ) ) {
				$user_group_course_ids = array_merge( $user_group_course_ids, $group_course_ids );
			}
		}
	}

	/**
	 * Filters the list of user group courses.
	 *
	 * @param array $user_group_course_ids An array of found user group courses.
	 * @param int   $user_id               User ID.
	 */
	return apply_filters( 'learndash_get_user_groups_courses_ids', $user_group_course_ids, $user_id );
}


/**
 * Records the last login time for the user.
 *
 * Fires on `wp_login` hook.
 *
 * @since 2.3.0
 *
 * @param string         $user_login Optional. Username. Default empty.
 * @param WP_User|string $user       Optional. The `WP_User` object of the logged-in user. Default empty.
 */
function learndash_wp_login( $user_login = '', $user = '' ) {
	if ( ! empty( $user_login ) ) {
		if ( ! ( $user instanceof WP_User ) ) {
			$user = get_user_by( 'login', $user_login );
		}

		if ( $user instanceof WP_User ) {
			update_user_meta( $user->ID, 'learndash-last-login', time() );
		}
	}
}
add_action( 'wp_login', 'learndash_wp_login', 99, 1 );


/**
 * Removes the lock from a quiz for a user.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.3.1
 *
 * @param int $user_id Optional. The User ID. Default 0.
 * @param int $quiz_id Optional. The Quiz post ID. Default 0.
 */
function learndash_remove_user_quiz_locks( $user_id = 0, $quiz_id = 0 ) {
	global $wpdb;

	if ( ( ! empty( $user_id ) ) && ( ! empty( $quiz_id ) ) ) {
		$pro_quiz_id = get_post_meta( $quiz_id, 'quiz_pro_id', true );
		if ( ! empty( $pro_quiz_id ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					'DELETE FROM ' . esc_sql( LDLMS_DB::get_table_name( 'quiz_lock' ) ) . ' WHERE quiz_id = %d AND user_id = %s',
					$pro_quiz_id,
					$user_id
				)
			);
		}
	}
}


/**
 * Gets the course points for a user.
 *
 * The course points calculation is based on all completed courses by the user. From
 * these completed courses we get any with assigned course points into a total.
 * Then we add the optional 'course_points' user meta value if present. This is a value the
 * admin can set to help increase the student's point total.
 *
 * The calculated course points plus user meta course points are added together and returned.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.4.0
 *
 * @param int $user_id Optional. User ID. Default 0.
 *
 * @return mixed User course points.
 */
function learndash_get_user_course_points( $user_id = 0 ) {
	global $wpdb;

	if ( empty( $user_id ) ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user_id = get_current_user_id();
	}

	$user_id = intval( $user_id );
	if ( ! empty( $user_id ) ) {

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$course_points_results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT DISTINCT postmeta.post_id as post_id, postmeta.meta_value as points
				FROM ' . $wpdb->postmeta . " as postmeta
				WHERE postmeta.post_id IN
				(
					SELECT DISTINCT REPLACE(user_meta.meta_key, 'course_completed_', '') as course_id
					FROM " . $wpdb->usermeta . " as user_meta
					WHERE user_meta.meta_key LIKE %s
						AND user_meta.user_id = %d and user_meta.meta_value != ''
				)
				AND postmeta.meta_key=%s
				AND postmeta.meta_value != ''",
				'course_completed_%',
				$user_id,
				'course_points'
			)
		);

		$course_points_sum = 0;
		if ( ! empty( $course_points_results ) ) {
			foreach ( $course_points_results as $course_points ) {
				$course_points_sum += learndash_format_course_points( $course_points->points );
			}
		}

		$user_course_points = get_user_meta( $user_id, 'course_points', true );
		$user_course_points = learndash_format_course_points( $user_course_points );

		return learndash_format_course_points( (string) ( $course_points_sum + $user_course_points ) );
	}
}

/**
 * Gets the quiz statistic ID for a quiz attempt.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int   $user_id      Optional. Quiz ID. Default 0.
 * @param array $quiz_attempt Optional. An array of quiz attempt data. Default empty array.
 *
 * @return int The quiz statistic reference ID.
 */
function learndash_get_quiz_statistics_ref_for_quiz_attempt( $user_id = 0, $quiz_attempt = array() ) {
	global $wpdb;

	if ( empty( $user_id ) ) {
		return 0;
	}

	if ( ( ! isset( $quiz_attempt['pro_quizid'] ) ) || ( empty( $quiz_attempt['pro_quizid'] ) ) ) {
		return 0;
	}

	if ( ( ! isset( $quiz_attempt['time'] ) ) || ( empty( $quiz_attempt['time'] ) ) ) {
		return 0;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$ref_id = $wpdb->get_var(
		$wpdb->prepare(
			'SELECT statistic_ref_id FROM ' . esc_sql( LDLMS_DB::get_table_name( 'quiz_statistic_ref' ) ) . ' as stat
			INNER JOIN ' . esc_sql( LDLMS_DB::get_table_name( 'quiz_master' ) ) . ' as master ON stat.quiz_id=master.id
			WHERE  user_id = %d AND quiz_id = %d AND create_time = %d AND master.statistics_on=1 LIMIT 1',
			$user_id,
			$quiz_attempt['pro_quizid'],
			$quiz_attempt['time']
		)
	);
	return $ref_id;
}

/**
 * Gets the available fields for `usermeta` shortcode.
 *
 * @since 2.4.0
 *
 * @param array $attr Optional. An array of attributes to provide context for filter. Default empty array.
 *
 * @return array An array of available usermeta fields.
 */
function learndash_get_usermeta_shortcode_available_fields( $attr = array() ) {

	/**
	 * Filters the `usermeta` shortcode available fields.
	 *
	 * Added logic to allow protect certain user meta fields. The default
	 * fields is based on some of the fields returned via get_userdata().
	 *
	 * @since 2.4.0
	 *
	 * @param array $available_fields An array of available shortcode fields.
	 * @param array $attributes      An array of attributes to provide context for the filter.
	 */
	return apply_filters(
		'learndash_usermeta_shortcode_available_fields',
		array(
			'user_login'      => esc_html__( 'User Login', 'learndash' ),
			'first_name'      => esc_html__( 'User First Name', 'learndash' ),
			'last_name'       => esc_html__( 'User Last Name', 'learndash' ),
			'first_last_name' => esc_html__( 'User First and Last Name', 'learndash' ),
			'display_name'    => esc_html__( 'User Display Name', 'learndash' ),
			'user_nicename'   => esc_html__( 'User Nicename', 'learndash' ),
			'nickname'        => esc_html__( 'User Nickname', 'learndash' ),
			'user_email'      => esc_html__( 'User Email', 'learndash' ),
			'user_url'        => esc_html__( 'User URL', 'learndash' ),
			'description'     => esc_html__( 'User Description', 'learndash' ),
		),
		$attr
	);
}

/**
 * Utility function to return the admin user Courses capabilities.
 *
 * @since 3.2.3.5
 *
 * @return array of role capabilities.
 */
function learndash_get_admin_courses_capabilities(): array {
	return array(
		'read_post'              => 'read_course',
		'publish_posts'          => 'publish_courses',
		'edit_posts'             => 'edit_courses',
		'edit_others_posts'      => 'edit_others_courses',
		'delete_posts'           => 'delete_courses',
		'delete_others_posts'    => 'delete_others_courses',
		'read_private_posts'     => 'read_private_courses',
		'edit_private_posts'     => 'edit_private_courses',
		'delete_private_posts'   => 'delete_private_courses',
		'delete_post'            => 'delete_course',
		'edit_published_posts'   => 'edit_published_courses',
		'delete_published_posts' => 'delete_published_courses',
	);
}

/**
 * Initialize the admin user Courses capabilities.
 *
 * @since 3.2.3.5
 *
 * @return void
 */
function learndash_init_admin_courses_capabilities(): void {
	$admin_role = get_role( 'administrator' );

	if ( is_null( $admin_role ) ) {
		return;
	}

	// Not sure why this is here.
	if ( ! $admin_role->has_cap( 'enroll_users' ) ) {
		$admin_role->add_cap( 'enroll_users' );
	}

	foreach ( learndash_get_admin_courses_capabilities() as $capability ) {
		if ( ! $admin_role->has_cap( $capability ) ) {
			$admin_role->add_cap( $capability );
		}
	}
}

/**
 * Utility function to return the admin user Groups capabilities.
 *
 * @since 3.2.3.5
 *
 * @return array of role capabilities.
 */
function learndash_get_admin_groups_capabilities(): array {
	return array(
		'read_post'              => 'read_group',
		'publish_posts'          => 'publish_groups',
		'edit_posts'             => 'edit_groups',
		'edit_post'              => 'edit_group',
		'edit_others_posts'      => 'edit_others_groups',
		'delete_posts'           => 'delete_groups',
		'delete_others_posts'    => 'delete_others_groups',
		'read_private_posts'     => 'read_private_groups',
		'delete_post'            => 'delete_group',
		'edit_published_posts'   => 'edit_published_groups',
		'delete_published_posts' => 'delete_published_groups',
	);
}

/**
 * Initialize the admin user Groups capabilities.
 *
 * @since 3.2.3.5
 *
 * @return void
 */
function learndash_init_admin_groups_capabilities(): void {
	$admin_role = get_role( 'administrator' );

	if ( is_null( $admin_role ) ) {
		return;
	}

	foreach ( learndash_get_admin_groups_capabilities() as $capability ) {
		if ( ! $admin_role->has_cap( $capability ) ) {
			$admin_role->add_cap( $capability );
		}
	}
}

/**
 * Utility function to return the admin user Coupons capabilities.
 *
 * @since 4.1.0
 *
 * @return array of role capabilities.
 */
function learndash_get_admin_coupons_capabilities(): array {
	return array(
		'read_post'              => 'read_coupon',
		'publish_posts'          => 'publish_coupons',
		'edit_posts'             => 'edit_coupons',
		'edit_post'              => 'edit_coupon',
		'edit_others_posts'      => 'edit_others_coupons',
		'delete_posts'           => 'delete_coupons',
		'delete_others_posts'    => 'delete_others_coupons',
		'read_private_posts'     => 'read_private_coupons',
		'delete_post'            => 'delete_coupon',
		'edit_published_posts'   => 'edit_published_coupons',
		'delete_published_posts' => 'delete_published_coupons',
	);
}

/**
 * Initialize the admin user Coupons capabilities.
 *
 * @since 4.1.0
 *
 * @return void
 */
function learndash_init_admin_coupons_capabilities(): void {
	$admin_role = get_role( 'administrator' );

	if ( is_null( $admin_role ) ) {
		return;
	}

	foreach ( learndash_get_admin_coupons_capabilities() as $capability ) {
		if ( ! $admin_role->has_cap( $capability ) ) {
			$admin_role->add_cap( $capability );
		}
	}
}

add_action( 'admin_init', 'learndash_init_admin_coupons_capabilities' );

/**
 * Gets all expired courses for the user via the user meta 'learndash_course_expired_XXX'.
 *
 * @since 3.4.2
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int $user_id Optional. ID of the user to get meta. Default 0.
 *
 * @return array An array of user's expired course IDs.
 */
function learndash_get_expired_user_courses_from_meta( $user_id = 0 ) {
	global $wpdb;

	$expired_user_course_ids = array();

	$user_id = intval( $user_id );
	if ( ! empty( $user_id ) ) {

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$expired_user_course_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT REPLACE(meta_key, 'learndash_course_expired_', '') FROM " . $wpdb->usermeta . ' as usermeta WHERE user_id=%d AND meta_key LIKE %s ',
				$user_id,
				'learndash_course_expired_%'
			)
		);
		if ( ! empty( $expired_user_course_ids ) ) {
			$expired_user_course_ids = array_map( 'intval', $expired_user_course_ids );
		}
	}
	return $expired_user_course_ids;
}

/**
 * Utility function to check if users can register for the site.
 *
 * @since 3.6.0
 */
function learndash_users_can_register() {
	if ( is_multisite() ) {
		$users_can_register = (bool) users_can_register_signup_filter();
	} else {
		$users_can_register = (bool) get_option( 'users_can_register' );
	}

	/**
	 * Filter for users can register.
	 *
	 * @since 3.6.0
	 * @param bool $users_can_register True if users can register.
	 */
	return (bool) apply_filters( 'learndash_users_can_register', $users_can_register );
}
