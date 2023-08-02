<?php
/**
 * Course Functions
 *
 * @since 2.1.0
 *
 * @package LearnDash\Course
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// cspell:ignore prerequities .

/**
 * Gets the course ID for a resource.
 *
 * Determine the type of ID being passed. Should be the ID of
 * anything that belongs to a course (Lesson, Topic, Quiz, etc)
 *
 * @since 2.1.0
 * @since 2.5.0 Added the `$bypass_cb` parameter.
 *
 * @param  WP_Post|int|null $id        Optional. ID of the resource. Default null.
 * @param  boolean          $bypass_cb Optional. If true will bypass course_builder logic. Default false.
 *
 * @return int|bool ID of the course.
 */
function learndash_get_course_id( $id = null, $bypass_cb = false ) {
	if ( is_object( $id ) && $id->ID ) {
		$p  = $id;
		$id = $p->ID;
	} elseif ( is_numeric( $id ) ) {
		$p = get_post( $id );
	}

	if ( empty( $id ) ) {
		if ( ! defined( 'REST_REQUEST' ) ) {
			if ( is_admin() ) {
				global $parent_file, $post_type, $pagenow;
				if ( ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) || ( ! in_array( $post_type, learndash_get_post_types( 'course' ), true ) ) ) {
					return false;
				}
			} elseif ( ! is_single() || is_home() ) {
				return false;
			}
		}

		$post = get_post( get_the_id() );
		if ( ( $post ) && ( $post instanceof WP_Post ) ) {
			$id = $post->ID;
			$p  = $post;
		} else {
			return false;
		}
	}

	if ( empty( $p->ID ) ) {
		return 0;
	}

	if ( learndash_get_post_type_slug( 'course' ) === $p->post_type ) {
		return $p->ID;
	}

	// Somewhat a kludge. Here we try and assume the course_id being handled.
	if ( ( learndash_is_course_shared_steps_enabled() ) && ( false === $bypass_cb ) ) {
		if ( ! is_admin() ) {
			$course_slug = get_query_var( 'sfwd-courses' );
			if ( ! empty( $course_slug ) ) {
				$course_post = learndash_get_page_by_path( $course_slug, 'sfwd-courses' );
				if ( ( $course_post ) && ( $course_post instanceof WP_Post ) ) {
					return $course_post->ID;
				}
			}
		}

		if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
			return intval( $_GET['course_id'] );
		} elseif ( ( isset( $_GET['course'] ) ) && ( ! empty( $_GET['course'] ) ) ) {
			return intval( $_GET['course'] );
		} elseif ( ( isset( $_POST['course_id'] ) ) && ( ! empty( $_POST['course_id'] ) ) ) {
			return intval( $_POST['course_id'] );
		} elseif ( ( isset( $_POST['course'] ) ) && ( ! empty( $_POST['course'] ) ) ) {
			return intval( $_POST['course'] );
		} elseif ( ( isset( $_GET['post'] ) ) && ( ! empty( $_GET['post'] ) ) ) {
			if ( get_post_type( intval( $_GET['post'] ) ) == 'sfwd-courses' ) {
				return intval( $_GET['post'] );
			}
		}
	}

	if ( learndash_get_post_type_slug( LDLMS_Post_Types::EXAM ) === $p->post_type ) {
		return (int) get_post_meta( intval( $id ), 'exam_challenge_course_show', true );
	}

	return (int) get_post_meta( $id, 'course_id', true );
}

/**
 * Gets the lesson ID of a resource.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 2.1.0
 *
 * @param int|null $post_id   Optional. ID of the resource. Default null.
 * @param int|null $course_id Optional. ID of the course. Default null.
 *
 * @return string Lesson ID.
 */
function learndash_get_lesson_id( $post_id = null, $course_id = null ) {
	$post_id   = absint( $post_id );
	$course_id = absint( $course_id );

	if ( empty( $post_id ) ) {
		$the_id = get_the_id();
		if ( empty( $the_id ) ) {
			return false;
		}
		$post = get_post( $the_id );
	} else {
		$post = get_post( $post_id );
	}

	if ( ( ! $post ) || ( ! is_a( $post, 'WP_Post' ) ) || ( ! in_array( $post->post_type, learndash_get_post_types( 'course' ), true ) ) ) {
		return false;
	}

	if ( learndash_get_post_type_slug( 'lesson' ) === $post->post_type ) {
		return $post->ID;
	}

	if ( learndash_is_course_shared_steps_enabled() ) {
		$lesson_slug = get_query_var( learndash_get_post_type_slug( 'lesson' ) );
		if ( ! empty( $lesson_slug ) ) {
			$lesson_post = learndash_get_page_by_path( $lesson_slug, learndash_get_post_type_slug( 'lesson' ) );
			if ( ( $lesson_post ) && ( is_a( $lesson_post, 'WP_Post' ) ) ) {
				return $lesson_post->ID;
			}
		} else {
			if ( empty( $course_id ) ) {
				$course_id = learndash_get_course_id( $post->ID );
			}

			if ( ! empty( $course_id ) ) {
				return learndash_course_get_single_parent_step( $course_id, $post->ID );
			}
		}
	} else {
		if ( in_array( $post->post_type, learndash_get_post_type_slug( array( 'topic', 'quiz' ) ), true ) ) {
			return get_post_meta( $post->ID, 'lesson_id', true );
		}
	}

	return '';
}

/**
 * Checks if the user's course prerequisites are completed for a given course.
 *
 * @since 3.2.0
 * @since 3.2.3 Added `$user_id` parameter.
 *
 * @param int $post_id Optional. The ID of the course. Default 0.
 * @param int $user_id Optional. The ID of the user. Default 0.
 *
 * @return boolean Returns true if the prerequisites are completed.
 */
function learndash_is_course_prerequities_completed( $post_id = 0, $user_id = 0 ) {
	$course_pre_complete = true;

	if ( empty( $user_id ) ) {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = 0;
		}
	}

	if ( ! empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( ( ! empty( $course_id ) ) && ( ! empty( $user_id ) ) && ( learndash_get_course_prerequisite_enabled( $course_id ) ) ) {
			$course_pre = learndash_get_course_prerequisites( $course_id, $user_id );
			if ( ! empty( $course_pre ) ) {
				$course_pre_compare = learndash_get_course_prerequisite_compare( $course_id );
				if ( 'ANY' === $course_pre_compare ) {
					$s_pre = array_search( true, $course_pre, true );
					if ( false !== $s_pre ) {
						$course_pre_complete = true;
					} else {
						$course_pre_complete = false;
					}
				} elseif ( 'ALL' === $course_pre_compare ) {
					$s_pre = array_search( false, $course_pre, true );
					if ( false === array_search( false, $course_pre, true ) ) {
						$course_pre_complete = true;
					} else {
						$course_pre_complete = false;
					}
				}
			}
		}
	}

	return $course_pre_complete;
}

