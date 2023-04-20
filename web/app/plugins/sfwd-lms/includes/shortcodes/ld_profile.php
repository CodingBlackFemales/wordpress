<?php
/**
 * LearnDash `[ld_profile]` shortcode processing.
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[ld_profile]` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 2.1.0
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type int       $user_id            User ID. Defaults to current user ID.
 *    @type false|int $per_page           Number of profiles per page. Default false.
 *    @type string    $order              Designates ascending ('ASC') or descending ('DESC') order. Default 'DESC'.
 *    @type string    $orderby            The name of the field to order posts by. Default 'ID'.
 *    @type int       $course_points_user Whether to show user course points. Default 'yes'.
 *    @type boolean   $expand_all         Whether to expand all. Default False.
 *    @type string    $profile_link       User profile link. Default 'yes'.
 *    @type string    $show_header        Whether to show header. Default 'yes'.
 *    @type string    $show_quizzes       Whether to show quizzes. Default 'yes'.
 *    @type string    $show_search        Whether to allow search. Default 'yes'.
 *    @type string    $search             Search query string. Default empty.
 *    @type false|int $quiz_num           Number of quiz attempts to show per course listing
 * }
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'ld_profile'.
 *
 * @return string The `ld_profile` shortcode output.
 */
function learndash_profile( $atts = array(), $content = '', $shortcode_slug = 'ld_profile' ) {
	global $learndash_shortcode_used;

	// Add check to ensure user it logged in.
	if ( ! is_user_logged_in() ) {
		return '';
	}

	$defaults = array(
		'user_id'            => get_current_user_id(),
		'per_page'           => false,
		'order'              => 'DESC',
		'orderby'            => 'ID',
		'course_points_user' => 'yes',
		'expand_all'         => false,
		'profile_link'       => 'yes',
		'show_header'        => 'yes',
		'show_quizzes'       => 'yes',
		'show_search'        => 'yes',
		'search'             => '',
		'quiz_num'           => false,
	);
	$atts     = wp_parse_args( $atts, $defaults );

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$atts = apply_filters( 'learndash_shortcode_atts', $atts, $shortcode_slug );

	/**
	 * LEARNDASH-6274: Patch to ensure the user_id is valid.
	 */
	if ( ( (int) get_current_user_id() !== (int) $atts['user_id'] ) && ( ! learndash_is_admin_user( get_current_user_id() ) ) ) {
		if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
			// If group leader user we ensure the preview user_id is within their group(s).
			if ( ! learndash_is_group_leader_of_user( get_current_user_id(), $atts['user_id'] ) ) {
				$atts['user_id'] = get_current_user_id();
			}
		} else {
			// If neither admin or group leader then we don't see the user_id for the shortcode.
			$atts['user_id'] = get_current_user_id();
		}
	}

	$enabled_values = array( 'yes', 'true', 'on', '1' );
	if ( in_array( strtolower( $atts['expand_all'] ), $enabled_values, true ) ) {
		$atts['expand_all'] = true;
	} else {
		$atts['expand_all'] = false;
	}

	if ( in_array( strtolower( $atts['show_header'] ), $enabled_values, true ) ) {
		$atts['show_header'] = 'yes';
	} else {
		$atts['show_header'] = false;
	}

	if ( in_array( strtolower( $atts['show_search'] ), $enabled_values, true ) ) {
		$atts['show_search'] = 'yes';
	} else {
		$atts['show_search'] = false;
	}

	if ( in_array( strtolower( $atts['course_points_user'] ), $enabled_values, true ) ) {
		$atts['course_points_user'] = 'yes';
	} else {
		$atts['course_points_user'] = false;
	}

	if ( false === $atts['per_page'] ) {
		$atts['per_page'] = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' );
	} else {
		$atts['per_page'] = intval( $atts['per_page'] );
	}

	if ( false === $atts['quiz_num'] ) {
		$atts['quiz_num'] = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' );
	} else {
		$atts['quiz_num'] = intval( $atts['quiz_num'] );
	}

	if ( $atts['per_page'] > 0 ) {
		$atts['paged'] = 1;
	} else {
		unset( $atts['paged'] );
		$atts['nopaging'] = true;
	}

	if ( in_array( strtolower( $atts['profile_link'] ), $enabled_values, true ) ) {
		$atts['profile_link'] = true;
	} else {
		$atts['profile_link'] = false;
	}

	if ( in_array( strtolower( $atts['show_quizzes'] ), $enabled_values, true ) ) {
		$atts['show_quizzes'] = true;
	} else {
		$atts['show_quizzes'] = false;
	}

	if ( 'yes' === $atts['show_search'] ) {
		if ( ( isset( $_GET['ld-profile-search'] ) ) && ( ! empty( $_GET['ld-profile-search'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$atts['search'] = esc_attr( $_GET['ld-profile-search'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
	} else {
		$atts['search'] = '';
	}

	/**
	 * Filters profile shortcode attributes.
	 *
	 * @param array $attributes An array of shortcode attributes.
	 */
	$atts = apply_filters( 'learndash_profile_shortcode_atts', $atts );

	if ( isset( $atts['search'] ) ) {
		$atts['s'] = $atts['search'];
		unset( $atts['search'] );
	}

	if ( empty( $atts['user_id'] ) ) {
		return;
	}

	$current_user = get_user_by( 'id', $atts['user_id'] );
	$user_courses = ld_get_mycourses( $atts['user_id'], $atts );

	$quiz_attempts = learndash_get_user_profile_quiz_attempts( $current_user->ID );

	$profile_pager = array();

	if ( ( isset( $atts['per_page'] ) ) && ( intval( $atts['per_page'] ) > 0 ) ) {
		$atts['per_page'] = intval( $atts['per_page'] );

		if ( ( ( isset( $_GET['ld-profile-page'] ) ) && ( ! empty( $_GET['ld-profile-page'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$profile_pager['paged']         = intval( $_GET['ld-profile-page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$quiz_attempts['quizzes-paged'] = ( isset( $_GET['profile-quizzes'] ) ? intval( $_GET['profile-quizzes'] ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( ( ( isset( $_GET['profile-quizzes'] ) ) && ( ! empty( $_GET['profile-quizzes'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$quiz_attempts['quizzes-paged'] = intval( $_GET['profile-quizzes'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ( ( isset( $_GET['ld-profile-page'] ) ) && ( ! empty( $_GET['ld-profile-page'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$profile_pager['paged'] = intval( $_GET['ld-profile-page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} else {
				$profile_pager['paged'] = 1;
			}
		} else {
			$profile_pager['paged']         = 1;
			$quiz_attempts['quizzes-paged'] = 1;
		}

		$profile_pager['total_items'] = count( $user_courses );
		$profile_pager['total_pages'] = ceil( count( $user_courses ) / $atts['per_page'] );

		$user_courses = array_slice( $user_courses, ( $profile_pager['paged'] * $atts['per_page'] ) - $atts['per_page'], $atts['per_page'], false );
	}

	$learndash_shortcode_used = true;

	return SFWD_LMS::get_template(
		'profile',
		array(
			'user_id'        => $atts['user_id'],
			'quiz_attempts'  => $quiz_attempts,
			'current_user'   => $current_user,
			'user_courses'   => $user_courses,
			'shortcode_atts' => $atts,
			'profile_pager'  => $profile_pager,
		)
	);
}
add_shortcode( 'ld_profile', 'learndash_profile', 10, 3 );
