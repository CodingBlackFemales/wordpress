<?php
/**
 * Activity Functions
 *
 * @since 3.4.0
 *
 * @package LearnDash\Activity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Updates the user activity.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array $args {
 *    An array of user activity arguments. Default empty array.
 *
 *    @type int    $activity_id        Optional. Activity ID. Default 0.
 *    @type int    $course_id          Optional. Course ID. Default 0.
 *    @type int    $post_id            Optional. Post ID. Default 0.
 *    @type int    $user_id            Optional. User ID. Default 0.
 *    @type string $activity_type      Optional. Type of the activity. Default empty.
 *    @type string $activity_status    Optional. The status of the activity. Default empty.
 *    @type string $activity_started   Optional. The timestamp of when the activity started. Default empty.
 *    @type string $activity_completed Optional. The timestamp of when the activity got completed. Default empty.
 *    @type string $activity_updated   Optional. The timestamp of when the activity was last updated. Default empty.
 *    @type string $activity_action    Optional. The action of the activity. Value can be 'update' or 'insert'. Default 'update'.
 *    @type string $activity_meta      Optional. The activity meta. Default empty.
 * }
 *
 * @return int The ID of the updated activity.
 */
function learndash_update_user_activity( $args = array() ) {

	global $wpdb;

	if ( is_object( $args ) ) {
		$args = json_decode( wp_json_encode( $args ), true );
	}

	$default_args = array(
		// Can be passed in if we are updating a specific existing activity row.
		'activity_id'        => 0,

		// Required. This is the ID of the Course. Unique key part 1/4.
		'course_id'          => 0,

		// Required. This is the ID of the Course, Lesson, Topic, Quiz item. Unique key part 2/4.
		'post_id'            => 0,

		// Optional. Will use get_current_user_id() if left 0. Unique key part 3/4.
		'user_id'            => 0,

		// Will be the token stats that described the status_times array (next argument) Can be most anything.
		// From 'course', 'lesson', 'topic', 'access' or 'expired'. Unique key part 4/4.
		'activity_type'      => '',

		// true if the lesson, topic, course, quiz is complete. False if not complete. null if not started.
		'activity_status'    => '',

		// Should be the timestamp when the 'status' started.
		'activity_started'   => '',

		// Should be the timestamp when the 'status' completed.
		'activity_completed' => '',

		// Should be the timestamp when the activity record was last updated. Used as a sort column for ProPanel and other queries.
		'activity_updated'   => '',

		// Flag to indicate what we are 'update', 'insert', 'delete'. The default action 'update' will cause this function
		// to check for an existing record to update (if found).
		'activity_action'    => 'update',
		'activity_meta'      => '',
	);

	$args = wp_parse_args( $args, $default_args );
	if ( empty( $args['activity_id'] ) ) {
		if ( ( empty( $args['post_id'] ) ) || ( empty( $args['activity_type'] ) ) ) {
			return;
		}
	}

	if ( empty( $args['user_id'] ) ) {
		// If we don't have a user_id passed via args.
		if ( ! is_user_logged_in() ) {
			return; // If not logged in, abort.
		}

		// Else use the logged in user ID as the args user_id.
		$args['user_id'] = get_current_user_id();
	}

	// End of args processing. Finally after we have applied all the logic we go out for filters.
	/**
	 * Filters user activity arguments.
	 *
	 * @param array $args An array of user activity arguments.
	 */
	$args = apply_filters( 'learndash_update_user_activity_args', $args );
	if ( empty( $args ) ) {
		return;
	}

	$values_array = array(
		'user_id'       => $args['user_id'],
		'course_id'     => $args['course_id'],
		'post_id'       => $args['post_id'],
		'activity_type' => $args['activity_type'],
	);

	$types_array = array(
		'%d', // user_id.
		'%d', // course_id.
		'%d', // post_id.
		'%s', // activity_type.
	);

	if ( ( true === (bool) $args['activity_status'] ) || ( false === (bool) $args['activity_status'] ) ) {
		$values_array['activity_status'] = $args['activity_status'];
		$types_array[]                   = '%d';
	}

	if ( '' !== $args['activity_completed'] ) {
		$values_array['activity_completed'] = $args['activity_completed'];
		$types_array[]                      = '%d';
	}

	if ( '' !== $args['activity_started'] ) {
		$values_array['activity_started'] = $args['activity_started'];
		$types_array[]                    = '%d';
	}

	if ( '' !== $args['activity_updated'] ) {
		$values_array['activity_updated'] = $args['activity_updated'];
		$types_array[]                    = '%d';
	} else {
		if ( ( empty( $args['activity_started'] ) ) && ( empty( $args['activity_completed'] ) ) ) {
			if ( ! isset( $args['data_upgrade'] ) ) {
				$values_array['activity_updated'] = time();
				$types_array[]                    = '%d';
			}
		} elseif ( $args['activity_started'] == $args['activity_completed'] ) {
			$values_array['activity_updated'] = $args['activity_completed'];
			$types_array[]                    = '%d';
		} else {
			if ( $args['activity_started'] > $args['activity_completed'] ) {
				$values_array['activity_updated'] = $args['activity_started'];
				$types_array[]                    = '%d';
			} elseif ( $args['activity_completed'] > $args['activity_started'] ) {
				$values_array['activity_updated'] = $args['activity_completed'];
				$types_array[]                    = '%d';
			}
		}
	}

	$update_ret = false;

	if ( 'update' === $args['activity_action'] ) {

		if ( empty( $args['activity_id'] ) ) {
			$activity = learndash_get_user_activity( $args );
			if ( null !== $activity ) {

				$args['activity_id'] = $activity->activity_id;
			}
		}

		if ( ! empty( $args['activity_id'] ) ) {

			$update_values_array = $values_array;
			$update_types_array  = $types_array;

			$update_ret = $wpdb->update(
				LDLMS_DB::get_table_name( 'user_activity' ),
				$update_values_array,
				array(
					'activity_id' => $args['activity_id'],
				),
				$update_types_array,
				array(
					'%d', // activity_id.
				)
			);

		} else {
			$args['activity_action'] = 'insert';
		}
	}

	if ( 'insert' === $args['activity_action'] ) {

		$values_array['activity_updated'] = time();
		$types_array[]                    = '%d';

		$insert_ret = $wpdb->insert(
			LDLMS_DB::get_table_name( 'user_activity' ),
			$values_array,
			$types_array
		);

		if ( false !== (bool) $insert_ret ) {
			$args['activity_id'] = $wpdb->insert_id;
		}
	}

	// Finally for the course we update the activity meta.
	if ( ( ! empty( $args['activity_id'] ) ) && ( ! empty( $args['activity_meta'] ) ) ) {
		foreach ( $args['activity_meta'] as $meta_key => $meta_value ) {
			learndash_update_user_activity_meta( $args['activity_id'], $meta_key, $meta_value );
		}
	}

	/**
	 * Fires after updating user activity.
	 *
	 * @param array $args An array of activity arguments.
	 */
	do_action( 'learndash_update_user_activity', $args );

	return $args['activity_id'];
}