/**
 * Gets the list of course prerequisites and its status for a course.
 *
 * @since 2.4.0
 * @since 3.2.3 Added `$user_id` parameter.
 *
 * @param int $post_id Optional. The ID of the course. Default 0.
 * @param int $user_id Optional. The ID of the user. Default 0.
 *
 * @return array An array of course prerequisites.
 */
function learndash_get_course_prerequisites( $post_id = 0, $user_id = 0 ) {
	$courses_status_array = array();

	if ( empty( $user_id ) ) {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = 0;
		}
	}

	if ( ! empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( ( ! empty( $course_id ) ) && ( ! empty( $user_id ) ) && ( learndash_get_course_prerequisite_enabled( $course_id ) ) ) {

			$course_pre = learndash_get_course_prerequisite( $course_id );
			if ( ! empty( $course_pre ) ) {
				$course_pre_compare = learndash_get_course_prerequisite_compare( $course_id );

				if ( is_string( $course_pre ) ) {
					$course_pre = array( $course_pre );
				}

				foreach ( $course_pre as $c_id ) {
					// Now check if the prerequisites course is completed by user or not.
					$course_status = learndash_course_status( $c_id, $user_id, true );
					if ( 'completed' === $course_status ) {
						$courses_status_array[ $c_id ] = true;
					} else {
						$courses_status_array[ $c_id ] = false;
					}
				}
			}
		}
	}
	return $courses_status_array;
}

/**
 * Gets the list of course prerequisites for a given course.
 *
 * @since 2.1.0
 *
 * @param int $course_id Optional. The ID if the course. Default 0.
 *
 * @return array An array of course prerequisite.
 */
function learndash_get_course_prerequisite( $course_id = 0 ) {
	$course_pre = array();

	if ( ! empty( $course_id ) ) {
		$transient_key        = 'learndash_course_pre_' . $course_id;
		$course_pre_transient = LDLMS_Transients::get( $transient_key );

		if ( false !== $course_pre_transient ) {
			$course_pre = (array) $course_pre_transient;
		} else {
			$course_pre = learndash_get_setting( $course_id, 'course_prerequisite' );
			if ( empty( $course_pre ) ) {
				$course_pre = array();
			}
			$course_pre = array_map( 'absint', $course_pre );
			$course_pre = array_diff( $course_pre, array( 0 ) ); // Removes zeros.
			if ( ! empty( $course_pre ) ) {
				$post_status = learndash_get_step_post_statuses();

				$course_pre_query_args = array(
					'post_type'   => learndash_get_post_type_slug( 'course' ),
					'nopaging'    => true,
					'post_status' => array_keys( $post_status ),
					'fields'      => 'ids',
					'post__in'    => $course_pre,
				);

				$course_pre_query = new WP_Query( $course_pre_query_args );
				if ( ( is_a( $course_pre_query, 'WP_Query' ) ) && ( property_exists( $course_pre_query, 'posts' ) ) && ( ! empty( $course_pre_query->posts ) ) ) {
					$course_pre = $course_pre_query->posts;
					LDLMS_Transients::set( $transient_key, $course_pre, HOUR_IN_SECONDS );
				}
			}
		}
	}

	return $course_pre;
}

/**
 * Sets new prerequisites for a course.
 *
 * @since 2.4.4
 *
 * @param int   $course_id  Optional. ID of the course. Default 0.
 * @param array $course_pre Optional. An array of course prerequisites. Default empty array.
 *
 * @return boolean Returns true if update was successful otherwise false.
 */
function learndash_set_course_prerequisite( $course_id = 0, $course_pre = array() ) {
	if ( ! empty( $course_id ) ) {
		if ( ( ! empty( $course_pre ) ) && ( is_array( $course_pre ) ) ) {
			$course_pre = array_unique( $course_pre );
		}

		$transient_key        = 'learndash_course_pre_' . $course_id;
		$course_pre_transient = LDLMS_Transients::delete( $transient_key );

		return learndash_update_setting( $course_id, 'course_prerequisite', $course_pre );
	}

	return false;
}

/**
 * Checks whether the prerequisites are enabled for a course.
 *
 * @since 2.4.0
 *
 * @param int $course_id The ID of the course.
 *
 * @return boolean Returns true if the prerequisites are enabled otherwise false.
 */
function learndash_get_course_prerequisite_enabled( $course_id ) {
	$course_pre_enabled = false;

	$course_id = learndash_get_course_id( $course_id );
	if ( ! empty( $course_id ) ) {
		$course_pre_enabled = learndash_get_setting( $course_id, 'course_prerequisite_enabled' );
		if ( 'on' === $course_pre_enabled ) {
			$course_pre_courses = learndash_get_setting( $course_id, 'course_prerequisite' );
			if ( ( is_array( $course_pre_courses ) ) && ( ! empty( $course_pre_courses ) ) ) {
				$course_pre_enabled = true;
			}
		}
	}

	return $course_pre_enabled;
}

/**
 * Sets the status of whether the course prerequisite is enabled or disabled.
 *
 * @since 2.4.4
 *
 * @param int     $course_id The ID of the course.
 * @param boolean $enabled   Optional. The value is true to enable course prerequisites. Any other
 *                           value will disable course prerequisites. Default true.
 *
 * @return boolean Returns true if the status was updated successfully otherwise false.
 */
function learndash_set_course_prerequisite_enabled( $course_id, $enabled = true ) {
	if ( true === $enabled ) {
		$enabled = 'on';
	}

	if ( 'on' !== $enabled ) {
		$enabled = '';
	}

	return learndash_update_setting( $course_id, 'course_prerequisite_enabled', $enabled );
}

/**
 * Gets the prerequisites compare value for a course.
 *
 * @since 2.4.0
 *
 * @param int $post_id The ID of the course.
 *
 * @return string The compare value for the prerequisite. Value can be 'ALL' or 'ANY' by default.
 */
function learndash_get_course_prerequisite_compare( $post_id ) {

	$course_pre_compare = 'ANY';

	if ( ! empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( ! empty( $course_id ) ) {
			$course_prerequisite_compare = learndash_get_setting( $course_id, 'course_prerequisite_compare' );
			if ( ( 'ANY' === $course_prerequisite_compare ) || ( 'ALL' === $course_prerequisite_compare ) ) {
				$course_pre_compare = $course_prerequisite_compare;
			}
		}
	}
	return $course_pre_compare;
}

/**
 * Checks if the course points are enabled for a course.
 *
 * @since 2.4.0
 *
 * @param int $post_id Optional. The course ID. Default 0.
 *
 * @return bool Returns true if the course points are enabled otherwise false.
 */
