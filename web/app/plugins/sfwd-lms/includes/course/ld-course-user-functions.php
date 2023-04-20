<?php
/**
 * Function that help the User Course Steps.
 *
 * @since 3.4.0
 *
 * @package LearnDash\User
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// cspell:ignore childen .

/**
 * Checks if the user has access to a course.
 *
 * @todo  duplicate function, exists in other places
 *        check it's use and consolidate
 *
 * @since 2.1.0
 *
 * @param int      $course_id Course ID.
 * @param int|null $user_id   Optional. User ID. Default null.
 *
 * @return boolean Returns true if the user has access otherwise false.
 */
function ld_course_check_user_access( $course_id, $user_id = null ) {
	return sfwd_lms_has_access( $course_id, $user_id );
}

/**
 * Gets the array of courses that can be accessed by the user.
 *
 * @since 2.1.0
 *
 * @param int|null $user_id User ID. Default null.
 * @param array    $atts {
 *    Optional. An array of attributes. Default empty array.
 *
 *    @type string $order   Optional. Designates ascending ('ASC') or descending ('DESC') order. Default 'DESC.
 *    @type string $orderby Optional. The name of the field to order posts by. Default ''ID.
 *    @type string $s       Optional. The search string. Default empty.
 * }
 *
 * @return array An array of courses accessible to user.
 */
function ld_get_mycourses( $user_id = null, $atts = array() ) {

	$defaults = array(
		'order'   => 'DESC',
		'orderby' => 'ID',
		's'       => '',
	);
	$atts     = wp_parse_args( $atts, $defaults );

	return learndash_user_get_enrolled_courses(
		$user_id,
		$atts,
		true
	);
}

/**
 * Checks whether a user has access to a course.
 *
 * @since 2.1.0
 *
 * @param int      $post_id ID of the resource.
 * @param int|null $user_id Optional. ID of the user. Default null.
 *
 * @return bool Returns true if the user has access.
 */
function sfwd_lms_has_access( $post_id, $user_id = null ) {

	/**
	 * Filters whether a user has access to the course.
	 *
	 * @since 2.1.0
	 *
	 * @param boolean $has_access Whether the user has access to the course or not.
	 * @param int     $post_id    Post ID.
	 * @param int     $user_id    User ID.
	 */
	return apply_filters( 'sfwd_lms_has_access', sfwd_lms_has_access_fn( $post_id, $user_id ), $post_id, $user_id );
}

/**
 * Checks whether a user has access to a course.
 *
 * @since 2.1.0
 *
 * @param int      $post_id ID of the resource.
 * @param int|null $user_id Optional. ID of the user. Default null.
 *
 * @return bool Returns true if the user has access.
 */
function sfwd_lms_has_access_fn( $post_id, $user_id = null ) {
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$course_id = learndash_get_course_id( $post_id );
	if ( empty( $course_id ) ) {
		return true;
	}

	$status = get_post_status( $course_id );
	if ( false === $status && ! empty( $course_id ) ) {
		return false;
	}

	if ( ! empty( $user_id ) ) {
		if ( learndash_can_user_autoenroll_courses( $user_id ) ) {
			return true;
		}
	}

	if ( ! empty( $post_id ) && learndash_is_sample( $post_id ) ) {
		return true;
	}

	$meta = learndash_get_setting( $course_id );

	if ( ( isset( $meta['course_price_type'] ) ) && ( $meta['course_price_type'] === 'open' ) ) {
		return true;
	}

	if ( ( isset( $meta['course_price_type'] ) ) && ( $meta['course_price_type'] === 'paynow' ) ) {
		// Allow for the course price field to be empty or not present.
		if ( ! isset( $meta['course_price'] ) || ( empty( $meta['course_price'] ) ) ) {
			return true;
		}
	}

	if ( ( isset( $meta['course_join'] ) ) && ( empty( $meta['course_join'] ) ) ) {
		return true;
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	if ( true === learndash_use_legacy_course_access_list() ) {
		if ( ! empty( $meta['course_access_list'] ) ) {
			$course_access_list = learndash_convert_course_access_list( $meta['course_access_list'], true );
		} else {
			$course_access_list = array();
		}
		if ( ( in_array( $user_id, $course_access_list ) ) || ( learndash_user_group_enrolled_to_course( $user_id, $course_id ) ) ) {
			$expired = ld_course_access_expired( $course_id, $user_id );
			return ! $expired; // True if not expired.
		} else {
			return false;
		}
	} else {
		$course_user_meta = get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );
		if ( ( ! empty( $course_user_meta ) ) || ( learndash_user_group_enrolled_to_course( $user_id, $course_id ) ) ) {
			$expired = ld_course_access_expired( $course_id, $user_id );
			return ! $expired; // True if not expired.
		} else {
			return false;
		}
	}

}