/**
 * Gets the user activity.
 *
 * @since 2.3.0
 * @since 3.5.0 Added `$activity_create` param.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array $args {
 *    An array of user activity arguments. Default empty array.
 *
 *    @type int    $course_id     Optional. Course ID. Default 0.
 *    @type string $activity_type Type of the activity.
 * }
 * @param bool  $activity_create It true will create to activity record.
 *
 * @return object|array|null Activity object (LDLMS_Activity) or null if not found.
 */
function learndash_get_user_activity( $args = array(), $activity_create = false ) {
	global $wpdb;

	if ( ! isset( $args['course_id'] ) ) {
		$args['course_id'] = 0;
	}

	if ( ( ! isset( $args['activity_type'] ) ) || ( empty( $args['activity_type'] ) ) ) {
		if ( ( isset( $args['post_id'] ) ) && ( ! empty( $args['post_id'] ) ) ) {
			$post_type = get_post_type( $args['post_id'] );
			if ( learndash_is_valid_post_type( $post_type ) ) {
				$args['activity_type'] = learndash_get_post_type_key( $post_type );
			}
		}
	}

	if ( 'quiz' === $args['activity_type'] ) {
		$data_settings_quizzes = learndash_data_upgrades_setting( 'user-meta-quizzes' );
		if ( ! isset( $args['activity_completed'] ) ) {
			$args['activity_completed'] = 0;
		}
		if ( version_compare( $data_settings_quizzes['version'], '2.5', '>=' ) ) {
			$sql_str = $wpdb->prepare( 'SELECT * FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' WHERE user_id=%d AND course_id=%d AND post_id=%d AND activity_type=%s AND activity_completed=%d LIMIT 1', $args['user_id'], $args['course_id'], $args['post_id'], $args['activity_type'], $args['activity_completed'] );
		} else {
			$sql_str = $wpdb->prepare( 'SELECT * FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' WHERE user_id=%d AND post_id=%d AND activity_type=%s AND activity_completed=%d LIMIT 1', $args['user_id'], $args['post_id'], $args['activity_type'], $args['activity_completed'] );
		}
	} else {
		$data_settings_courses = learndash_data_upgrades_setting( 'user-meta-courses' );
		if ( version_compare( $data_settings_courses['version'], '2.5', '>=' ) ) {
			$sql_str = $wpdb->prepare( 'SELECT * FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' WHERE user_id=%d AND course_id=%d AND post_id=%d AND activity_type=%s LIMIT 1', $args['user_id'], $args['course_id'], $args['post_id'], $args['activity_type'] );
		} else {
			$sql_str = $wpdb->prepare( 'SELECT * FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' WHERE user_id=%d AND post_id=%d AND activity_type=%s LIMIT 1', $args['user_id'], $args['post_id'], $args['activity_type'] );
		}
	}
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql_str prepared in previous lines
	$activity = $wpdb->get_row( $sql_str ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	if ( ! $activity ) {
		if ( true === $activity_create ) {
			$activity_id = learndash_update_user_activity( $args );
			if ( ! empty( $activity_id ) ) {
				$activity = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						'SELECT * FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' WHERE activity_id=%d',
						$activity_id
					)
				);
			}
		}
	}

	if ( $activity ) {
		$activity = new LDLMS_Model_Activity( $activity );
	}

	/**
	 * Filter for learndash_get_user_activity.
	 *
	 * @since 3.2.3
	 *
	 * @param array $activity Object (LDLMS_Activity) of activity.
	 * @param array $args     Array of args used for activity query.
	 */
	$activity = apply_filters( 'learndash_get_user_activity', $activity, $args );

	return $activity;

}


/**
 * Gets the user activity meta.
 *
 * @since 2.3.0
 * @since 3.5.0 Added $return_activity_meta_value_unserialized param.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int     $activity_id                             Optional. Activity ID. Default 0.
 * @param string  $activity_meta_key                       Optional. The activity meta key to get. Default empty.
 * @param boolean $return_activity_meta_value_only         Optional. Whether to return only activity meta value. Default true.
 * @param boolean $return_activity_meta_value_unserialized Optional. Whether to unserialize the single meta value.
 *
 * @return object Activity meta object.
 */
function learndash_get_user_activity_meta( $activity_id = 0, $activity_meta_key = '', $return_activity_meta_value_only = true, $return_activity_meta_value_unserialized = false ) {

	global $wpdb;

	if ( empty( $activity_id ) ) {
		return;
	}

	if ( ! empty( $activity_meta_key ) ) {
		$activity_meta = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity_meta' ) ) . ' WHERE activity_id=%d AND activity_meta_key=%s',
				$activity_id,
				$activity_meta_key
			)
		);
		if ( ! empty( $activity_meta ) ) {
			if ( true === (bool) $return_activity_meta_value_only ) {
				if ( property_exists( $activity_meta, 'activity_meta_value' ) ) {
					if ( true === (bool) $return_activity_meta_value_unserialized ) {
						return maybe_unserialize( $activity_meta->activity_meta_value );
					} else {
						return $activity_meta->activity_meta_value;
					}
				}
			}
		}
		return $activity_meta;
	} else {
		// Here we return ALL meta for the given activity_id.
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity_meta' ) ) . ' WHERE activity_id=%d',
				$activity_id
			)
		);
	}
}