function learndash_get_course_points_enabled( $post_id = 0 ) {
	$course_points_enabled = false;

	if ( ! empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( ! empty( $course_id ) ) {
			$course_points_enabled = learndash_get_setting( $course_id, 'course_points_enabled' );
			if ( 'on' === $course_points_enabled ) {
				$course_points_enabled = true;
			}
		}
	}

	return $course_points_enabled;
}

/**
 * Gets the course points for a given course ID.
 *
 * @since 2.4.0
 *
 * @param int $post_id  Optional. Course Step or Course post ID. Default 0.
 * @param int $decimals Optional. Number of decimal places to round. Default 1.
 *
 * @return int|false Returns false if the course points are disabled otherwise returns course points.
 */
function learndash_get_course_points( $post_id = 0, $decimals = 1 ) {
	$course_points = false;

	if ( ! empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( ! empty( $course_id ) ) {
			if ( learndash_get_course_points_enabled( $course_id ) ) {
				$course_points = 0;

				$course_points = learndash_get_setting( $course_id, 'course_points' );
				if ( ! empty( $course_points ) ) {
					$course_points = learndash_format_course_points( $course_points, $decimals );
				}
			}
		}
	}

	return $course_points;
}

/**
 * Gets the course points access for a given course ID.
 *
 * @since 2.4.0
 *
 * @param int $post_id Optional. The ID of the course. Default 0.
 *
 * @return int|false Returns false if the course points are disabled otherwise returns course points.
 */
function learndash_get_course_points_access( $post_id = 0 ) {
	$course_points_access = false;

	if ( ! empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( ! empty( $course_id ) ) {
			if ( learndash_get_course_points_enabled( $course_id ) ) {
				$course_points_access = 0;

				$course_points_access = learndash_format_course_points( learndash_get_setting( $course_id, 'course_points_access' ) );
			}
		}
	}

	return $course_points_access;
}

/**
 * Checks if a user can access course points.
 *
 * @since 2.4.0
 *
 * @param int $post_id The ID of the post.
 * @param int $user_id Optional. The ID of the user. Default 0.
 *
 * @return boolean Whether a user can access course points.
 */
function learndash_check_user_course_points_access( $post_id, $user_id = 0 ) {
	$user_can_access = true;

	if ( empty( $user_id ) ) {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			return false;
		}
	}

	if ( ! empty( $post_id ) ) {
		$course_id = learndash_get_course_id( $post_id );
		if ( ( ! empty( $course_id ) ) && ( ! empty( $user_id ) ) ) {
			if ( learndash_get_course_points_enabled( $course_id ) ) {
				$course_access_points = learndash_get_course_points_access( $course_id );

				if ( ! empty( $course_access_points ) ) {
					$user_course_points = learndash_get_user_course_points( $user_id );

					if ( floatval( $user_course_points ) >= floatval( $course_access_points ) ) {
						return true;
					} else {
						return false;
					}
				}
			}
		}
	}

	return true;
}

/**
 * Handles the actions to be made when the user joins a course.
 *
 * Fires on `wp` hook.
 * Redirects user to login URL, adds course access to user.
 *
 * @since 2.1.0
 */
function learndash_process_course_join() {
	$user_id = get_current_user_id();

	if ( ( isset( $_POST['course_join'] ) ) && ( isset( $_POST['course_id'] ) ) ) {
		$post_label_prefix = 'course';
		$post_id           = intval( $_POST['course_id'] );
		$post              = get_post( $post_id );
		if ( ( ! $post ) || ( ! is_a( $post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'course' ) !== $post->post_type ) ) {
			return;
		}
	} elseif ( ( isset( $_POST['group_join'] ) ) && ( isset( $_POST['group_id'] ) ) ) {
		$post_label_prefix = 'group';
		$post_id           = intval( $_POST['group_id'] );
		$post              = get_post( $post_id );
		if ( ( ! $post ) || ( ! is_a( $post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'group' ) !== $post->post_type ) ) {
			return;
		}
	} else {
		return;
	}

	if ( empty( $user_id ) ) {
		$login_url = wp_login_url( get_permalink( $post_id ) );

		/**
		 * Filters URL that a user should be redirected to after joining a course.
		 *
		 * @since 2.1.0
		 *
		 * @param string $login_url Redirect URL.
		 * @param int    $post_id Course or Group ID.
		 */
		$login_url = apply_filters( 'learndash_' . $post_label_prefix . '_join_redirect', $login_url, $post_id );
		if ( ! empty( $login_url ) ) {
			learndash_safe_redirect( $login_url );
		}
	}

	/**
	 * Verify the form is valid
	 *
	 * @since 2.2.1.2
	 */
	if ( ! wp_verify_nonce( $_POST[ $post_label_prefix . '_join' ], $post_label_prefix . '_join_' . $user_id . '_' . $post_id ) ) {
		return;
	}

	$settings = learndash_get_setting( $post_id );

	if ( learndash_get_post_type_slug( 'group' ) === get_post_type( $post_id ) ) {
		if ( ! isset( $settings['group_price_type'] ) ) {
			if ( ! defined( 'LEARNDASH_DEFAULT_GROUP_PRICE_TYPE' ) ) {
				$settings['group_price_type'] = LEARNDASH_DEFAULT_GROUP_PRICE_TYPE;
			} else {
				$settings['group_price_type'] = '';
			}
		}

		if ( 'free' === $settings['group_price_type'] || 'paynow' === $settings['group_price_type'] && empty( $settings['group_price'] ) && ! empty( $_POST['group_join'] ) || learndash_is_user_in_group( $user_id, $post_id ) ) {
			ld_update_group_access( $user_id, $post_id );
		}
	} elseif ( learndash_get_post_type_slug( 'course' ) === get_post_type( $post_id ) ) {
		if ( ! isset( $settings['course_price_type'] ) ) {
			if ( ! defined( 'LEARNDASH_DEFAULT_COURSE_PRICE_TYPE' ) ) {
				$settings['course_price_type'] = LEARNDASH_DEFAULT_COURSE_PRICE_TYPE;
			} else {
				$settings['course_price_type'] = '';
			}
		}

		if ( 'free' === $settings['course_price_type'] || 'paynow' === $settings['course_price_type'] && empty( $settings['course_price'] ) && ! empty( $settings['course_join'] ) || sfwd_lms_has_access( $post_id, $user_id ) ) {
			ld_update_course_access( $user_id, $post_id );
		}
	}
}

add_action( 'wp', 'learndash_process_course_join' );

/**
 * Gets all the courses with the price type open.
 *
 * Logic for this query was taken from the `sfwd_lms_has_access_fn()` function
 *
 * @since 2.3.0
 *
 * @param boolean $bypass_transient Optional. Whether to bypass transient cache. Default false.
 *
 * @return array An array of course IDs.
 */
function learndash_get_open_courses( $bypass_transient = false ) {
	return learndash_get_posts_by_price_type( learndash_get_post_type_slug( 'course' ), 'open', $bypass_transient );
}