/**
 * Redirects a user to the course page if it does not have access.
 *
 * @since 2.1.0
 *
 * @param int $post_id The ID of the resource that belongs to a course.
 *
 * @return boolean|void Returns true if the user has access to the course.
 */
function sfwd_lms_access_redirect( $post_id ) {
	$access = sfwd_lms_has_access( $post_id );
	if ( true === $access ) {
		return true;
	}

	$link = get_permalink( learndash_get_course_id( $post_id ) );
	/**
	 * Filters the course redirect URL after checking access.
	 *
	 * @param string $link    The course URL a user is redirected to it has access.
	 * @param int    $post_id Post ID.
	 */
	$link = apply_filters( 'learndash_access_redirect', $link, $post_id );
	if ( ! empty( $link ) ) {
		learndash_safe_redirect( $link );
	}
}

/**
 * Checks whether the user's access to the course is expired.
 *
 * @since 2.1.0
 *
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 *
 * @return bool Returns true if the access is expired otherwise false.
 */
function ld_course_access_expired( $course_id, $user_id ) {
	$course_access_upto = ld_course_access_expires_on( $course_id, $user_id );

	if ( empty( $course_access_upto ) ) {
		return false;
	} else {

		if ( time() >= $course_access_upto ) {
			/**
			 * Filters whether the course is expired for a user or not.
			 *
			 * @since 2.6.2
			 *
			 * @param boolean $expired            Whether the course is expired or not.
			 * @param int     $user_id            User ID.
			 * @param int     $course_id          Course ID.
			 * @param int     $course_access_upto Course expiration timestamp.
			 */
			if ( apply_filters( 'learndash_process_user_course_access_expire', true, $user_id, $course_id, $course_access_upto ) ) {

				/**
				 * As of LearnDash 2.3.0.3 we store the GMT timestamp as the meta value. In prior versions we stored 1
				*/
				update_user_meta( $user_id, 'learndash_course_expired_' . $course_id, time() );
				ld_update_course_access( $user_id, $course_id, true );

				/**
				 * Fires when the user course access is expired.
				 *
				 * @since 2.6.2
				 *
				 * @param int $user_id   User ID.
				 * @param int $course_id Course ID.
				 */
				do_action( 'learndash_user_course_access_expired', $user_id, $course_id );

				$delete_course_progress = learndash_get_setting( $course_id, 'expire_access_delete_progress' );
				if ( ! empty( $delete_course_progress ) ) {
					learndash_delete_course_progress( $course_id, $user_id );
				}
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}


/**
 * Generates an alert in the header that a user's access to the course is expired.
 *
 * Fires on `wp_head` hook.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 2.1.0
 */
function ld_course_access_expired_alert() {
	global $post;

	if ( ! is_singular() || empty( $post->ID ) || learndash_get_post_type_slug( 'course' ) !== $post->post_type ) {
		return;
	}

	$user_id = get_current_user_id();

	if ( empty( $user_id ) ) {
		return;
	}

	$expired = get_user_meta( $user_id, 'learndash_course_expired_' . $post->ID, true );

	if ( empty( $expired ) ) {
		return;
	}

	$has_access = sfwd_lms_has_access( $post->ID, $user_id );

	if ( $has_access ) {
		delete_user_meta( $user_id, 'learndash_course_expired_' . $post->ID );
		return;
	} else {
		echo '<script>
			setTimeout(function() {
				alert("';
				printf(
					// translators: placeholder: Course.
					esc_html_x( 'Your access to this %s has expired.', 'placeholder: Course', 'learndash' ),
					esc_attr( LearnDash_Custom_Label::get_label( 'course' ) )
				);
				echo '")
			}, 2000);
		</script>';
	}
}

add_action( 'wp_head', 'ld_course_access_expired_alert', 1 );

/**
 * Gets the amount of time until the course access expires for a user.
 *
 * @since 2.1.0
 *
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 *
 * @return int The timestamp for course access expiration.
 */
function ld_course_access_expires_on( $course_id, $user_id ) {
	// Set a default return var.
	$course_access_upto = 0;

	// Check access to course_id + user_id.
	$courses_access_from = ld_course_access_from( $course_id, $user_id );

	// If the course_id + user_id is not set we check the group courses.
	if ( empty( $courses_access_from ) ) {
		$courses_access_from = learndash_user_group_enrolled_to_course_from( $user_id, $course_id );
	}

	// If we have a non-empty access from...
	if ( abs( intval( $courses_access_from ) ) ) {

		// Check the course is using expire access.
		$expire_access = learndash_get_setting( $course_id, 'expire_access' );
		// The value stored in the post meta for 'expire_access' is 'on' not true/false 1 or 0. The string 'on'.
		if ( ! empty( $expire_access ) ) {
			$expire_access_days = learndash_get_setting( $course_id, 'expire_access_days' );
			if ( abs( intval( $expire_access_days ) ) > 0 ) {
				$course_access_upto = abs( intval( $courses_access_from ) ) + ( abs( intval( $expire_access_days ) ) * DAY_IN_SECONDS );
			}
		}
	}

	/**
	 * Filters the amount of time until the user's course access expires.
	 *
	 * @since 3.0.7
	 *
	 * @param int $course_access_upto Course expires on timestamp.
	 * @param int $course_id          Course ID.
	 * @param int $user_id            User ID.
	 */
	return apply_filters( 'ld_course_access_expires_on', $course_access_upto, $course_id, $user_id );
}

/**
 * Gets the amount of time when the lesson becomes available to a user.
 *
 * @since 2.1.0
 *
 * @param int $course_id Optional. Course ID to check. Default 0.
 * @param int $user_id   Optional. User ID to check. Default 0.
 *
 * @return int The timestamp of when the course can be accessed from.
 */
function ld_course_access_from( $course_id = 0, $user_id = 0 ) {
	static $courses = array();

	$course_id = absint( $course_id );
	$user_id   = absint( $user_id );

	// If Shared Steps enabled we need to ensure both Course ID and User ID and not empty.
	if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
		if ( ( empty( $course_id ) ) || ( empty( $user_id ) ) ) {
			return false;
		}
	}

	if ( ! isset( $courses[ $course_id ][ $user_id ] ) ) {
		if ( ! isset( $courses[ $course_id ] ) ) {
			$courses[ $course_id ] = array();
		}
		$courses[ $course_id ][ $user_id ] = false;

		$courses[ $course_id ][ $user_id ] = (int) get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );
		if ( empty( $courses[ $course_id ][ $user_id ] ) ) {
			/**
			 * Filters whether to update user course access from value.
			 *
			 * @param boolean $update_access_from Whether to update user access from.
			 * @param int     $user_id            User ID.
			 * @param int     $course_id          Course ID.
			 */
			if ( ( 'open' === learndash_get_course_meta_setting( $course_id, 'course_price_type' ) ) && ( apply_filters( 'learndash_course_open_set_user_access_from', true, $user_id, $course_id ) ) ) {
				$enrolled_groups = learndash_user_group_enrolled_to_course_from( $user_id, $course_id );
				if ( ! empty( $enrolled_groups ) ) {
					$courses[ $course_id ][ $user_id ] = absint( $enrolled_groups );
				}
			}
		}
		if ( empty( $courses[ $course_id ][ $user_id ] ) ) {
			$course_activity_args = array(
				'user_id'       => $user_id,
				'post_id'       => $course_id,
				'activity_type' => 'access',
			);

			$course_activity = learndash_get_user_activity( $course_activity_args );
			if ( ( ! empty( $course_activity ) ) && ( is_object( $course_activity ) ) ) {
				if ( ( property_exists( $course_activity, 'activity_started' ) ) && ( ! empty( $course_activity->activity_started ) ) ) {
					$courses[ $course_id ][ $user_id ] = intval( $course_activity->activity_started );
					update_user_meta( $user_id, 'course_' . $course_id . '_access_from', $courses[ $course_id ][ $user_id ] );
				}
			}
		}
	}

	/**
	 * Filters the amount of time when a lesson becomes available to the user.
	 *
	 * @since 3.0.7
	 *
	 * @param int $access_from The timestamp of when the lesson wil become available to user.
	 * @param int $course_id   Course ID.
	 * @param int $user_id     User ID.
	 */
	return apply_filters( 'ld_course_access_from', $courses[ $course_id ][ $user_id ], $course_id, $user_id );
}

/**
 * Updates the course access time for a user.
 *
 * @since 3.0.0
 *
 * @param int        $course_id Course ID for update.
 * @param int        $user_id   User ID for update.
 * @param string|int $access    Optional. Value can be a date string (YYYY-MM-DD hh:mm:ss or integer value. Default empty.
 * @param boolean    $is_gmt    Optional. True if the access value is GMT or false if it is relative to site timezone. Default false.
 *
 * @return boolean Returns true if the value is updated successfully.
 */
function ld_course_access_from_update( $course_id, $user_id, $access = '', $is_gmt = false ) {
	if ( ( ! empty( $course_id ) ) && ( ! empty( $user_id ) ) && ( ! empty( $access ) ) ) {

		if ( ! is_numeric( $access ) ) {
			// If we a non-numeric value like a date stamp Y-m-d hh:mm:ss we want to convert it to a GMT timestamp.
			$access_time = learndash_get_timestamp_from_date_string( $access, ! $is_gmt );
		} elseif ( is_string( $access ) ) {
			if ( ! $is_gmt ) {
				$access = get_gmt_from_date( $access, 'Y-m-d H:i:s' );
			}
			$access_time = strtotime( $access );
		} else {
			return false;
		}

		if ( ( ! empty( $access_time ) ) && ( $access_time > 0 ) ) {
			// We don't allow dates greater than now.
			if ( $access_time > time() ) {
				$access_time = time();
			}

			$course_args = array(
				'course_id'        => $course_id,
				'post_id'          => $course_id,
				'activity_type'    => 'course',
				'user_id'          => $user_id,
				'activity_started' => $access_time,
			);
			$activity_id = learndash_update_user_activity( $course_args );

			return update_user_meta( $user_id, 'course_' . $course_id . '_access_from', $access_time );
		}
	}

	return false;
}

/**
 * Updates the list of courses a user can access.
 *
 * @since 2.1.0
 *
 * @param  int     $user_id   User ID.
 * @param  int     $course_id Course ID.
 * @param  boolean $remove    Optional. Whether to remove course access for the user. Default false.
 *
 * @return bool Returns true if the user course access update was successful otherwise false.
 */
function ld_update_course_access( $user_id, $course_id, $remove = false ): bool {
	$action_success = false;

	$user_id            = absint( $user_id );
	$course_id          = absint( $course_id );
	$course_access_list = null;

	if ( ( empty( $user_id ) ) || ( empty( $course_id ) ) ) {
		return false;
	}

	if ( true === learndash_use_legacy_course_access_list() ) {
		$course_access_list = learndash_get_setting( $course_id, 'course_access_list' );
		$course_access_list = learndash_convert_course_access_list( $course_access_list, true );

		if ( empty( $remove ) ) {
			$course_access_list[] = $user_id;
			$course_access_list   = array_unique( $course_access_list );
			$action_success       = true;
		} else {
			$course_access_list = array_diff( $course_access_list, array( $user_id ) );
			$action_success     = true;
		}
		$course_access_list = learndash_convert_course_access_list( $course_access_list );
		learndash_update_setting( $course_id, 'course_access_list', $course_access_list );
	}

	$user_course_access_time = 0;
	if ( empty( $remove ) ) {
		$user_course_access_time = get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );
		if ( empty( $user_course_access_time ) ) {
			$user_course_access_time = time();
			update_user_meta( $user_id, 'course_' . $course_id . '_access_from', $user_course_access_time );
			$action_success = true;
		}
	} else {
		$user_course_access_time = get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );
		if ( ! empty( $user_course_access_time ) ) {
			delete_user_meta( $user_id, 'course_' . $course_id . '_access_from' );
			$action_success = true;
		}
	}

	$course_activity_args = array(
		'activity_type' => 'access',
		'user_id'       => $user_id,
		'post_id'       => $course_id,
		'course_id'     => $course_id,
	);
	$course_activity      = learndash_get_user_activity( $course_activity_args );
	if ( is_null( $course_activity ) ) {
		$course_activity_args['course_id'] = 0;
		$course_activity                   = learndash_get_user_activity( $course_activity_args );
	}

	if ( is_object( $course_activity ) ) {
		$course_activity_args            = json_decode( wp_json_encode( $course_activity ), true );
		$course_activity_args['changed'] = false;
	} else {
		$course_activity_args['changed']          = true;
		$course_activity_args['activity_started'] = 0;
	}

	if ( ( empty( $course_activity_args['course_id'] ) ) || ( $course_activity_args['course_id'] !== $course_activity_args['post_id'] ) ) {
		$course_activity_args['course_id'] = $course_activity_args['post_id'];
		$course_activity_args['changed']   = true;
	}

	if ( empty( $remove ) ) {
		if ( absint( $course_activity_args['activity_started'] ) !== $user_course_access_time ) {
			$course_activity_args['activity_started'] = $user_course_access_time;
			$course_activity_args['changed']          = true;
		}
	} else {
		$course_activity_args['activity_started'] = $user_course_access_time;
		$course_activity_args['changed']          = true;
	}

	if ( true === $course_activity_args['changed'] ) {
		$skip = false;
		if ( ( ! empty( $remove ) ) && ( ! isset( $course_activity_args['activity_id'] ) ) ) {
			$skip = true;
		}
		if ( true !== $skip ) {
			$course_activity_args['data_upgrade'] = true;
			learndash_update_user_activity( $course_activity_args );
		}
	}

	/**
	 * Fires after a user's list of courses are updated.
	 *
	 * @since 2.1.0
	 *
	 * @param int          $user_id            User ID.
	 * @param int          $course_id          Course ID.
	 * @param string|null  $course_access_list A comma-separated list of user IDs used for the course_access_list field.
	 * Note: Used if `learndash_use_legacy_course_access_list()` returns true. Otherwise null is sent.
	 * @param boolean      $remove             Whether to remove course access from the user.
	 */
	do_action( 'learndash_update_course_access', $user_id, $course_id, $course_access_list, $remove );

	// Finally clear our cache for other services.
	$transient_key = 'learndash_user_courses_' . $user_id;
	LDLMS_Transients::delete( $transient_key );

	return $action_success;
}