/**
 * Updates the user activity meta.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int         $activity_id Optional. Activity ID. Default 0.
 * @param string      $meta_key    Optional. The activity meta key to get. Default empty.
 * @param string|null $meta_value  Optional. Activity meta value. Default null.
 */
function learndash_update_user_activity_meta( $activity_id = 0, $meta_key = '', $meta_value = null ) {
	global $wpdb;

	if ( ( empty( $activity_id ) ) || ( empty( $meta_key ) ) || ( null === $meta_value ) ) {
		return;
	}

	$activity = learndash_get_user_activity_meta( $activity_id, $meta_key, false );
	if ( null !== $activity ) {
		$wpdb->update(
			LDLMS_DB::get_table_name( 'user_activity_meta' ),
			array(
				'activity_id'         => $activity_id,
				'activity_meta_key'   => $meta_key,
				'activity_meta_value' => maybe_serialize( $meta_value ),
			),
			array(
				'activity_meta_id' => $activity->activity_meta_id,
			),
			array(
				'%d',   // activity_id.
				'%s',   // meta_key.
				'%s',   // meta_value.
			),
			array(
				'%d',   // activity_meta_id.
			)
		);

	} else {
		$wpdb->insert(
			LDLMS_DB::get_table_name( 'user_activity_meta' ),
			array(
				'activity_id'         => $activity_id,
				'activity_meta_key'   => $meta_key,
				'activity_meta_value' => maybe_serialize( $meta_value ),
			),
			array(
				'%d',   // activity_id.
				'%s',   // meta_key.
				'%s',   // meta_value.
			)
		);
	}
}