/**
 * Gets all the courses with the price type paynow.
 *
 * Logic for this query was taken from the `sfwd_lms_has_access_fn()` function.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.3.0
 *
 * @param boolean $bypass_transient Optional. Whether to bypass the transient cache. Default false.
 *
 * @return array An array of course IDs.
 */
function learndash_get_paynow_courses( $bypass_transient = false ) {
	return learndash_get_posts_by_price_type( learndash_get_post_type_slug( 'course' ), 'paynow', $bypass_transient );
}

/**
 * Gets the list of users with expired course access from the user meta.
 *
 * @since 2.6.4
 *
 * @param int $course_id Optional. The ID of the course. Default 0.
 *
 * @return array An array of users with expired course access.
 */
function learndash_get_course_expired_access_from_meta( $course_id = 0 ) {
	global $wpdb;

	$expired_user_ids = array();

	if ( ! empty( $course_id ) ) {
		$expired_user_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} as usermeta WHERE meta_key = %s",
				'learndash_course_expired_' . $course_id
			)
		);
	}

	return array_map( 'absint', $expired_user_ids );
}


/**
 * Gets the course settings from the course meta.
 *
 * @since 2.6.4
 *
 * @TODO Need to convert all references to get_post_meta for '_sfwd-courses' to use this function.
 *
 * @param int    $course_id   Optional. The ID of the course. Default 0.
 * @param string $setting_key Optional. The slug of the setting to get. Default empty.
 *
 * @return mixed Returns course settings. Passing empty setting key gets all the settings.
 */
function learndash_get_course_meta_setting( $course_id = 0, $setting_key = '' ) {
	$course_settings = array();

	if ( empty( $course_id ) ) {
		return $course_settings;
	}

	$meta = get_post_meta( $course_id, '_sfwd-courses', true );
	if ( ( is_null( $meta ) ) || ( ! is_array( $meta ) ) ) {
		$meta = array();
	}

	// we only want/need to reformat the access list of we are returning ALL setting or just the access list.
	if ( ( empty( $setting_key ) ) || ( 'course_access_list' === $setting_key ) ) {
		if ( ! isset( $meta['sfwd-courses_course_access_list'] ) ) {
			$meta['sfwd-courses_course_access_list'] = '';
		}

		if ( ! empty( $meta['sfwd-courses_course_access_list'] ) ) {
			if ( is_string( $meta['sfwd-courses_course_access_list'] ) ) {
				$meta['sfwd-courses_course_access_list'] = array_map( 'absint', explode( ',', $meta['sfwd-courses_course_access_list'] ) );
			} elseif ( is_array( $meta['sfwd-courses_course_access_list'] ) ) {
				$meta['sfwd-courses_course_access_list'] = array_map( 'absint', $meta['sfwd-courses_course_access_list'] );
			} else {
				// Not sure how we can get here. Just in case.
				$meta['sfwd-courses_course_access_list'] = array();
			}
		} else {
			$meta['sfwd-courses_course_access_list'] = array();
		}

		// Need to remove the empty '0' items.
		$meta['sfwd-courses_course_access_list'] = array_diff( $meta['sfwd-courses_course_access_list'], array( 0, '' ) );
	}

	if ( empty( $setting_key ) ) {
		return $meta;
	} elseif ( isset( $meta[ 'sfwd-courses_' . $setting_key ] ) ) {
		return $meta[ 'sfwd-courses_' . $setting_key ];
	}
}

add_filter(
	'sfwd-courses_display_options',
	function( $options, $location ) {
		if ( ( ! isset( $options[ $location . '_course_prerequisite_enabled' ] ) ) || ( empty( $options[ $location . '_course_prerequisite_enabled' ] ) ) ) {
			global $post;
			if ( $post instanceof WP_Post ) {
				$settings = get_post_meta( $post->ID, '_sfwd-courses', true );

				if ( ( isset( $settings[ $location . '_course_prerequisite' ] ) ) && ( ! empty( $settings[ $location . '_course_prerequisite' ] ) ) ) {
					$options[ $location . '_course_prerequisite_enabled' ]  = 'on';
					$settings[ $location . '_course_prerequisite_enabled' ] = 'on';
					update_post_meta( $post->ID, '_sfwd-courses', $settings );
				}
			}
		}

		return $options;
	},
	1,
	2
);

/**
 * Updates the users group course access.
 *
 * Fires on `learndash_update_course_access` hook.
 *
 * @since 2.4.0
 *
 * @param int     $user_id     The ID of the user.
 * @param int     $course_id   The ID of the course.
 * @param array   $access_list An array of course access list.
 * @param boolean $remove      Whether to user group from course access.
 */
function learndash_update_course_users_groups( $user_id, $course_id, $access_list, $remove ) {
	if ( ( ! empty( $user_id ) ) && ( ! empty( $course_id ) ) && ( true !== (bool) $remove ) ) {

		$groups = learndash_get_course_groups( $course_id, true );
		if ( ! empty( $groups ) ) {
			foreach ( $groups as $group_id ) {
				$ld_auto_enroll_group_courses = get_post_meta( $group_id, 'ld_auto_enroll_group_courses', true );
				/**
				 * See settings in includes/settings/settings-metaboxes/class-ld-settings-metabox-group-courses-enroll.php
				 * If the checkbox is set then ALL courses can be used to enroll into group.
				 */
				if ( 'yes' === $ld_auto_enroll_group_courses ) {
					/**
					 * Filters whether to enroll into group for the course.
					 *
					 * @since 3.2.0
					 *
					 * @param boolean $enroll_in_group Whether to enroll the user into the group.
					 * @param integer $group_id        The Group ID.
					 * @param integer $course_id       The Course ID.
					 */
					if ( apply_filters( 'learndash_group_course_auto_enroll', true, $group_id, $course_id ) ) {
						ld_update_group_access( $user_id, $group_id );
					}
				} else {
					/**
					 * Else if the checkbox is not set and there are entries for the selective course enroll. Use those.
					 */
					$ld_auto_enroll_group_course_ids = get_post_meta( $group_id, 'ld_auto_enroll_group_course_ids', true );
					if ( ( is_array( $ld_auto_enroll_group_course_ids ) ) && ( ! empty( $ld_auto_enroll_group_course_ids ) ) ) {
						$ld_auto_enroll_group_course_ids = array_map( 'absint', $ld_auto_enroll_group_course_ids );

						if ( in_array( $course_id, $ld_auto_enroll_group_course_ids, true ) ) {
							/** This filter is documented in includes/course/ld-course-functions.php */
							if ( apply_filters( 'learndash_group_course_auto_enroll', true, $group_id, $course_id ) ) {
								ld_update_group_access( $user_id, $group_id );
							}
						}
					}
				}
			}
		}
	}
}
add_action( 'learndash_update_course_access', 'learndash_update_course_users_groups', 50, 4 );

