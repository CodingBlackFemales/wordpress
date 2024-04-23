<?php
/**
 * TutorLMS integration helpers.
 *
 * @since 2.4.40
 *
 * @package BuddyBoss\TutorLMS
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns TutorLMS Integration url.
 *
 * @since 2.4.40
 *
 * @param string $path Path to tutorlms integration.
 *
 * @return string
 */
function bb_tutorlms_integration_url( $path = '' ) {
	return trailingslashit( bb_platform_pro()->integration_url ) . 'tutorlms/' . trim( $path, '/\\' );
}

/**
 * Returns TutorLMS Integration path.
 *
 * @since 2.4.40
 *
 * @param string $path Path to tutorlms integration.
 *
 * @return string
 */
function bb_tutorlms_integration_path( $path = '' ) {
	return trailingslashit( bb_platform_pro()->integration_dir ) . 'tutorlms/' . trim( $path, '/\\' );
}

/**
 * Get TutorLMS settings.
 *
 * @since 2.4.40
 *
 * @param string $keys    Optional. Get setting by key.
 * @param string $default Optional. Default value if value or setting not available.
 *
 * @return array|string
 */
function bb_get_tutorlms_settings( $keys = '', $default = '' ) {
	$settings = bp_get_option( 'bb-tutorlms', array() );

	if ( ! empty( $keys ) ) {
		if ( is_string( $keys ) ) {
			$keys = explode( '.', $keys );
		}

		foreach ( $keys as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$settings = $settings[ $key ];
			} else {
				return $default;
			}
		}
	} elseif ( empty( $settings ) ) {
		$settings = array();
	}

	/**
	 * Filters TutorLMS get settings.
	 *
	 * @since 2.4.40
	 *
	 * @param array  $settings Settings of tutorlms.
	 * @param string $keys     Optional. Get setting by key.
	 * @param string $default  Optional. Default value if value or setting not available.
	 */
	return apply_filters( 'bb_get_tutorlms_settings', $settings, $keys, $default );
}

/**
 * Checks if TutorLMS enable.
 *
 * @since 2.4.40
 *
 * @param integer $default TutorLMS enabled by default.
 *
 * @return bool Is TutorLMS enabled or not.
 */
function bb_tutorlms_enable( $default = 0 ) {

	/**
	 * Filters TutorLMS enabled settings.
	 *
	 * @since 2.4.40
	 *
	 * @param integer $default TutorLMS enabled by default.
	 */
	return (bool) apply_filters( 'bb_tutorlms_enable', bb_get_tutorlms_settings( 'bb-tutorlms-enable', $default ) );
}

/**
 * Function to return all TutorLMS post types.
 *
 * @since 2.4.40
 *
 * @return array
 */
function bb_tutorlms_get_post_types() {
	if ( ! function_exists( 'tutor' ) ) {
		return array();
	}

	$tutorlms_post_types = array(
		tutor()->course_post_type,
		tutor()->lesson_post_type,
		tutor()->quiz_post_type,
		tutor()->assignment_post_type,
	);

	$monetize_by = function_exists( 'tutor_utils' ) ? tutor_utils()->get_option( 'monetize_by' ) : '';
	if ( ! empty( $monetize_by ) && 'wc' === $monetize_by ) {
		$tutorlms_post_types[] = 'course-bundle';
	}

	/**
	 * Filters for TutorLMS post types.
	 *
	 * @since 2.4.40
	 *
	 * @param array $tutorlms_post_types TutorLMS post types.
	 */
	return apply_filters( 'bb_tutorlms_get_post_types', $tutorlms_post_types );
}

/**
 * Function to get published TutorLMS courses.
 *
 * @since 2.4.40
 *
 * @param array $args Array of args.
 *
 * @return false|mixed|null|object
 */
function bb_tutorlms_get_courses( $args = array() ) {
	if ( ! function_exists( 'tutor' ) ) {
		return false;
	}

	$r = bp_parse_args(
		$args,
		array(
			'fields'         => 'all',
			'post_type'      => tutor()->course_post_type,
			'post_status'    => array( 'publish', 'private' ),
			'orderby'        => 'ID',
			'order'          => 'DESC',
			'paged'          => 1,
			'posts_per_page' => tutor_utils()->get_option( 'courses_per_page' ),
			's'              => '',
		)
	);

	if ( $r['s'] ) {
		add_filter( 'posts_search', '_tutor_search_by_title_only', 500, 2 );
	}

	/**
	 * Apply filters for course arguments.
	 *
	 * @since 2.4.40
	 *
	 * @param array $r Array of args.
	 */
	$r = apply_filters( 'bb_tutorlms_get_courses_args', $r );

	$query = new WP_Query( $r );

	if ( $r['s'] ) {
		remove_filter( 'posts_search', '_tutor_search_by_title_only', 500 );
	}

	$results = ! empty( $query ) ? $query : array();

	/**
	 * Apply filters for course results.
	 *
	 * @since 2.4.40
	 *
	 * @param object $results WP_Query object.
	 * @param array  $r       Array of args.
	 */
	return apply_filters( 'bb_get_tutorlms_courses', $results, $r );
}