/**
 * Delete the user activity meta.
 *
 * @since 3.5.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int    $activity_id Optional. Activity ID. Default 0.
 * @param string $meta_key    Optional. The activity meta key to delete. If empty will delete all.
 */
function learndash_delete_user_activity_meta( $activity_id = 0, $meta_key = '' ) {
	global $wpdb;

	if ( empty( $activity_id ) ) {
		return;
	}

	$activity = learndash_get_user_activity_meta( $activity_id, $meta_key, false );
	if ( null !== $activity ) {
		if ( is_object( $activity ) ) {
			$activity = array( $activity );
		}

		foreach ( $activity as $activity_item ) {
			$wpdb->delete(
				LDLMS_DB::get_table_name( 'user_activity_meta' ),
				array(
					'activity_meta_id' => $activity_item->activity_meta_id,
				),
				array(
					'%d',   // activity_meta_id.
				)
			);
		}
	}
}

/**
 * Deletes the user activity.
 *
 * @since 2.5.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int $activity_id Optional. Activity ID. Default 0.
 */
function learndash_delete_user_activity( $activity_id = 0 ) {
	global $wpdb;

	if ( ! empty( $activity_id ) ) {
		$wpdb->delete(
			LDLMS_DB::get_table_name( 'user_activity' ),
			array( 'activity_id' => $activity_id ),
			array( '%d' )
		);

		$wpdb->delete(
			LDLMS_DB::get_table_name( 'user_activity_meta' ),
			array( 'activity_id' => $activity_id ),
			array( '%d' )
		);
	}
}

/**
 * Get the earliest course activity record for the user.
 *
 * @since 3.5.0
 *
 * @param int $user_id          User ID.
 * @param int $course_id        Course ID.
 * @param int $default_started  Default value.
 */
function learndash_activity_course_get_earliest_started( $user_id = 0, $course_id = 0, $default_started = 0 ) {
	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );

	if ( ( ! empty( $user_id ) ) && ( ! empty( $course_id ) ) ) {
		$user_activity = learndash_user_get_course_progress( $user_id, $course_id, 'activity' );
		if ( ! empty( $user_activity ) ) {
			if ( isset( $user_activity[ learndash_get_post_type_slug( 'course' ) . ':' . $course_id ] ) ) {
				unset( $user_activity[ learndash_get_post_type_slug( 'course' ) . ':' . $course_id ] );
			}
			$activity_started = wp_list_pluck( $user_activity, 'activity_started' );
			$activity_started = array_map( 'absint', $activity_started ); // Give me only integers.
			$activity_started = array_diff( $activity_started, array( 0 ) ); // Remove empty values.
			if ( ! empty( $activity_started ) ) {
				sort( $activity_started );
				$default_started = (int) $activity_started[0];
			}
		}
	}

	return $default_started;
}

