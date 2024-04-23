<?php
/**
 * TutorLMS integration filters
 *
 * @package BuddyBoss\TutorLMS
 * @since 2.4.40
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'bb_feed_excluded_post_types', 'bb_feed_not_allowed_tutorlms_post_types' );
add_filter( 'tutor_course_filter_args', 'bb_tutor_course_filter_args' );
add_filter( 'bp_repair_list', 'bb_migrate_tutor_group_course' );
add_filter( 'bb_nouveau_get_activity_entry_bubble_buttons', 'bb_nouveau_remove_edit_activity_entry_buttons', 999, 2 );
add_filter( 'bp_is_post_type_feed_enable', 'bb_tutorlms_post_type_feed_is_enable', 10, 3 );
add_filter( 'bb_enable_blog_feed', 'bb_enable_existing_blog_feed_option', 10, 2 );

/**
 * Function to exclude TutorLMS CPT from Activity setting screen.
 *
 * @since 2.4.40
 *
 * @param array $post_types Array of post types.
 *
 * @return array
 */
function bb_feed_not_allowed_tutorlms_post_types( $post_types ) {

	$bb_tutorlms_posttypes = ! empty( bb_tutorlms_get_post_types() ) ? bb_tutorlms_get_post_types() : array();

	if ( ! empty( $post_types ) ) {
		$post_types = array_merge( $post_types, $bb_tutorlms_posttypes );
	} else {
		$post_types = $bb_tutorlms_posttypes;
	}

	return $post_types;
}

/**
 * Add parameter for load ajax course data.
 *
 * @since 2.4.40
 *
 * @param array $args Array of args.
 *
 * @return array
 */
function bb_tutor_course_filter_args( $args ) {

	if (
		function_exists( 'bp_is_group' ) &&
		! bp_is_group() &&
		function_exists( 'bbp_is_single_user' ) &&
		! bbp_is_single_user()
	) {
		return $args;
	}

	if ( function_exists( 'bp_is_group' ) && bp_is_group() ) {
		$group_id = bp_get_current_group_id();
		if ( ! $group_id ) {
			return $args;
		}

		$bb_tutorlms_groups = bb_load_tutorlms_group()->get(
			array(
				'group_id' => $group_id,
				'fields'   => 'course_id',
				'per_page' => false,
			)
		);

		if ( ! empty( $bb_tutorlms_groups['courses'] ) ) {
			$args['post__in'] = $bb_tutorlms_groups['courses'];
		}
	} elseif ( function_exists( 'bbp_is_single_user' ) && bbp_is_single_user() ) {
		$current_tab = bp_current_action();
		if ( 'enrolled-courses' === $current_tab ) {
			$courses = bb_tutorlms_get_enrolled_courses();
		} elseif ( 'instructor-courses' === $current_tab ) {
			$courses = bb_tutorlms_get_instructor_courses();
		}

		if ( ! empty( $courses ) ) {
			if ( ! empty( $courses->posts ) ) {
				$courses = $courses->posts;
			}
			$course_ids = array();
			if ( is_array( $courses ) ) {
				foreach ( $courses as $course ) {
					if ( is_numeric( $course ) ) {

						// Check if the item is a numeric ID (integer or string representation of an integer)
						$course_ids[] = intval( $course );
					} elseif ( is_object( $course ) ) {

						// If the item is a object, you can access its ID property.
						$course_ids[] = $course->ID;
					}
				}
			}
			$args['post__in'] = $course_ids;
		}

	}

	return $args;
}

/**
 * Migrate TutorLMS buddypress group courses to TutorLMS buddyboss group courses.
 *
 * @since 2.4.40
 *
 * @param array $repair_list Repair List.
 *
 * @return array
 */
function bb_migrate_tutor_group_course( $repair_list ) {
	if (
		! bp_is_active( 'groups' ) ||
		! bb_tutorlms_enable()
	) {
		return $repair_list;
	}

	if ( bp_get_option( 'bb_migration_tutorlms_buddypress_group_course' ) ) {
		return $repair_list;
	}

	$repair_list[112] = array(
		'bp-migrate-tutorlms-buddypress-group-course',
		esc_html__( 'Migrate BuddyPress group courses to BuddyBoss group courses for TutorLMS', 'buddyboss-pro' ),
		'bb_migration_tutorlms_buddypress_group_course',
		(
			isset( $_GET['tool'] ) &&
			'bp-migrate-tutorlms-buddypress-group-course' === $_GET['tool']
		),
	);

	return $repair_list;
}

/**
 * Migrate courses which is attached in the Buddypress groups to BuddyBoss groups.
 *
 * @since 2.4.40
 *
 * @return array
 */