/**
 * Gets the course completion date for a user.
 *
 * @since 2.4.7
 *
 * @param int $user_id   Optional. The ID of the user. Default 0.
 * @param int $course_id Optional. The ID of the course. Default 0.
 *
 * @return int The timestamp of when the course was completed. The value is 0 if the course is not completed.
 */
function learndash_user_get_course_completed_date( $user_id = 0, $course_id = 0 ) {
	$completed_on_timestamp = 0;
	if ( ( ! empty( $user_id ) ) && ( ! empty( $course_id ) ) ) {
		$completed_on_timestamp = get_user_meta( $user_id, 'course_completed_' . $course_id, true );

		if ( empty( $completed_on_timestamp ) ) {
			$activity_query_args = array(
				'post_ids'       => $course_id,
				'user_ids'       => $user_id,
				'activity_types' => 'course',
				'per_page'       => 1,
			);

			$activity = learndash_reports_get_activity( $activity_query_args );
			if ( ! empty( $activity['results'] ) ) {
				foreach ( $activity['results'] as $activity_item ) {
					if ( property_exists( $activity_item, 'activity_completed' ) ) {
						$completed_on_timestamp = $activity_item->activity_completed;

						// To make the next check easier we update the user meta.
						update_user_meta( $user_id, 'course_completed_' . $course_id, $completed_on_timestamp );
						break;
					}
				}
			}
		}
	}

	return $completed_on_timestamp;
}

/**
 * Gets the page data by page path.
 *
 * @since 2.5.2
 *
 * @param string $slug      Optional. The slug of the page. Default empty.
 * @param string $post_type Optional. The post type slug. Default empty.
 *
 * @return WP_Post|array|null `WP_Post` object or array on success, null on failure.
 */
function learndash_get_page_by_path( $slug = '', $post_type = '' ) {
	$course_post = null;

	if ( ( ! empty( $slug ) ) && ( ! empty( $post_type ) ) ) {

		$course_post = get_page_by_path( $slug, OBJECT, $post_type );

		if ( ( defined( 'ICL_LANGUAGE_CODE' ) ) && ( ICL_LANGUAGE_CODE != '' ) ) {
			if ( function_exists( 'icl_object_id' ) ) {
				$course_post = get_page( icl_object_id( $course_post->ID, $post_type, true, ICL_LANGUAGE_CODE ) );
			}
		}
	}

	return $course_post;
}

/**
 * Gets the course lessons per page setting.
 *
 * This function will initially source the per_page from the course. But if we are using the
 * default lesson options setting we will use that. Then if the lessons options
 * is not set for some reason we use the default system option 'posts_per_page'.
 *
 * @since 2.5.5
 *
 * @param int $course_id Optional. The ID of the course. Default 0.
 *
 * @return int The number of lessons per page or 0.
 */
function learndash_get_course_lessons_per_page( $course_id = 0 ) {
	$course_lessons_per_page = 0;

	// From the WP > Settings > Reading > Posts per page.
	$course_lessons_per_page = (int) get_option( 'posts_per_page' );

	// From the LearnDash > Settings > General > Global Pagination Settings > Shortcodes & Widgets per page.
	$course_lessons_per_page = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page', $course_lessons_per_page );

	// From the LearnDash > Courses > Settings > Global Course Management > Course Table Pagination > Lessons per page.
	$course_settings = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Courses_Management_Display' );
	if ( ( isset( $course_settings['course_pagination_enabled'] ) ) && ( 'yes' === $course_settings['course_pagination_enabled'] ) ) {
		if ( isset( $course_settings['course_pagination_lessons'] ) ) {
			$course_lessons_per_page = $course_settings['course_pagination_lessons'];
		} elseif ( isset( $course_settings['posts_per_page'] ) ) {
			$course_lessons_per_page = $course_settings['posts_per_page'];
		}
	}

	// From the specific Course Settings > Custom Pagination.
	if ( ! empty( $course_id ) ) {
		$course_settings = learndash_get_setting( intval( $course_id ) );
		if ( ( isset( $course_settings['course_lesson_per_page'] ) ) && ( 'CUSTOM' === $course_settings['course_lesson_per_page'] ) && ( isset( $course_settings['course_lesson_per_page_custom'] ) ) ) {
			$course_lessons_per_page = $course_settings['course_lesson_per_page_custom'];
		}
	}

	return absint( $course_lessons_per_page );
}

/**
 * Called from within the Course Lessons List processing query SFWD_CPT::loop_shortcode.
 * This action will setup a global pager array to be used in templates.
 */

$course_pager_results = array( 'pager' => array() );
global $course_pager_results;

/**
 * Handles the course lessons list pager.
 *
 * @since 2.5.5
 *
 * Fires on `learndash_course_lessons_list_pager` hook.
 *
 * @global array $course_pager_results
 *
 * @param WP_Query|null $query_result  Optional. Course lesson list `WP_Query` object. Default null.
 * @param string        $pager_context Optional. The context where pagination is shown. Default empty.
 */
function learndash_course_lessons_list_pager( $query_result = null, $pager_context = '' ) {
	global $course_pager_results;

	$course_pager_results['pager']['paged'] = 1;
	if ( ( isset( $query_result->query_vars['paged'] ) ) && ( $query_result->query_vars['paged'] > 1 ) ) {
		$course_pager_results['pager']['paged'] = $query_result->query_vars['paged'];
	}

	$course_pager_results['pager']['total_items'] = absint( $query_result->found_posts );
	$course_pager_results['pager']['total_pages'] = absint( $query_result->max_num_pages );
}
add_action( 'learndash_course_lessons_list_pager', 'learndash_course_lessons_list_pager', 10, 2 );

/**
 * Gets the lesson topic pagination values from HTTP get global array.
 *
 * @since 3.0.0
 *
 * @return array An array of lesson topic pagination values.
 */
function learndash_get_lesson_topic_paged_values() {
	$paged_values = array(
		'lesson' => 0,
		'paged'  => 1,
	);
	if ( ( isset( $_GET['ld-topic-page'] ) ) && ( ! empty( $_GET['ld-topic-page'] ) ) ) {
		list( $paged_values['lesson'], $paged_values['paged'] ) = explode( '-', $_GET['ld-topic-page'] );
		$paged_values['lesson']                                 = absint( $paged_values['lesson'] );
		$paged_values['paged']                                  = absint( $paged_values['paged'] );
		if ( $paged_values['paged'] < 1 ) {
			$paged_values['paged'] = 1;
		}
		if ( ( empty( $paged_values['lesson'] ) ) || ( empty( $paged_values['paged'] ) ) ) {
			$paged_values = array(
				'lesson' => 0,
				'paged'  => 1,
			);
		}
	}

	return $paged_values;
}