/**
 * Gets the timestamp of when a user can access the lesson.
 *
 * @since 2.1.0
 *
 * @param int      $lesson_id Lesson ID.
 * @param int      $user_id   User ID.
 * @param int|null $course_id Optional. Course ID. Default null.
 * @param boolean  $bypass_transient Optional. Whether to bypass transient cache. Default false.
 *
 * @return int|void The timestamp of when the user can access the lesson.
 */
function ld_lesson_access_from( $lesson_id, $user_id, $course_id = null, $bypass_transient = false ) {
	$return = null;

	if ( is_null( $course_id ) ) {
		$course_id = learndash_get_course_id( $lesson_id );
	}

	$courses_access_from = ld_course_access_from( $course_id, $user_id );
	if ( empty( $courses_access_from ) ) {
		$courses_access_from = learndash_user_group_enrolled_to_course_from( $user_id, $course_id, $bypass_transient );
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
		$lesson_access_from = apply_filters( 'ld_lesson_access_from__visible_after', $lesson_access_from, $lesson_id, $user_id );

		$current_timestamp = time();
		if ( $current_timestamp < $lesson_access_from ) {
			$return = $lesson_access_from;
		}
	} else {
		$visible_after_specific_date = learndash_get_setting( $lesson_id, 'visible_after_specific_date' );
		if ( ! empty( $visible_after_specific_date ) ) {
			if ( ! is_numeric( $visible_after_specific_date ) ) {
				// If we a non-numeric value like a date stamp Y-m-d hh:mm:ss we want to convert it to a GMT timestamp.
				$visible_after_specific_date = learndash_get_timestamp_from_date_string( $visible_after_specific_date, true );
			}

			$current_time = time();

			if ( $current_time < $visible_after_specific_date ) {
				/**
				 * Filters the timestamp of when lesson will be available after a specific date.
				 *
				 * @param int $visible_after_specific_date The timestamp of when the lesson will be available after a specific date.
				 * @param int $lesson_id                  Lesson ID.
				 * @param int $user_id                    User ID.
				 */
				$return = apply_filters( 'ld_lesson_access_from__visible_after_specific_date', $visible_after_specific_date, $lesson_id, $user_id );
			}
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

/**
 * Gets when the lesson will be available.
 *
 * Fires on `learndash_content` hook.
 *
 * This function is not reentrant. If called using a Topic post it will recursively
 * call itself for the parent Lesson post.
 *
 * @since 2.1.0
 *
 * @param string  $content The content of lesson.
 * @param WP_Post $post    The `WP_Post` object.
 *
 * @return string The output of when the lesson will be available.
 */
/**
 * Gets when the lesson will be available.
 *
 * Fires on `learndash_content` hook.
 *
 * @since 2.1.0
 *
 * @param string  $content The content of lesson.
 * @param WP_Post $post    The `WP_Post` object.
 *
 * @return string The output of when the lesson will be available.
 */
function lesson_visible_after( string $content = '', $post = null ) {
	if ( ! is_a( $post, 'WP_Post' ) ) {
		$post_id = get_the_ID();
		if ( ! empty( $post_id ) ) {
			$post = get_post( $post_id );
			if ( ! is_a( $post, 'WP_Post' ) ) {
				return $content;
			}
		}
	}

	if ( ! in_array( $post->post_type, learndash_get_post_types(), true ) ) {
		return $content;
	}

	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
	} else {
		return $content;
	}

	$bypass_course_limits_admin_users = learndash_can_user_bypass( $user_id, 'learndash_course_lesson_not_available', $post->ID, $post );

	// For logged in users to allow an override filter.
	/** This filter is documented in includes/course/ld-course-progress.php */
	if (
		apply_filters(
			'learndash_prerequities_bypass', // cspell:disable-line -- prerequities are prerequisites...
			$bypass_course_limits_admin_users,
			$user_id,
			$post->ID,
			$post
		)
	) {
		return $content;
	}

	$course_id = learndash_get_course_id( $post );
	if ( empty( $course_id ) ) {
		return $content;
	}

	$lesson_access_from = learndash_course_step_available_date( $post->ID, $course_id, $user_id, true );
	if ( ! empty( $lesson_access_from ) ) {
		$context = learndash_get_post_type_key( $post->post_type );

		if ( learndash_get_post_type_slug( 'lesson' ) === $post->post_type ) {
			$lesson_id = $post->ID;
		} else {
			$lesson_id = 0;
		}

		$content = SFWD_LMS::get_template(
			'learndash_course_lesson_not_available',
			array(
				'user_id'                 => $user_id,
				'course_id'               => $course_id,
				'step_id'                 => $post->ID,
				'lesson_id'               => $lesson_id,
				'lesson_access_from_int'  => $lesson_access_from,
				'lesson_access_from_date' => learndash_adjust_date_time_display( $lesson_access_from ),
				'context'                 => $context,
			),
			false
		);
	}

	return $content;
}

add_filter( 'learndash_content', 'lesson_visible_after', 1, 2 );

/**
 * Gets the list of users who has access to the given course.
 *
 * @since 2.3.0
 *
 * @param int     $course_id     Optional. The ID of the course. Default 0.
 * @param array   $query_args    Optional. An array of `WP_User_query` arguments. Default empty array.
 * @param boolean $exclude_admin Optional. Whether to exclude admins from the user list. Default true.
 *
 * @return WP_User_Query The `WP_User_Query` object.
 */
function learndash_get_users_for_course( $course_id = 0, $query_args = array(), $exclude_admin = true ) {
	$course_user_ids = array();

	if ( empty( $course_id ) ) {
		return $course_user_ids;
	}

	$defaults = array(
		// By default WP_User_Query will return ALL users. Strange.
		'fields' => 'ID',
	);

	$query_args = wp_parse_args( $query_args, $defaults );

	if ( true === $exclude_admin ) {
		$query_args['role__not_in'] = array( 'administrator' );
	}

	$course_price_type = learndash_get_course_meta_setting( $course_id, 'course_price_type' );

	if ( 'open' === $course_price_type ) {

		$user_query = new WP_User_Query( $query_args );
		return $user_query;

	} else {

		if ( true === learndash_use_legacy_course_access_list() ) {
			$course_access_list = learndash_get_course_meta_setting( $course_id, 'course_access_list' );
			$course_user_ids    = array_merge( $course_user_ids, $course_access_list );
		}

		$course_access_users = learndash_get_course_users_access_from_meta( $course_id );
		$course_user_ids     = array_merge( $course_user_ids, $course_access_users );

		$course_groups_users = learndash_get_course_groups_users_access( $course_id );
		$course_user_ids     = array_merge( $course_user_ids, $course_groups_users );

		if ( ! empty( $course_user_ids ) ) {
			$course_user_ids = array_unique( $course_user_ids );
		}

		$course_expired_access_users = learndash_get_course_expired_access_from_meta( $course_id );
		if ( ! empty( $course_expired_access_users ) ) {
			$course_user_ids = array_diff( $course_user_ids, $course_expired_access_users );
		}

		if ( ! empty( $course_user_ids ) ) {
			$query_args['include'] = $course_user_ids;

			$user_query = new WP_User_Query( $query_args );

			return $user_query;
		}
	}

	return $course_user_ids;
}

/**
 * Sets new users to the course access list.
 *
 * @since 2.5.0
 *
 * @param int   $course_id        Optional. The ID of the course. Default 0.
 * @param array $course_users_new Optional. An array of user IDs to set course access. Default empty array.
 */
function learndash_set_users_for_course( $course_id = 0, $course_users_new = array() ) {

	if ( ! empty( $course_id ) ) {

		if ( ! empty( $course_users_new ) ) {
			$course_users_new = learndash_convert_course_access_list( $course_users_new, true );
		} else {
			$course_users_new = array();
		}

		$course_users_old = learndash_get_course_users_access_from_meta( $course_id );
		if ( ! empty( $course_users_old ) ) {
			$course_users_old = learndash_convert_course_access_list( $course_users_old, true );
		} else {
			$course_users_old = array();
		}

		$course_users_intersect = array_intersect( $course_users_new, $course_users_old );

		$course_users_add = array_diff( $course_users_new, $course_users_intersect );
		if ( ! empty( $course_users_add ) ) {
			foreach ( $course_users_add as $user_id ) {
				ld_update_course_access( $user_id, $course_id, false );
			}
		}

		$course_users_remove = array_diff( $course_users_old, $course_users_intersect );
		if ( ! empty( $course_users_remove ) ) {
			foreach ( $course_users_remove as $user_id ) {
				ld_update_course_access( $user_id, $course_id, true );
			}
		}
	}
}

/**
 * Gets the users with course access from the user meta.
 *
 * @since 2.6.4
 *
 * @param int $course_id Optional. The ID of the course. Default 0.
 *
 * @return array An array of user IDs that have access to course.
 */
function learndash_get_course_users_access_from_meta( $course_id = 0 ) {
	global $wpdb;

	$course_user_ids = array();

	if ( ! empty( $course_id ) ) {
		// We have to do it this was because WP_User_Query cannot handle on meta EXISTS and another 'NOT EXISTS' in the same query.
		$course_user_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} as usermeta WHERE meta_key = %s",
				'course_' . $course_id . '_access_from'
			)
		);
	}
	return $course_user_ids;
}