/**
 * Set the step activity started record.
 *
 * @since 3.5.0
 *
 * @param int    $user_id    User ID.
 * @param int    $course_id  Course ID.
 * @param int    $step_id    Course step ID.
 * @param string $type       Step type 'course', lesson', 'topic', 'quiz.
 * @param int    $start_time Activity start timestamp (GMT).
 *
 * @return object Instance of LDLMS_Model_Activity or null;
 */
function learndash_activity_start_step( $user_id = 0, $course_id = 0, $step_id = 0, $type = '', $start_time = 0 ) {
	if ( empty( $start_time ) ) {
		$start_time = time();
	}
	$args = array(
		'course_id'        => $course_id,
		'user_id'          => $user_id,
		'post_id'          => $step_id,
		'activity_type'    => $type,
		'activity_started' => $start_time,
	);
	return learndash_get_user_activity( $args, true );
}

/**
 * Set the step activity completed record.
 *
 * @since 3.5.0
 *
 * @param int    $user_id       User ID.
 * @param int    $course_id     Course ID.
 * @param int    $step_id       Course step ID.
 * @param string $type          Step type 'course', lesson', 'topic', 'quiz.
 * @param int    $complete_time Activity complete timestamp (GMT).
 *
 * @return object Instance of LDLMS_Model_Activity or null;
 */
function learndash_activity_complete_step( $user_id = 0, $course_id = 0, $step_id = 0, $type = '', $complete_time = 0 ) {
	if ( empty( $complete_time ) ) {
		$complete_time = time();
	}
	$args     = array(
		'course_id'     => $course_id,
		'user_id'       => $user_id,
		'post_id'       => $step_id,
		'activity_type' => $type,
	);
	$activity = learndash_get_user_activity( $args, true );
	if ( ! empty( $activity ) ) {
		$activity = json_decode( wp_json_encode( $activity ), true );
	} else {
		$activity = $args;
	}
	$activity['activity_status']    = true;
	$activity['activity_completed'] = $complete_time;
	$activity['activity_updated']   = $complete_time;

	if ( empty( $activity['activity_started'] ) ) {
		$activity['activity_started'] = $complete_time;
	}

	$activity_id = learndash_update_user_activity( $activity );
	if ( ! empty( $activity_id ) ) {
		return learndash_get_user_activity( $activity, true );
	}
	return null;
}

/**
 * Set the course activity started record.
 *
 * @since 3.5.0
 *
 * @param int  $user_id          User ID.
 * @param int  $course_id        Course ID.
 * @param int  $start_time       Activity start timestamp (GMT).
 * @param bool $force_start_time Force update on start time.
 *
 * @return object Instance of LDLMS_Model_Activity or null;
 */
function learndash_activity_start_course( $user_id = 0, $course_id = 0, $start_time = 0, $force_start_time = true ) {
	$activity = learndash_activity_start_step( $user_id, $course_id, $course_id, 'course', $start_time );
	if ( $activity ) {

		if ( ( ! empty( absint( $start_time ) ) ) && ( empty( absint( $activity->activity_started ) ) ) ) {
			$activity->activity_started = absint( $start_time );
			learndash_update_user_activity( $activity );
		}
	}

	return $activity;
}

/**
 * Set the course activity completed record.
 *
 * @since 3.5.0
 *
 * @param int $user_id       User ID.
 * @param int $course_id     Course ID.
 * @param int $complete_time Activity complete timestamp (GMT).
 *
 * @return object Instance of LDLMS_Model_Activity or null;
 */
function learndash_activity_complete_course( $user_id = 0, $course_id = 0, $complete_time = 0 ) {
	return learndash_activity_complete_step( $user_id, $course_id, $course_id, 'course', $complete_time );
}

/**
 * Set the lesson activity started record.
 *
 * @since 3.5.0
 *
 * @param int $user_id    User ID.
 * @param int $course_id  Course ID.
 * @param int $lesson_id  Lesson ID.
 * @param int $start_time Activity start timestamp (GMT).
 *
 * @return object Instance of LDLMS_Model_Activity or null;
 */
function learndash_activity_start_lesson( $user_id = 0, $course_id = 0, $lesson_id = 0, $start_time = 0 ) {
	return learndash_activity_start_step( $user_id, $course_id, $lesson_id, 'lesson', $start_time );
}