function bb_migration_tutorlms_buddypress_group_course() {
	global $wpdb, $bp;

	if ( ! bp_is_active( 'groups' ) || ! bb_tutorlms_enable() ) {
		return array();
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$offset = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;

	// Fetch groups data.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$groups_data = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT g.id,
            GROUP_CONCAT( DISTINCT ( CASE WHEN gm.meta_key = '_tutor_attached_course' THEN gm.meta_value END ) ) AS attached_courses,
            CASE WHEN gm1.meta_key = '_tutor_bp_group_activities' THEN gm1.meta_value END AS group_activities
	        FROM {$bp->groups->table_name} g 
	        LEFT JOIN {$bp->groups->table_name_groupmeta} gm ON g.id = gm.group_id
	        LEFT JOIN {$bp->groups->table_name_groupmeta} gm1 ON g.id = gm1.group_id
	        WHERE gm.meta_key = '_tutor_attached_course' OR gm1.meta_key = '_tutor_bp_group_activities'
	        GROUP BY g.id
	        ORDER BY g.id DESC LIMIT 20 OFFSET %d", $offset
		),
		ARRAY_A
	);
	if ( ! empty( $groups_data ) ) {
		foreach ( $groups_data as $group_data ) {
			$attached_courses = isset( $group_data['attached_courses'] ) ? explode( ',', $group_data['attached_courses'] ) : '';
			if ( ! empty( $attached_courses ) ) {
				// Get existing group course ids.
				$existing_course_data = bb_load_tutorlms_group()->get(
					array(
						'group_id' => $group_data['id'],
						'fields'   => 'course_id',
					)
				);
				$existing_course_ids  = ! empty( $existing_course_data['courses'] ) ? $existing_course_data['courses'] : array();
				if ( ! empty( $existing_course_ids ) ) {
					$attached_courses = array_unique( array_merge( $attached_courses, $existing_course_ids ) );
				}
				static $static_enable_tutor_bp = array();
				foreach ( $attached_courses as $key => $course_id ) {
					if ( ! isset( $static_enable_tutor_bp[ $course_id ] ) ) {
						$static_enable_tutor_bp[ $course_id ] = (bool) tutor_utils()->get_course_settings( $course_id, 'enable_tutor_bp' );
					}
					$enable_tutor_bp = $static_enable_tutor_bp[ $course_id ];
					if ( ! $enable_tutor_bp ) {
						unset( $attached_courses[ $key ] );
					}
				}

				if ( ! empty( $attached_courses ) && count( $attached_courses ) > 0 ) {
					groups_update_groupmeta( $group_data['id'], 'bb-tutorlms-group-course-is-enable', true );

					bb_load_tutorlms_group()->add(
						array(
							'group_id'  => $group_data['id'],
							'course_id' => $attached_courses,
						)
					);
				}
			}

			$group_activities = isset( $group_data['group_activities'] ) ? maybe_unserialize( $group_data['group_activities'] ) : '';
			if ( ! empty( $group_activities ) ) {
				$output_array = array_map( function ( $key ) {
					return 'bb_tutorlms_' . $key;
				}, array_keys( $group_activities ) );

				$result_array = array_combine( $output_array, $group_activities );
				groups_update_groupmeta( $group_data['id'], 'bb-tutorlms-groups-courses-activities', $result_array );
			}

			$offset ++;
		}

		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => sprintf(
				/* translators: %s: number of groups */
				esc_html__( '%s groups updated successfully.', 'buddyboss-pro' ),
				bp_core_number_format( $offset )
			),
		);
	}

	$statement = esc_html__( 'TutorLMS courses successfully updated from BuddyPress to BuddyBoss for each group &hellip; %s', 'buddyboss-pro' );

	bp_update_option( 'bb_migration_tutorlms_buddypress_group_course', true );

	return array(
		'status'  => 1,
		'message' => sprintf( $statement, esc_html__( 'Complete!', 'buddyboss-pro' ) ),
	);
}

/**
 * We're removing the Edit Button for TutorLMS activity.
 *
 * @since 2.4.40
 *
 * @param array $buttons     Buttons Argument.
 * @param int   $activity_id Activity ID.
 *
 * @return array
 */
function bb_nouveau_remove_edit_activity_entry_buttons( $buttons, $activity_id ) {
	if (
		! bp_is_active( 'groups' ) ||
		! (
			function_exists( 'tutor' ) &&
			bb_tutorlms_enable()
		)
	) {
		return $buttons;
	}

	$bb_tutor_actions = array_keys( bb_tutorlms_course_activities() );

	// Also, check buddypress actions.
	$bp_tutor_actions = array(
		'_tutor_course_enrolled',
		'_tutor_course_started',
		'_tutor_course_completed',
		'_tutor_lesson_creates',
		'_tutor_lesson_updated',
		'_tutor_quiz_started',
		'_tutor_quiz_finished',
	);

	// Merge tutorlms buddypress and buddyboss actions.
	$exclude_action_arr = array_merge( $bb_tutor_actions, $bp_tutor_actions );
	if ( bp_is_activity_edit_enabled() ) {
		add_filter( 'bp_activity_generate_action_string', '__return_false', 999, 2 );
		$activity = new BP_Activity_Activity( $activity_id );
		remove_filter( 'bp_activity_generate_action_string', '__return_false', 999, 2 );
		if ( in_array( $activity->action, $exclude_action_arr, true ) ) {
			unset( $buttons['activity_edit'] );
		}
	}

	return $buttons;
}

/**
 * Function to check if TutorLMS integration is disabled then activity should
 * not be record for any new TutorLMS post type.
 *
 * @since 2.4.40
 *
 * @param bool   $retval    Current value.
 * @param string $post_type Post type.
 * @param bool   $default   Default value.
 *
 * @return bool
 */
function bb_tutorlms_post_type_feed_is_enable( $retval, $post_type = '', $default = false ) {
	if (
		! empty( $post_type ) &&
		in_array( $post_type, bb_tutorlms_get_post_types() ) &&
		(
			! bp_is_active( 'groups' ) ||
			! bb_tutorlms_enable()
		)
	) {
		return false;
	}

	return $retval;
}

/**
 * Function to check existing blog is enabled or not.
 *
 * @since 2.4.40
 *
 * @param bool   $retval    Current value.
 * @param string $post_type Post type.
 *
 * @return bool
 */
function bb_enable_existing_blog_feed_option( $retval, $post_type ) {
	if (
		bp_is_active( 'groups' ) &&
		! empty( $post_type ) &&
		in_array( $post_type, bb_tutorlms_get_post_types() ) &&
		bb_tutorlms_enable() &&
		bp_get_option( bb_post_type_feed_option_name( $post_type ) )
	) {
		return true;
	}

	return $retval;
}