/**
 * Get profile courses slug.
 *
 * @since 2.4.40
 *
 * @return string
 */
function bb_tutorlms_profile_courses_slug() {
	if ( function_exists( 'tutor' ) ) {
		$course_permalink_base = tutor_utils()->get_option( 'course_permalink_base', tutor()->course_post_type );
		if ( ! empty( $course_permalink_base ) ) {
			return apply_filters( 'bb_tutorlms_profile_courses_slug', $course_permalink_base );
		}
	}

	/**
	 * Apply filters for profile courses slug.
	 *
	 * @since 2.4.40
	 *
	 * @param string $slug Defaults to 'courses'.
	 */
	return apply_filters( 'bb_tutorlms_profile_courses_slug', 'courses' );
}

/**
 * Get profile enrolled courses slug.
 *
 * @since 2.4.40
 *
 * @return string
 */
function bb_tutorlms_profile_enrolled_courses_slug() {

	/**
	 * Apply filters for get profile enrolled courses slug.
	 *
	 * @since 2.4.40
	 *
	 * @param string $slug Defaults to 'enrolled-courses'.
	 */
	return apply_filters( 'bb_tutorlms_profile_enrolled_courses_slug', 'enrolled-courses' );
}

/**
 * Get profile instructor courses slug.
 *
 * @since 2.4.40
 *
 * @return string
 */
function bb_tutorlms_profile_instructor_courses_slug() {

	/**
	 * Apply filters for get profile instructor courses slug.
	 *
	 * @since 2.4.40
	 *
	 * @param string $slug Defaults to 'instructor-courses'.
	 */
	return apply_filters( 'bb_tutorlms_profile_instructor_courses_slug', 'instructor-courses' );
}

/**
 * Get Tutor LMS enrolled courses.
 *
 * @since 2.4.40
 *
 * @param int $user_id User Id.
 *
 * @return array|object Enrolled courses WP Query.
 */
function bb_tutorlms_get_enrolled_courses( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	$enrolled_courses = array();
	if ( function_exists( 'tutor_utils' ) ) {
		$enrolled_courses = tutor_utils()->get_enrolled_courses_by_user( $user_id );
	}

	/**
	 * Apply filters for get profile instructor courses slug.
	 *
	 * @since 2.4.40
	 *
	 * @param bool|\WP_Query $enrolled_courses Get the enrolled courses by user.
	 * @param int            $user_id          User id.
	 */
	return apply_filters( 'bb_tutorlms_get_enrolled_courses', $enrolled_courses, $user_id );
}

/**
 * Get Tutor LMS instructor created courses.
 *
 * @since 2.4.40
 *
 * @param int $instructor_id Instructor User ID.
 *
 * @return array|object Instructor courses WP Query.
 */
function bb_tutorlms_get_instructor_courses( $instructor_id = 0 ) {
	if ( empty( $instructor_id ) ) {
		$instructor_id = bp_displayed_user_id();
	}

	$instructor_courses = array();
	if ( class_exists( 'Tutor\Models\CourseModel' ) ) {
		$instructor_courses = Tutor\Models\CourseModel::get_courses_by_instructor( $instructor_id, Tutor\Models\CourseModel::STATUS_PUBLISH );
	}

	/**
	 * Apply filters for get instructors courses.
	 *
	 * @since 2.4.40
	 *
	 * @param array|null|object $instructor_courses Get courses by a instructor.
	 * @param int               $instructor_id      Instructor id.
	 */
	return apply_filters( 'bb_tutorlms_get_instructor_courses', $instructor_courses, $instructor_id );
}

/**
 * Function to load the instance of the class BB_TutorLMS_Group_Table.
 *
 * @since 2.4.40
 *
 * @return null|BB_TutorLMS_Groups|void
 */
function bb_load_tutorlms_group() {
	if ( class_exists( 'BB_TutorLMS_Groups' ) ) {
		return BB_TutorLMS_Groups::instance();
	}
}

/**
 * Function to add notices when courses empty for TutorLMS.
 *
 * @since 2.4.40
 *
 * @param array $messages Array of feedback messages.
 *
 * @return array
 */
function bb_tutorlms_nouveau_feedback_messages( $messages ) {
	$user_same = bp_displayed_user_id() === bp_loggedin_user_id();
	$messages['tutorlms-courses-loop-none'] = array(
		'type'    => 'info',
		'message' => $user_same ? __( 'You have not enrolled in any courses yet!', 'buddyboss-pro' ) : __( 'This member has not enrolled in any courses yet!', 'buddyboss-pro' ),
	);

	$messages['tutorlms-created-courses-loop-none'] = array(
		'type'    => 'info',
		'message' => $user_same ? __( 'You have not created any courses yet!', 'buddyboss-pro' ) : __( 'This member has not created any courses yet!', 'buddyboss-pro' ),
	);

	return $messages;
}
add_filter( 'bp_nouveau_feedback_messages', 'bb_tutorlms_nouveau_feedback_messages' );