/**
 * Processes the lesson topics pagination.
 *
 * @since 3.0.0
 *
 * @global array $course_pager_results
 *
 * @param array $topics Optional. An array of topics. Default empty array.
 * @param array $args {
 *    An array of lesson topic pager arguments. Default empty array.
 *
 *    @type int $course_id Course ID.
 *    @type int $lesson_id Lesson ID.
 * }
 *
 * @return array An array of paged topics.
 */
function learndash_process_lesson_topics_pager( $topics = array(), $args = array() ) {
	global $course_pager_results;

	$paged_values = learndash_get_lesson_topic_paged_values();

	if ( ! empty( $topics ) ) {
		if ( ! isset( $args['per_page'] ) ) {
			$topics_per_page = learndash_get_course_topics_per_page( $args['course_id'], $args['lesson_id'] );
		} else {
			$topics_per_page = intval( $args['per_page'] );
		}
		if ( ( $topics_per_page > 0 ) && ( count( $topics ) > $topics_per_page ) ) {
			$topics_chunks = array_chunk( $topics, $topics_per_page );

			$course_pager_results[ $args['lesson_id'] ]          = array();
			$course_pager_results[ $args['lesson_id'] ]['pager'] = array();

			$topics_paged = 1;

			if ( ( ! empty( $paged_values['lesson'] ) ) && ( $paged_values['lesson'] == $args['lesson_id'] ) ) {
				$topics_paged = $paged_values['paged'];
			} elseif ( get_post_type() === learndash_get_post_type_slug( 'topic' ) ) {
				/**
				 * If we are viewing a Topic and the page is empty we load the
				 * paged set to show the current topic item.
				 */
				foreach ( $topics_chunks as $topics_chunk_page => $topics_chunk_set ) {
					$topics_ids = array_values( wp_list_pluck( $topics_chunk_set, 'ID' ) );

					if ( ( ! empty( $topics_ids ) ) && ( in_array( (int) get_the_ID(), array_map( 'absint', $topics_ids ), true ) ) ) {
						$topics_paged = ++$topics_chunk_page;
						break;
					}
				}
			} elseif ( get_post_type() === learndash_get_post_type_slug( 'quiz' ) ) {
				$parent_step_ids = learndash_course_get_all_parent_step_ids( $args['course_id'], get_the_ID() );
				if ( ! empty( $parent_step_ids ) ) {
					$parent_step_ids = array_map( 'absint', $parent_step_ids );
					$parent_step_ids = array_reverse( $parent_step_ids );

					if ( get_post_type( $parent_step_ids[0] ) === learndash_get_post_type_slug( 'topic' ) ) {
						// If the Quiz has a Topic parent we loop through the topic chunks to find the parent.
						foreach ( $topics_chunks as $topics_chunk_page => $topics_chunk_set ) {
							$topics_ids = array_values( wp_list_pluck( $topics_chunk_set, 'ID' ) );
							if ( ( ! empty( $topics_ids ) ) && ( in_array( $parent_step_ids[0], $topics_ids ) ) ) {
								$topics_paged = ++$topics_chunk_page;
								break;
							}
						}
					} elseif ( get_post_type( $parent_step_ids[0] ) === learndash_get_post_type_slug( 'lesson' ) ) {
						/**
						 * If the Quiz has a LEsson parent we just set the last Topic chunk set because
						 * Lesson Quizzes are shown at the end.
						 */
						$topics_paged = count( $topics_chunks );
					}
				}
			}

			$course_pager_results[ $args['lesson_id'] ]['pager']['paged'] = $topics_paged;

			$course_pager_results[ $args['lesson_id'] ]['pager']['total_items'] = count( $topics );
			$course_pager_results[ $args['lesson_id'] ]['pager']['total_pages'] = count( $topics_chunks );

			$topics = $topics_chunks[ $topics_paged - 1 ];
		}
	}

	return $topics;
}

/**
 * Gets the course lessons order query arguments.
 *
 * The course lessons order can be set in the course or globally defined in
 * the lesson options. This function will check all logic and return the
 * correct setting.
 *
 * @since 2.5.8
 *
 * @param int $course_id Optional. The ID of the course. Default 0.
 *
 * @return array An array of course lessons order query arguments.
 */
function learndash_get_course_lessons_order( $course_id = 0 ) {
	$course_lessons_args = array(
		'order'   => '',
		'orderby' => '',
	);

	if ( learndash_is_course_shared_steps_enabled() ) {
		$course_lessons_args['orderby'] = 'post__in';
		return $course_lessons_args;

	} else {
		$lessons_options = learndash_get_option( 'sfwd-lessons' );
		if ( ( isset( $lessons_options['order'] ) ) && ( ! empty( $lessons_options['order'] ) ) ) {
			$course_lessons_args['order'] = $lessons_options['order'];
		}

		if ( ( isset( $lessons_options['orderby'] ) ) && ( ! empty( $lessons_options['orderby'] ) ) ) {
			$course_lessons_args['orderby'] = $lessons_options['orderby'];
		}
	}

	if ( ! empty( $course_id ) ) {
		$course_settings = learndash_get_setting( $course_id );
		if ( ( isset( $course_settings['course_lesson_order'] ) ) && ( ! empty( $course_settings['course_lesson_order'] ) ) ) {
			$course_lessons_args['order'] = $course_settings['course_lesson_order'];
		}

		if ( ( isset( $course_settings['course_lesson_orderby'] ) ) && ( ! empty( $course_settings['course_lesson_orderby'] ) ) ) {
			$course_lessons_args['orderby'] = $course_settings['course_lesson_orderby'];
		}
	}

	/**
	 * Filters course lessons order query arguments.
	 *
	 * @param array $course_lesson_args An array of course lesson order arguments.
	 * @param int   $course_id          Course ID.
	 */
	return apply_filters( 'learndash_course_lessons_order', $course_lessons_args, $course_id );
}

/**
 * Converts and gets the course access list.
 *
 * The function converts the standard comma-separated list of user IDs
 * used for the course_access_list field. The conversion is to trim and ensure
 * the values are integer and not empty.
 *
 * @since 2.5.9
 *
 * @param string|array $course_access_list Optional. String of comma separated user IDs or array. Default empty.
 * @param boolean      $return_array       Optional. Whether to return array. True to return array and false to return string. Default false.
 *
 * @return string|array The list of user IDs.
 */
function learndash_convert_course_access_list( $course_access_list = '', $return_array = false ) {
	if ( ! empty( $course_access_list ) ) {

		// Convert the comma separated list into an array.
		if ( is_string( $course_access_list ) ) {
			$course_access_list = explode( ',', $course_access_list );
		}

		// Now normalize the array elements.
		if ( is_array( $course_access_list ) ) {
			$course_access_list = array_map( 'absint', $course_access_list );
			$course_access_list = array_unique( $course_access_list, SORT_NUMERIC );
			$course_access_list = array_diff( $course_access_list, array( 0 ) );
		}

		// Prepare the return value.
		if ( true !== $return_array ) {
			$course_access_list = implode( ',', $course_access_list );
		}
	} elseif ( true === $return_array ) {
		$course_access_list = array();
	}

	return $course_access_list;
}