/**
 * Get user progress for course child steps.
 *
 * @since 3.4.2
 *
 * @param int $user_id   User ID.
 * @param int $course_id Course post ID.
 * @param int $step_id   Parent step post ID.
 *
 * @return array An array of child steps with status.
 */
function learndash_user_get_course_childen_progress( $user_id = 0, $course_id = 0, $step_id = 0 ) {
	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );
	$step_id   = absint( $step_id );

	$return_steps = array();

	if ( ( ! empty( $course_id ) ) && ( ! empty( $step_id ) ) && ( ! empty( $user_id ) ) ) {
		$course_children_steps = learndash_course_get_children_of_step( $course_id, $step_id );
		if ( ! empty( $course_children_steps ) ) {
			$course_children_steps = array_map( 'absint', $course_children_steps );

			$user_course_progress_co = learndash_user_get_course_progress( $user_id, $course_id, 'co' );
			if ( ! empty( $user_course_progress_co ) ) {
				foreach ( $user_course_progress_co as $step_key => $step_complete ) {
					list( $child_post_type, $child_post_id ) = explode( ':', $step_key );
					$child_post_type                         = esc_attr( $child_post_type );
					$child_post_id                           = absint( $child_post_id );
					if ( ( ! empty( $child_post_id ) ) && ( in_array( $child_post_id, $course_children_steps, true ) ) ) {
						$return_steps[ $step_key ] = $step_complete;
					}
				}
			}
		}
	}

	return $return_steps;
}