/**
 * Set the lesson activity completed record.
 *
 * @since 3.5.0
 *
 * @param int $user_id       User ID.
 * @param int $course_id     Course ID.
 * @param int $lesson_id     Lesson ID.
 * @param int $complete_time Activity complete timestamp (GMT).
 *
 * @return object Instance of LDLMS_Model_Activity or null;
 */
function learndash_activity_complete_lesson( $user_id = 0, $course_id = 0, $lesson_id = 0, $complete_time = 0 ) {
	return learndash_activity_complete_step( $user_id, $course_id, $lesson_id, 'lesson', $complete_time );
}

/**
 * Set the topic activity started record.
 *
 * @since 3.5.0
 *
 * @param int $user_id    User ID.
 * @param int $course_id  Course ID.
 * @param int $topic_id   Topic ID.
 * @param int $start_time Activity start timestamp (GMT).
 *
 * @return object Instance of LDLMS_Model_Activity or null;
 */
function learndash_activity_start_topic( $user_id = 0, $course_id = 0, $topic_id = 0, $start_time = 0 ) {
	return learndash_activity_start_step( $user_id, $course_id, $topic_id, 'topic', $start_time );
}

/**
 * Set the topic activity completed record.
 *
 * @since 3.5.0
 *
 * @param int $user_id       User ID.
 * @param int $course_id     Course ID.
 * @param int $topic_id      Topic ID.
 * @param int $complete_time Activity complete timestamp (GMT).
 *
 * @return object Instance of LDLMS_Model_Activity or null;
 */
function learndash_activity_complete_topic( $user_id = 0, $course_id = 0, $topic_id = 0, $complete_time = 0 ) {
	return learndash_activity_complete_step( $user_id, $course_id, $topic_id, 'topic', $complete_time );
}

/**
 * Set the lesson activity started record.
 *
 * @since 3.5.0
 *
 * @param int $user_id    User ID.
 * @param int $course_id  Course ID.
 * @param int $quiz_id    Quiz ID.
 * @param int $start_time Activity start timestamp (GMT).
 *
 * @return object Instance of LDLMS_Model_Activity or null;
 */
function learndash_activity_start_quiz( $user_id = 0, $course_id = 0, $quiz_id = 0, $start_time = 0 ) {
	return learndash_activity_start_step( $user_id, $course_id, $quiz_id, 'quiz', $start_time );
}

/**
 * Set the quiz activity completed record.
 *
 * @since 3.5.0
 *
 * @param int $user_id       User ID.
 * @param int $course_id     Course ID.
 * @param int $quiz_id       Quiz ID.
 * @param int $complete_time Activity complete timestamp (GMT).
 *
 * @return object Instance of LDLMS_Model_Activity or null;
 */
function learndash_activity_complete_quiz( $user_id = 0, $course_id = 0, $quiz_id = 0, $complete_time = 0 ) {
	return learndash_activity_complete_step( $user_id, $course_id, $quiz_id, 'quiz', $complete_time );
}

/**
 * Update activity meta set.
 *
 * This will update an array of activity meta record(s).
 *
 * @since 3.5.0
 *
 * @param int   $activity_id   Activity ID.
 * @param array $activity_meta Array of key/value meta data.
 */
function learndash_activity_update_meta_set( $activity_id = 0, $activity_meta = array() ) {
	if ( ( ! empty( $activity_id ) ) && ( is_array( $activity_meta ) ) ) {
		foreach ( $activity_meta as $meta_key => $meta_value ) {
			learndash_update_user_activity_meta( $activity_id, $meta_key, $meta_value );
		}
	}
}

/**
 * Get the latest completed course activity record for the user.
 *
 * @since 3.5.0
 *
 * @param int $user_id   User ID.
 * @param int $course_id Course ID.
 */
function learndash_activity_course_get_latest_completed_step( $user_id = 0, $course_id = 0 ) {
	global $wpdb;

	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );

	if ( ( ! empty( $user_id ) ) && ( ! empty( $course_id ) ) ) {
		$activity_item = $wpdb->get_row(
			$wpdb->prepare( 'SELECT post_id, activity_completed FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' WHERE course_id=%d AND user_id=%d AND activity_type IN ( "lesson", "topic", "quiz" ) AND activity_completed > 0 ORDER BY activity_completed DESC LIMIT 1', $course_id, $user_id ),
			ARRAY_A
		);

		return $activity_item;
	}
}