/**
 * Determines the number of lesson topics to display per page.
 *
 * @since 3.0.0
 *
 * @param int $course_id Optional. Parent Course ID. Default 0.
 * @param int $lesson_id Optional. Parent Lesson ID. Default 0.
 *
 * @return int The number of lesson topics per page.
 */
function learndash_get_course_topics_per_page( $course_id = 0, $lesson_id = 0 ) {
	$course_topics_per_page = 0;

	// From the WP > Settings > Reading > Posts per page.
	$course_topics_per_page = intval( get_option( 'posts_per_page' ) );

	// From the LearnDash > Settings > General > Global Pagination Settings > Shortcodes & Widgets per page.
	$course_topics_per_page = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page', $course_topics_per_page );

	// From the LearnDash > Courses > Settings > Global Course Management > Course Table Pagination > Lessons per page.
	$course_settings = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Courses_Management_Display' );
	if ( ( isset( $course_settings['course_pagination_enabled'] ) ) && ( 'yes' === $course_settings['course_pagination_enabled'] ) ) {
		if ( isset( $course_settings['course_pagination_topics'] ) ) {
			$course_topics_per_page = absint( $course_settings['course_pagination_topics'] );
		} elseif ( isset( $course_settings['posts_per_page'] ) ) {
			$course_topics_per_page = absint( $course_settings['posts_per_page'] );
		}
	}

	// From the specific Course Settings > Custom Pagination.
	if ( ! empty( $course_id ) ) {
		$course_settings = learndash_get_setting( intval( $course_id ) );
		if ( ( isset( $course_settings['course_lesson_per_page'] ) ) && ( 'CUSTOM' === $course_settings['course_lesson_per_page'] ) && ( isset( $course_settings['course_topic_per_page_custom'] ) ) ) {
			$course_topics_per_page = absint( $course_settings['course_topic_per_page_custom'] );
		}
	}

	return $course_topics_per_page;
}

/**
 * Checks whether to use the legacy course access list.
 *
 * @since 3.1.0
 *
 * @return boolean Returns true to use legacy course access list otherwise false.
 */
function learndash_use_legacy_course_access_list() {
	$use_legacy_course_access_list = true;

	$data_course_access_convert = learndash_data_upgrades_setting( 'course-access-lists-convert' );
	if ( $data_course_access_convert ) {
		$use_legacy_course_access_list = false;

	}
	/**
	 * Filters whether to use legacy course access list or not.
	 *
	 * @param boolean $use_legacy_course_access_list Whether to use legacy course access list.
	 */
	return apply_filters( 'learndash_use_legacy_course_access_list', $use_legacy_course_access_list );
}

/**
 * Gets the user's last active (last updated) course ID.
 *
 * @since 3.1.4
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int $user_id Optional. User ID. Default 0.
 *
 * @return int The last active course ID.
 */
function learndash_get_last_active_course( $user_id = 0 ) {
	global $wpdb;

	$last_course_id = 0;

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( ! empty( $user_id ) ) {
		$query_result   = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT post_id FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . " WHERE user_id=%d AND activity_type='course' AND activity_status = 0 AND activity_completed = '' ORDER BY activity_updated DESC",
				$user_id
			)
		);
		$last_course_id = absint( $query_result );
	}

	return $last_course_id;
}


/**
 * Gets the user's last active step for a course.
 *
 * @since 3.1.4
 *
 * @param int $user_id   Optional. User ID. Default 0.
 * @param int $course_id Optional. Course ID. Default 0.
 *
 * @return int The last active course step ID.
 */
function learndash_user_course_last_step( $user_id = 0, $course_id = 0 ) {
	global $wpdb;

	$last_course_step_id = 0;

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( ! empty( $user_id ) ) {
		if ( empty( $course_id ) ) {
			$course_id = learndash_get_last_active_course( $user_id );
		}
		if ( ! empty( $course_id ) ) {
			$query_result        = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT user_activity_meta.activity_meta_value FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' as user_activity INNER JOIN ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity_meta' ) ) . " as user_activity_meta ON user_activity.activity_id = user_activity_meta.activity_id WHERE user_activity.user_id=%d AND user_activity.post_id=%d AND user_activity.activity_type='course' AND user_activity_meta.activity_meta_key= 'steps_last_id' ORDER BY activity_updated DESC",
					$user_id,
					$course_id
				)
			);
			$last_course_step_id = absint( $query_result );
		}
	}

	return $last_course_step_id;
}


/**
 * Check if user can bypass action ($context).
 *
 * @since 3.2.0
 *
 * @param int    $user_id User ID.
 * @param string $context The specific action to check for.
 * @param array  $args Optional array of args related to the
 * context. Typically starting with an step ID, Course ID, etc.
 * @return bool True if user can bypass. Otherwise false.
 */
function learndash_can_user_bypass( $user_id = 0, $context = 'learndash_course_progression', $args = array() ) {
	if ( empty( $user_id ) ) {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}
	}

	$can_bypass = false;
	if ( ! empty( $user_id ) ) {
		if ( ( learndash_is_admin_user( $user_id ) ) && ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'bypass_course_limits_admin_users' ) ) ) {
			$can_bypass = true;
		} elseif ( ( learndash_is_group_leader_user( $user_id ) ) && ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'bypass_course_limits' ) ) ) {
			$can_bypass = true;
		}
	}

	/**
	 * Filters user can bypass logic.
	 *
	 * @since 3.2.0
	 *
	 * @param boolean $can_bypass Whether the user can bypass $context.
	 * @param int     $user_id    User ID.
	 * @param string  $context The specific action to check for.
	 * @param array  $args Optional array of args related to the
	 * context. Typically starting with an step ID, Course ID, etc.
	 */
	$can_bypass = apply_filters( 'learndash_user_can_bypass', $can_bypass, $user_id, $context, $args );

	return $can_bypass;
}

/**
 * Check if user can auto-enroll in courses..
 *
 * @since 3.2.3
 *
 * @param int $user_id User ID.
 * @return bool True if user can auto-enroll.
 */
function learndash_can_user_autoenroll_courses( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}
	}

	$auto_enroll = false;
	if ( ! empty( $user_id ) ) {
		if ( learndash_is_admin_user( $user_id ) ) {
			$auto_enroll = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' );
		} elseif ( learndash_is_group_leader_user( $user_id ) ) {
			if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'courses_autoenroll' ) ) {
				$auto_enroll = 'yes';
			}
		}
	}

	if ( 'yes' === $auto_enroll ) {
		$auto_enroll = true;
	} else {
		$auto_enroll = false;
	}

	/**
	 * Filters whether to auto enroll a user into a course or not.
	 *
	 * @since 2.3.0
	 *
	 * @param boolean $auto_enroll Whether to auto enroll user or not.
	 * @param int     $user_id     ID of the logged in user to check.
	 */
	return apply_filters( 'learndash_override_course_auto_enroll', $auto_enroll, $user_id );
}