/**
 * Check if user has completed all course children steps.
 *
 * @since 3.4.2
 *
 * @param int $user_id   User ID.
 * @param int $course_id Course post ID.
 * @param int $step_id   Parent step post ID.
 *
 * @return bool true if all child steps are complete.
 */
function learndash_user_is_course_children_progress_complete( $user_id = 0, $course_id = 0, $step_id = 0 ) {
	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );
	$step_id   = absint( $step_id );

	if ( ( ! empty( $course_id ) ) && ( ! empty( $step_id ) ) && ( ! empty( $user_id ) ) ) {
		$user_children_progress = learndash_user_get_course_childen_progress( $user_id, $course_id, $step_id ); // cspell:disable-line.
		if ( ( is_array( $user_children_progress ) ) && ( array_sum( $user_children_progress ) === count( $user_children_progress ) ) ) {
			return true;
		}
	}

	return false;
}


/**
 * Gets the course step available date.
 *
 * @since 4.2.0
 *
 * @param int  $step_id      The Course step post ID Lesson, Topic, or Quiz.
 * @param int  $course_id    Optional. The Course ID.
 * @param int  $user_id      Optional. The user ID.
 * @param bool $parent_steps Optional. Whether to include the parent steps. Default false.
 *
 * @return int.
 */
function learndash_course_step_available_date( int $step_id = 0, int $course_id = 0, int $user_id = 0, bool $parent_steps = false ) {
	$available_timestamp = 0;

	$step_id   = absint( $step_id );
	$course_id = absint( $course_id );
	$user_id   = absint( $user_id );

	if ( empty( $step_id ) ) {
		return $available_timestamp;
	}

	$step_post = get_post();
	if ( ( ! is_a( $step_post, 'WP_Post' ) ) || ( ! in_array( $step_post->post_type, learndash_get_post_types(), true ) ) ) {
		return $available_timestamp;
	}

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id( $step_id );
		if ( empty( $course_id ) ) {
			return $available_timestamp;
		}
	}

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
		if ( empty( $course_id ) ) {
			return $available_timestamp;
		}
	}

	if ( learndash_can_user_bypass( $user_id, 'learndash_course_lesson_not_available', $step_post->ID, $step_post ) ) {
		return $available_timestamp;
	}

	$step_ids = array();
	if ( true === $parent_steps ) {
		$step_ids = learndash_course_get_all_parent_step_ids( $course_id, $step_id, true, true );
		if ( count( $step_ids ) > 1 ) {
			$step_ids = array_reverse( $step_ids );
		}
	}
	$step_ids = array_merge( array( $step_id ), $step_ids );

	if ( ! empty( $step_ids ) ) {
		foreach ( $step_ids as $_step_id ) {
			$available_timestamp = (int) ld_lesson_access_from( $_step_id, $user_id, $course_id );
			if ( ! empty( $available_timestamp ) ) {
				break;
			}
		}
	}

	return $available_timestamp;
}