/**
 * Utility function to check if Course Builder is enabled.
 *
 * @since 3.4.0
 */
function learndash_is_course_builder_enabled() {
	if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Management_Display', 'course_builder_enabled' ) ) {
		return true;
	}

	return false;
}

/**
 * Utility function to check if Course Shared steps is enabled.
 *
 * @since 3.4.0
 */
function learndash_is_course_shared_steps_enabled() {
	if ( ( true === learndash_is_course_builder_enabled() ) && ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Management_Display', 'course_builder_shared_steps' ) ) ) {
		return true;
	}

	return false;
}

/**
 * Get Courses/Groups by Price Type.
 *
 * @since 3.4.1
 *
 * @param string  $post_type        Post Type slug: sfwd-courses or group.
 * @param string  $price_type       Price Type: open, free, closed, paynow, etc.
 * @param boolean $bypass_transient Optional. Whether to bypass transient cache. Default false.
 *
 * @return @array Array of Course IDs.
 */
function learndash_get_posts_by_price_type( $post_type = '', $price_type = '', $bypass_transient = false ) {
	global $wpdb;

	$post_ids   = array();
	$post_type  = esc_attr( $post_type );
	$price_type = esc_attr( $price_type );

	if ( ( ! empty( $post_type ) ) && ( in_array( $post_type, learndash_get_post_type_slug( array( 'course', 'group' ) ), true ) ) ) {

		$new_logic = false;
		if ( learndash_get_post_type_slug( 'course' ) === $post_type ) {
			if ( empty( $price_type ) ) {
				$price_type = LEARNDASH_DEFAULT_COURSE_PRICE_TYPE;
			}

			$transient_key = 'learndash_' . $price_type . '_courses';

		} elseif ( learndash_get_post_type_slug( 'group' ) === $post_type ) {
			if ( empty( $price_type ) ) {
				$price_type = LEARNDASH_DEFAULT_GROUP_PRICE_TYPE;
			}

			$transient_key = 'learndash_' . $price_type . '_groups';
		}

		if ( ! $bypass_transient ) {
			$post_ids_transient = LDLMS_Transients::get( $transient_key );
		} else {
			$post_ids_transient = false;
		}

		if ( false === $post_ids_transient ) {
			if ( learndash_post_meta_processed( $post_type ) ) {
				$price_query_args = array(
					'post_type'    => $post_type,
					'fields'       => 'ids',
					'nopaging'     => true,
					'meta_key'     => '_ld_price_type',
					'meta_value'   => $price_type,
					'meta_compare' => '=',
				);

				$price_query = new WP_Query( $price_query_args );
				if ( ( property_exists( $price_query, 'posts' ) ) && ( ! empty( $price_query->posts ) ) ) {
					$post_ids = $price_query->posts;
					$post_ids = array_map( 'absint', $post_ids );
				}
			} else {
				$sql_str = $wpdb->prepare(
					"SELECT postmeta.post_id as post_id FROM {$wpdb->postmeta} as postmeta
						INNER JOIN {$wpdb->posts} as posts ON posts.ID = postmeta.post_id
						WHERE posts.post_status='publish' AND posts.post_type=%s AND postmeta.meta_key=%s
						AND ( postmeta.meta_value REGEXP '\"" . $post_type . '_' . learndash_get_post_type_key( $post_type ) . '_price_type";s:' . strlen( $price_type ) . ':"' . $price_type . "\";' )",
					$post_type,
					'_' . $post_type
				);

				$post_ids = $wpdb->get_col( $sql_str );
			}
			if ( ! empty( $post_ids ) ) {
				$post_ids = array_map( 'absint', $post_ids );
			}
			LDLMS_Transients::set( $transient_key, $post_ids, MINUTE_IN_SECONDS );

		} else {
			$post_ids = $post_ids_transient;
		}
	}

	return $post_ids;
}

/**
 * Checks if Post Meta Data Upgrade has completed.
 *
 * @since 3.4.1
 *
 * @param string $post_type The post type slug to check.
 *
 * @return boolean.
 */
function learndash_post_meta_processed( $post_type = '' ) {
	if ( ( ! empty( $post_type ) ) && ( learndash_is_valid_post_type( $post_type ) ) ) {
		$data_settings = learndash_data_upgrades_setting( learndash_get_post_type_key( $post_type ) . '-post-meta' );
		if ( ( ! isset( $data_settings['process_status'] ) ) || ( 'complete' !== $data_settings['process_status'] ) ) {
			$post_meta_processed = false;
		} else {
			$post_meta_processed = true;
		}

		/**
		 * Filters whether to post meta is processed or not.
		 *
		 * @since 3.4.1
		 *
		 * @param boolean $process   True or False to process post meta.
		 * @param string  $post_type The post type slug.
		 */
		return apply_filters( 'learndash_post_meta_processed', $post_meta_processed, $post_type );
	}
	return false;
}

/**
 * Returns true if it's a course post.
 *
 * @param WP_Post|int|null $post Post or Post ID.
 *
 * @since 4.1.0
 *
 * @return bool
 */
function learndash_is_course_post( $post ): bool {
	if ( empty( $post ) ) {
		return false;
	}

	$post_type = is_a( $post, WP_Post::class ) ? $post->post_type : get_post_type( $post );

	return LDLMS_Post_Types::get_post_type_slug( 'course' ) === $post_type;
}

/**
 * Returns course enrollment url.
 *
 * @param WP_Post|int|null $post Post or Post ID.
 *
 * @since 4.1.0
 *
 * @return string
 */
function learndash_get_course_enrollment_url( $post ): string {
	if ( empty( $post ) ) {
		return '';
	}

	if ( is_int( $post ) ) {
		$post = get_post( $post );

		if ( is_null( $post ) ) {
			return '';
		}
	}

	$url = get_permalink( $post );

	$settings = learndash_get_setting( $post );

	if ( 'paynow' === $settings['course_price_type'] && ! empty( $settings['course_price_type_paynow_enrollment_url'] ) ) {
		$url = $settings['course_price_type_paynow_enrollment_url'];
	} elseif ( 'subscribe' === $settings['course_price_type'] && ! empty( $settings['course_price_type_subscribe_enrollment_url'] ) ) {
		$url = $settings['course_price_type_subscribe_enrollment_url'];
	}

	/** This filter is documented in includes/course/ld-course-functions.php */
	return apply_filters( 'learndash_course_join_redirect', $url, $post->ID );
}
