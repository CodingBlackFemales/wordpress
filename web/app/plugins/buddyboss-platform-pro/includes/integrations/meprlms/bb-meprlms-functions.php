<?php
/**
 * MemberpressLMS integration helpers.
 *
 * @since 2.6.30
 *
 * @package BuddyBossPro\Integration\MemberpressLMS
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use memberpress\courses\models as models;
use memberpress\courses\helpers as helpers;

/**
 * Returns MemberpressLMS Integration url.
 *
 * @since 2.6.30
 *
 * @param string $path Path to meprlms integration.
 *
 * @return string
 */
function bb_meprlms_integration_url( $path = '' ) {
	return trailingslashit( bb_platform_pro()->integration_url ) . 'meprlms/' . trim( $path, '/\\' );
}

/**
 * Returns MemberpressLMS Integration path.
 *
 * @since 2.6.30
 *
 * @param string $path Path to meprlms integration.
 *
 * @return string
 */
function bb_meprlms_integration_path( $path = '' ) {
	return trailingslashit( bb_platform_pro()->integration_dir ) . 'meprlms/' . trim( $path, '/\\' );
}

/**
 * Get MemberpressLMS settings.
 *
 * @since 2.6.30
 *
 * @param string $keys    Optional. Get setting by key.
 * @param string $default Optional. Default value if value or setting not available.
 *
 * @return array|string
 */
function bb_get_meprlms_settings( $keys = '', $default = '' ) {
	$settings = bp_get_option( 'bb-meprlms', array() );

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
	 * Filters MemberpressLMS get settings.
	 *
	 * @since 2.6.30
	 *
	 * @param array  $settings Settings of meprlms.
	 * @param string $keys     Optional. Get setting by key.
	 * @param string $default  Optional. Default value if value or setting not available.
	 */
	return apply_filters( 'bb_get_meprlms_settings', $settings, $keys, $default );
}

/**
 * Checks if MemberpressLMS enable.
 *
 * @since 2.6.30
 *
 * @param integer $default MemberpressLMS enabled by default.
 *
 * @return bool Is MemberpressLMS enabled or not.
 */
function bb_meprlms_enable( $default = 0 ) {

	/**
	 * Filters MemberpressLMS enabled settings.
	 *
	 * @since 2.6.30
	 *
	 * @param integer $default MemberpressLMS enabled by default.
	 */
	return (bool) apply_filters( 'bb_meprlms_enable', bb_get_meprlms_settings( 'bb-meprlms-enable', $default ) );
}

/**
 * Function to return all MemberpressLMS post types.
 *
 * @since 2.6.30
 *
 * @return array
 */
function bb_meprlms_get_post_types() {
	if ( ! class_exists( 'memberpress\courses\helpers\Courses' ) ) {
		return array();
	}

	$meprlms_post_types = array(
		'mpcs-course',
		'mpcs-lesson',
	);

	if ( class_exists( 'memberpress\assignments\models\Assignment' ) ) {
		$meprlms_post_types[] = 'mpcs-assignment';
	}

	if ( class_exists( 'memberpress\quizzes\models\Quiz' ) ) {
		$meprlms_post_types[] = 'mpcs-quiz';
	}

	/**
	 * Filters for MemberpressLMS post types.
	 *
	 * @since 2.6.30
	 *
	 * @param array $meprlms_post_types MemberpressLMS post types.
	 */
	return apply_filters( 'bb_meprlms_get_post_types', $meprlms_post_types );
}

/**
 * Function to get published MemberpressLMS courses.
 *
 * @since 2.6.30
 *
 * @param array $args Array of args.
 *
 * @return false|mixed|null|object
 */
function bb_meprlms_get_courses( $args = array() ) {
	if ( ! class_exists( 'memberpress\courses\helpers\Courses' ) ) {
		return false;
	}

	$r = bp_parse_args(
		$args,
		array(
			'fields'         => 'all',
			'post_type'      => 'mpcs-course',
			'post_status'    => array( 'publish', 'private' ),
			'orderby'        => 'ID',
			'order'          => 'DESC',
			'paged'          => 1,
			'posts_per_page' => 10,
			's'              => '',
		)
	);

	if ( $r['s'] ) {
		add_filter( 'posts_search', 'bb_meprlms_search_by_title_only', 500, 2 );
	}

	/**
	 * Apply filters for course arguments.
	 *
	 * @since 2.6.30
	 *
	 * @param array $r Array of args.
	 */
	$r = apply_filters( 'bb_meprlms_get_courses_args', $r );

	$results = new WP_Query( $r );

	if ( $r['s'] ) {
		remove_filter( 'posts_search', 'bb_meprlms_search_by_title_only', 500 );
	}

	/**
	 * Apply filters for course results.
	 *
	 * @since 2.6.30
	 *
	 * @param object $results WP_Query object.
	 * @param array  $r       Array of args.
	 */
	return apply_filters( 'bb_get_meprlms_courses', $results, $r );
}

/**
 * Get profile courses slug.
 *
 * @since 2.6.30
 *
 * @return string
 */
function bb_meprlms_profile_courses_slug() {

	/**
	 * Apply filters for profile courses slug.
	 *
	 * @since 2.6.30
	 *
	 * @param string $slug Defaults to 'courses'.
	 */
	return apply_filters( 'bb_meprlms_profile_courses_slug', 'courses' );
}

/**
 * Get profile user courses slug.
 *
 * @since 2.6.30
 *
 * @return string
 */
function bb_meprlms_profile_user_courses_slug() {

	/**
	 * Apply filters for get profile user courses slug.
	 *
	 * @since 2.6.30
	 *
	 * @param string $slug Defaults to 'user-courses'.
	 */
	return apply_filters( 'bb_meprlms_profile_user_courses_slug', 'user-courses' );
}

/**
 * Get profile instructor courses slug.
 *
 * @since 2.6.30
 *
 * @return string
 */
function bb_meprlms_profile_instructor_courses_slug() {

	/**
	 * Apply filters for get profile instructor courses slug.
	 *
	 * @since 2.6.30
	 *
	 * @param string $slug Defaults to 'instructor-courses'.
	 */
	return apply_filters( 'bb_meprlms_profile_instructor_courses_slug', 'instructor-courses' );
}

/**
 * Get Memberpress LMS user accessible course ids.
 *
 * @since 2.6.30
 *
 * @param int $user_id user id.
 *
 * @return array User accessible courses ids..
 */
function bb_meprlms_get_user_course_ids( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	$course_ids = array();
	if (
		class_exists( 'memberpress\courses\models\Course' ) &&
		class_exists( 'memberpress\courses\helpers\Options' ) &&
		class_exists( 'MeprOptions' ) &&
		class_exists( 'MeprUser' ) &&
		class_exists( 'MeprRule' )
	) {
		$options      = get_option( 'mpcs-options' );
		$sort_options = array(
			'alphabetically' => array(
				'orderby' => 'title',
			),
			'last-updated'   => array(
				'orderby' => 'modified',
			),
			'publish-date'   => array(
				'orderby' => 'date',
			),
		);

		$mpcs_sort_order           = helpers\Options::val( $options, 'courses-sort-order', 'alphabetically' );
		$mpcs_sort_order_direction = helpers\Options::val( $options, 'courses-sort-order-direction', 'ASC' );

		if ( ! in_array( $mpcs_sort_order_direction, array( 'ASC', 'DESC' ), true ) ) {
			$mpcs_sort_order_direction = 'ASC';
		}

		$sort_option = $sort_options[ $mpcs_sort_order ] ?? $sort_options['default'];

		$post_args = array(
			'post_type'      => models\Course::$cpt,
			'post_status'    => 'publish',
			'posts_per_page' => '-1',
			'orderby'        => $sort_option['orderby'],
			'order'          => $mpcs_sort_order_direction,
		);

		$courses = get_posts( $post_args );
		if ( $user_id ) {
			$mepr_user = new MeprUser( $user_id );

			if ( ! user_can( $user_id, 'administrator' ) ) {
				$courses = array_filter(
					$courses,
					function ( $course ) use ( $mepr_user ) {
						return false === MeprRule::is_locked_for_user( $mepr_user, $course );
					}
				);
			}
		}

		$course_ids = array_map(
			function ( $c ) {
				return is_object( $c ) ? $c->ID : $c['ID'];
			},
			$courses
		);

		if ( empty( $course_ids ) ) {
			$course_ids = array( 0 );
		}
	}

	return $course_ids;
}

/**
 * Get Memberpress LMS courses accessible by user.
 *
 * @since 2.6.30
 *
 * @param int    $user_id        user id.
 * @param string $post_status    post status.
 * @param int    $paged          page number.
 * @param int    $posts_per_page post per page.
 * @param array  $filters        additional filters with key value for \WP_Query.
 *
 * @return array|object User accessible courses WP Query.
 */
function bb_meprlms_get_user_courses( $user_id = 0, $post_status = 'publish', $paged = 1, $posts_per_page = 0, $filters = array() ) {

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	$user_courses = array();
	$course_ids   = bb_meprlms_get_user_course_ids( $user_id );

	if (
		! empty( $course_ids ) &&
		class_exists( 'memberpress\courses\models\Course' ) &&
		class_exists( 'memberpress\courses\helpers\Options' )
	) {

		if ( empty( $posts_per_page ) ) {
			$options        = get_option( 'mpcs-options' );
			$posts_per_page = (int) helpers\Options::val( $options, 'courses-per-page', 10 );
		}

		$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : $paged;

		if ( empty( $filters ) && get_query_var( 's' ) ) {
			$filters['s'] = get_query_var( 's' );
		}

		$course_args = array(
			'post_type'      => models\Course::$cpt,
			'post_status'    => $post_status,
			'posts_per_page' => $posts_per_page,
			'paged'          => $paged,
			'orderby'        => 'post__in',
			'order'          => 'ASC',
			'post__in'       => $course_ids,
		);

		if ( ! empty( $filters ) ) {
			$keys = array_keys( $course_args );
			foreach ( $filters as $key => $value ) {
				if ( ! in_array( $key, $keys, true ) ) {
					$course_args[ $key ] = $value;
				}
			}
		}

		$user_courses = new WP_Query( $course_args );
	}

	/**
	 * Apply filters for get profile instructor courses slug.
	 *
	 * @since 2.6.30
	 *
	 * @param bool|WP_Query $user_courses   Get the accessible courses by user.
	 * @param int            $user_id        user id.
	 * @param string         $post_status    post status.
	 * @param int            $paged          page no.
	 * @param int            $posts_per_page post per page.
	 * @param array          $filters        additional filters with key value for \WP_Query.
	 */
	return apply_filters( 'bb_meprlms_get_user_courses', $user_courses, $user_id, $post_status, $paged, $posts_per_page, $filters );
}

/**
 * Get Memberpress LMS instructor created courses.
 *
 * @since 2.6.30
 *
 * @param int    $instructor_id  Instructor User ID.
 * @param string $post_status    post status.
 * @param int    $paged          page number.
 * @param int    $posts_per_page post per page.
 * @param array  $filters        additional filters with key value for \WP_Query.
 *
 * @return array|object Instructor courses WP Query.
 */
function bb_meprlms_get_instructor_courses( $instructor_id = 0, $post_status = 'publish', $paged = 1, $posts_per_page = 0, $filters = array() ) {
	if ( empty( $instructor_id ) ) {
		$instructor_id = bp_displayed_user_id();
	}

	$instructor_courses = array();

	if ( class_exists( 'memberpress\courses\models\Course' ) && class_exists( 'memberpress\courses\helpers\Options' ) && user_can( $instructor_id, 'administrator' ) ) {
		$options      = get_option( 'mpcs-options' );
		$sort_options = array(
			'alphabetically' => array(
				'orderby' => 'title',
			),
			'last-updated'   => array(
				'orderby' => 'modified',
			),
			'publish-date'   => array(
				'orderby' => 'date',
			),
		);

		$mpcs_sort_order           = helpers\Options::val( $options, 'courses-sort-order', 'alphabetically' );
		$mpcs_sort_order_direction = helpers\Options::val( $options, 'courses-sort-order-direction', 'ASC' );

		if ( ! in_array( $mpcs_sort_order_direction, array( 'ASC', 'DESC' ), true ) ) {
			$mpcs_sort_order_direction = 'ASC';
		}

		$sort_option = $sort_options[ $mpcs_sort_order ] ?? $sort_options['default'];

		if ( empty( $posts_per_page ) ) {
			$posts_per_page = (int) helpers\Options::val( $options, 'courses-per-page', 10 );
		}

		$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : $paged;

		if ( empty( $filters ) && get_query_var( 's' ) ) {
			$filters['s'] = get_query_var( 's' );
		}

		$course_args = array(
			'post_type'      => models\Course::$cpt,
			'post_status'    => $post_status,
			'author'         => $instructor_id,
			'paged'          => $paged,
			'posts_per_page' => $posts_per_page,
			'orderby'        => $sort_option['orderby'],
			'order'          => $mpcs_sort_order_direction,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'key'   => '_mpcs_course_status',
				'value' => 'enabled',
			),
		);

		if ( ! empty( $filters ) ) {
			$keys = array_keys( $course_args );
			foreach ( $filters as $key => $value ) {
				if ( ! in_array( $key, $keys, true ) ) {
					$course_args[ $key ] = $value;
				}
			}
		}

		$instructor_courses = new WP_Query( $course_args );
	}

	/**
	 * Apply filters for get instructors courses.
	 *
	 * @since 2.6.30
	 *
	 * @param array|null|object $instructor_courses Get courses by a instructor.
	 * @param int               $instructor_id      Instructor id.
	 * @param string            $post_status        post status.
	 * @param int               $paged              page no.
	 * @param int               $posts_per_page     post per page.
	 * @param array             $filters            additional filters with key value for \WP_Query.
	 */
	return apply_filters( 'bb_meprlms_get_instructor_courses', $instructor_courses, $instructor_id, $post_status, $paged, $posts_per_page, $filters );
}

/**
 * Function to load the instance of the class BB_MeprLMS_Group_Table.
 *
 * @since 2.6.30
 *
 * @return null|BB_MeprLMS_Groups|void
 */
function bb_load_meprlms_group() {
	if ( class_exists( 'BB_MeprLMS_Groups' ) ) {
		return BB_MeprLMS_Groups::instance();
	}
}

/**
 * Function to add notices when courses empty for Memberpress LMS.
 *
 * @since 2.6.30
 *
 * @param array $messages Array of feedback messages.
 *
 * @return array
 */
function bb_meprlms_nouveau_feedback_messages( $messages ) {
	$user_same = bp_displayed_user_id() === bp_loggedin_user_id();

	$messages['meprlms-courses-loop-none'] = array(
		'type'    => 'info',
		'message' => __( 'No courses found!', 'buddyboss-pro' ),
	);

	$messages['meprlms-accessible-courses-loop-none'] = array(
		'type'    => 'info',
		'message' => $user_same ? __( 'You have no access to any courses yet!', 'buddyboss-pro' ) : __( 'This member has not access to any courses yet!', 'buddyboss-pro' ),
	);

	$messages['meprlms-created-courses-loop-none'] = array(
		'type'    => 'info',
		'message' => $user_same ? __( 'You have not created any courses yet!', 'buddyboss-pro' ) : __( 'This member has not created any courses yet!', 'buddyboss-pro' ),
	);

	return $messages;
}

/**
 * Search SQL filter for matching against post title only.
 *
 * @since 2.6.30
 *
 * @param string   $search   Search string.
 * @param WP_Query $wp_query WP Query object.
 *
 * @return string
 */
function bb_meprlms_search_by_title_only( $search, $wp_query ) {
	if ( ! empty( $search ) && ! empty( $wp_query->query_vars['search_terms'] ) ) {
		global $wpdb;

		$q = $wp_query->query_vars;
		$n = ! empty( $q['exact'] ) ? '' : '%';

		$search = array();

		foreach ( (array) $q['search_terms'] as $term ) {
			$search[] = $wpdb->prepare( "$wpdb->posts.post_title LIKE %s", $n . $wpdb->esc_like( $term ) . $n );
		}

		if ( ! is_user_logged_in() ) {
			$search[] = "$wpdb->posts.post_password = ''";
		}

		$search = ' AND ' . implode( ' AND ', $search );
	}

	return $search;
}

/**
 * Utility function to add template paths based on context.
 *
 * @since 2.6.30
 *
 * @param array  $paths          Existing template paths.
 * @param string $relative_path  Relative path for template integration.
 *
 * @return array Modified template paths.
 */
function bb_meprlms_add_template_paths( $paths, $relative_path ) {
	$stylesheet_path = get_stylesheet_directory();
	$template_path   = get_template_directory();
	$is_child_theme  = is_child_theme();
	$template_paths  = array();

	if ( $is_child_theme ) {
		$template_paths[] = $template_path . '/buddyboss/' . $relative_path;
	}

	$template_paths[] = $stylesheet_path . '/buddyboss/' . $relative_path;
	$template_paths[] = bb_meprlms_integration_path( '/templates/' . $relative_path );

	return array_merge( $template_paths, $paths );
}

/**
 * Get the correct template path if it exists.
 *
 * @since 2.6.30
 *
 * @param string $template_name The name of the template file.
 * @param string $relative_path Relative path name.
 *
 * @return string|false The full path to the template if it exists, or false if not.
 */
function bb_meprlms_get_template_path( $template_name, $relative_path = 'courses' ) {
	$stylesheet_path = get_stylesheet_directory();
	$template_path   = get_template_directory();
	$is_child_theme  = is_child_theme();
	$template_paths  = array();

	if ( $is_child_theme ) {
		$template_paths[] = $template_path . '/memberpress/' . $relative_path . '/' . $template_name;
	}

	$template_paths[] = $stylesheet_path . '/memberpress/' . $relative_path . '/' . $template_name;
	$template_paths[] = bb_meprlms_integration_path( '/templates/memberpress/' . $relative_path . '/' . $template_name );

	// Return the first valid template path found.
	foreach ( $template_paths as $path ) {
		if ( $path && file_exists( $path ) ) {
			return $path;
		}
	}

	return false;
}

/**
 * Get the course search form.
 *
 * @since 2.6.30
 *
 * @return string Search form html.
 */
function bb_meprlms_get_course_search_form() {
	global $wp;
	$action_url   = home_url( $wp->request );
	$action_url   = preg_replace( '#/page/\d+/?#', '/', $action_url );
	$placeholder  = esc_html__( 'Search..', 'buddyboss-pro' );
	$reader_text  = esc_html__( 'Search For.', 'buddyboss-pro' );
	$search_value = ( get_query_var( 's' ) ) ? get_query_var( 's' ) : '';

	return '<form method="get" id="bb_meprlms_courses_search_form" action="' . $action_url . '">
			<label>
				<span class="screen-reader-text">' . $reader_text . '</span>
				<input type="search" class="search-field-top" placeholder="' . $placeholder . '" value="' . $search_value . '" name="s">
			</label>
	</form>';
}

/**
 * Checks if MemberpressLMS course visibility enable.
 *
 * @since 2.6.30
 *
 * @param integer $default MemberpressLMS course visibility enabled by default.
 *
 * @return bool Is MemberpressLMS course visibility enabled or not.
 */
function bb_meprlms_course_visibility( $default = 1 ) {

	/**
	 * Filters MemberpressLMS course visibility enabled settings.
	 *
	 * @since 2.6.30
	 *
	 * @param integer $default MemberpressLMS course visibility enabled by default.
	 */
	return (bool) apply_filters( 'bb_meprlms_course_visibility', bb_get_meprlms_settings( 'bb-meprlms-course-visibility', $default ) );
}

/**
 * MemberpressLMS course activities.
 *
 * @since 2.6.30
 *
 * @param array $keys Optionals.
 *
 * @return array
 */
function bb_meprlms_course_activities( $keys = array() ) {
	$activities = array(
		'bb_meprlms_user_started_course'   => esc_html__( 'Group member started a course', 'buddyboss-pro' ),
		'bb_meprlms_user_completed_course' => esc_html__( 'Group member completes a course', 'buddyboss-pro' ),
		'bb_meprlms_user_completed_lesson' => esc_html__( 'Group member completes a lesson', 'buddyboss-pro' ),
	);

	if ( class_exists( 'memberpress\assignments\models\Assignment' ) ) {
		$activities['bb_meprlms_user_completed_assignment'] = esc_html__( 'Group member completed an assignment', 'buddyboss-pro' );
	}

	if ( class_exists( 'memberpress\quizzes\models\Quiz' ) ) {
		$activities['bb_meprlms_user_completed_quiz'] = esc_html__( 'Group member completed quiz', 'buddyboss-pro' );
	}

	$result_activities = ! empty( $keys ) ? array_intersect_key( $activities, $keys ) : $activities;

	/**
	 * Filters to get enabled MemberpressLMS courses activities.
	 *
	 * @since 2.6.30
	 *
	 * @param array|string $result_activities MemberpressLMS course activities.
	 */
	return apply_filters( 'bb_meprlms_course_activities', $result_activities );
}

/**
 * Function to get enabled MemberpressLMS courses activities.
 *
 * @since 2.6.30
 *
 * @param string $key MemberpressLMS course activity slug.
 *
 * @return array Is any MemberpressLMS courses activities enabled?
 */
function bb_get_enabled_meprlms_course_activities( $key = '' ) {

	$option_name = ! empty( $key ) ? 'bb-meprlms-course-activity.' . $key : 'bb-meprlms-course-activity';

	/**
	 * Filters to get enabled MemberpressLMS courses activities.
	 *
	 * @since 2.6.30
	 *
	 * @param array|string MemberpressLMS settings.
	 */
	return apply_filters( 'bb_get_enabled_meprlms_course_activities', bb_get_meprlms_settings( $option_name ) );
}

/**
 * Return inactive class.
 *
 * @since 2.6.30
 *
 * @return string class string.
 */
function bb_meprlms_get_inactive_class() {
	return bp_is_active( 'groups' ) ? '' : 'bb-inactive-field';
}

/**
 * Normalizes file type to a standardized format.
 *
 * @since 2.7.20
 *
 * @param string $file_type The mime type to normalize.
 *
 * @return string Normalized file type or empty string if invalid.
 */
function bb_mpcs_get_normalized_file_type( $file_type ) {

	if ( ! $file_type ) {
		return '';
	}

	$parts   = explode( '/', $file_type );
	$subtype = isset( $parts[1] ) ? strtolower( $parts[1] ) : '';

	// Normalize known subtypes.
	$normalized = array(
		// Images.
		'jpeg'                                                                  => 'jpeg',
		'pjpeg'                                                                 => 'jpeg',
		'jpg'                                                                   => 'jpeg',
		'x-png'                                                                 => 'png',
		'png'                                                                   => 'png',
		'gif'                                                                   => 'gif',
		'bmp'                                                                   => 'bmp',
		'ms-bmp'                                                                => 'bmp',
		'x-windows-bmp'                                                         => 'bmp',
		'webp'                                                                  => 'webp',
		'svg+xml'                                                               => 'svg',
		'tiff'                                                                  => 'tiff',
		'x-icon'                                                                => 'ico',
		'ico'                                                                   => 'ico',
		'icon'                                                                  => 'ico',
		'vnd.adobe.photoshop'                                                   => 'psd',
		'photoshop'                                                             => 'psd',
		'psd'                                                                   => 'psd',
		'x-portable-pixmap'                                                     => 'ppm',
		'portable-pixmap'                                                       => 'ppm',
		'x-portable-bitmap'                                                     => 'pbm',
		'portable-bitmap'                                                       => 'pbm',
		'x-portable-anymap'                                                     => 'pnm',
		'portable-anymap'                                                       => 'pnm',
		'x-portable-graymap'                                                    => 'pgm',
		'portable-graymap'                                                      => 'pgm',
		'x-pcx'                                                                 => 'pcx',
		'pc-paintbrush'                                                         => 'pcx',
		'pcx'                                                                   => 'pcx',
		'x-xbitmap'                                                             => 'xbm',
		'xbitmap'                                                               => 'xbm',
		'x-xpixmap'                                                             => 'xpm',
		'xpixmap'                                                               => 'xpm',
		'x-rgb'                                                                 => 'rgb',
		'heif'                                                                  => 'heif',
		'heic'                                                                  => 'heic',
		'x-windows-bmp'                                                         => 'bmp',
		'cgm'                                                                   => 'cgm',
		'x-cmx'                                                                 => 'cmx',
		'x-cmu-raster'                                                          => 'ras',
		'vnd.djvu'                                                              => 'djvu',
		'djvu'                                                                  => 'djvu',
		'vnd.dxf'                                                               => 'dxf',
		'vnd.dwg'                                                               => 'dwg',
		'x-xcf'                                                                 => 'xcf',
		'jp2'                                                                   => 'jp2',
		'x-tga'                                                                 => 'tga',
		'targa'                                                                 => 'tga',
		'prs.btif'                                                              => 'btif',
		'vnd.fastbidsheet'                                                      => 'fbs',
		'vnd.fpx'                                                               => 'fpx',
		'vnd.net-fpx'                                                           => 'npx',
		'vnd.xiff'                                                              => 'xif',
		'vnd.fst'                                                               => 'fst',
		'ktx'                                                                   => 'ktx',
		'vnd.dvb.subtitle'                                                      => 'sub',
		'ief'                                                                   => 'ief',
		'vnd.dece.graphic'                                                      => 'uvi',

		// Video.
		'mp4'                                                                   => 'mp4',
		'quicktime'                                                             => 'mov',
		'x-matroska'                                                            => 'mkv',
		'x-msvideo'                                                             => 'avi',
		'avi'                                                                   => 'avi',
		'msvideo'                                                               => 'avi',
		'x-ms-wmv'                                                              => 'wmv',
		'ms-wmv'                                                                => 'wmv',
		'ogg'                                                                   => 'ogg',
		'x-flv'                                                                 => 'flv',
		'flv'                                                                   => 'flv',
		'flash-video'                                                           => 'flv',
		'3gpp'                                                                  => '3gp',
		'3gp'                                                                   => '3gp',
		'3gpp-encrypted'                                                        => '3gp',
		'3gpp2'                                                                 => '3g2',
		'mp2t'                                                                  => 'ts',
		'x-ms-asf'                                                              => 'asf',
		'ms-asf'                                                                => 'asf',
		'mpeg-system'                                                           => 'mpeg',
		'mpeg2'                                                                 => 'mpeg',
		'x-m4v'                                                                 => 'm4v',
		'm4v'                                                                   => 'm4v',
		'mp4v-es'                                                               => 'mp4',
		'h264'                                                                  => 'h264',
		'h263'                                                                  => 'h263',
		'h261'                                                                  => 'h261',
		'x-f4v'                                                                 => 'f4v',
		'f4v'                                                                   => 'f4v',
		'x-fli'                                                                 => 'fli',
		'fli'                                                                   => 'fli',
		'flic'                                                                  => 'fli',
		'x-sgi-movie'                                                           => 'movie',
		'vnd.dvb.file'                                                          => 'dvb',
		'x-ms-wm'                                                               => 'wm',
		'x-ms-wvx'                                                              => 'wvx',
		'x-ms-wmx'                                                              => 'wmx',
		'x-shockwave-flash'                                                     => 'swf',
		'adobe.flash.movie'                                                     => 'swf',
		'futuresplash'                                                          => 'swf',
		'shockwave-flash'                                                       => 'swf',
		'webm'                                                                  => 'webm',
		'vnd.fvt'                                                               => 'fvt',
		'vnd.ms-playready.media.pyv'                                            => 'pyv',
		'vnd.uvvu.mp4'                                                          => 'uvu',
		'vnd.dece.hd'                                                           => 'uvh',
		'vnd.dece.mobile'                                                       => 'uvm',
		'vnd.dece.pd'                                                           => 'uvp',
		'vnd.dece.sd'                                                           => 'uvs',
		'vnd.dece.video'                                                        => 'uvv',
		'vnd.vivo'                                                              => 'viv',

		// Audio.
		'x-aac'                                                                 => 'aac',
		'flac'                                                                  => 'flac',
		'x-flac'                                                                => 'flac',
		'midi'                                                                  => 'midi',
		'x-midi'                                                                => 'midi',
		'x-wav'                                                                 => 'wav',
		'wave'                                                                  => 'wav',
		'wav'                                                                   => 'wav',
		'x-ms-wma'                                                              => 'wma',
		'ms-wma'                                                                => 'wma',
		'wma'                                                                   => 'wma',
		'x-aiff'                                                                => 'aiff',
		'aiff'                                                                  => 'aiff',
		'x-mpegurl'                                                             => 'm3u',
		'x-pn-realaudio'                                                        => 'ram',
		'pn-realaudio'                                                          => 'ram',
		'realaudio'                                                             => 'ram',
		'vnd.dts'                                                               => 'dts',
		'dts'                                                                   => 'dts',
		'vnd.dts.hd'                                                            => 'dtshd',
		'dts.hd'                                                                => 'dtshd',
		'dtshd'                                                                 => 'dtshd',
		'basic'                                                                 => 'au',
		'x-ms-wax'                                                              => 'wax',
		'ms-wax'                                                                => 'wax',
		'vnd.audiology'                                                         => 'aud',
		'speex'                                                                 => 'spx',
		'x-gsm'                                                                 => 'gsm',
		'x-vorbis'                                                              => 'ogg',
		'vorbis'                                                                => 'ogg',
		'mp3'                                                                   => 'mp3',
		'mpg'                                                                   => 'mp3',
		'm4a'                                                                   => 'm4a',
		'mpeg'                                                                  => 'mp3',
		'adpcm'                                                                 => 'adp',
		'vnd.digital-winds'                                                     => 'eol',
		'vnd.dra'                                                               => 'dra',
		'vnd.lucent.voice'                                                      => 'lvp',
		'vnd.ms-playready.media.pya'                                            => 'pya',
		'vnd.nuera.ecelp4800'                                                   => 'ecelp4800',
		'vnd.nuera.ecelp7470'                                                   => 'ecelp7470',
		'vnd.nuera.ecelp9600'                                                   => 'ecelp9600',
		'vnd.rip'                                                               => 'rip',
		'x-pn-realaudio-plugin'                                                 => 'rmp',

		// Documents.
		'pdf'                                                                   => 'pdf',
		'acrobat'                                                               => 'pdf',
		'nappdf'                                                                => 'pdf',
		'msword'                                                                => 'doc',
		'ms-word'                                                               => 'doc',
		'vnd.openxmlformats-officedocument.wordprocessingml.document'           => 'docx',
		'vnd.ms-excel'                                                          => 'xls',
		'ms-excel'                                                              => 'xls',
		'msexcel'                                                               => 'xls',
		'vnd.openxmlformats-officedocument.spreadsheetml.sheet'                 => 'xlsx',
		'vnd.ms-powerpoint'                                                     => 'ppt',
		'ms-powerpoint'                                                         => 'ppt',
		'mspowerpoint'                                                          => 'ppt',
		'vnd.openxmlformats-officedocument.presentationml.presentation'         => 'pptx',
		'plain'                                                                 => 'txt',
		'rtf'                                                                   => 'rtf',
		'csv'                                                                   => 'csv',
		'comma-separated-values'                                                => 'csv',
		'tab-separated-values'                                                  => 'tsv',
		'xml'                                                                   => 'xml',
		'vnd.mozilla.xul+xml'                                                   => 'xml',
		'richtext'                                                              => 'rtf',
		'x-rtf'                                                                 => 'rtf',
		'html'                                                                  => 'html',
		'xhtml+xml'                                                             => 'xhtml',
		'x-latex'                                                               => 'latex',
		'latex'                                                                 => 'latex',
		'x-tex'                                                                 => 'tex',
		'tex'                                                                   => 'tex',
		'oasis.opendocument.text'                                               => 'odt',
		'vnd.oasis.opendocument.text'                                           => 'odt',
		'oasis.opendocument.spreadsheet'                                        => 'ods',
		'vnd.oasis.opendocument.spreadsheet'                                    => 'ods',
		'oasis.opendocument.presentation'                                       => 'odp',
		'vnd.oasis.opendocument.presentation'                                   => 'odp',
		'epub+zip'                                                              => 'epub',
		'json'                                                                  => 'json',
		'x-yaml'                                                                => 'yaml',
		'yaml'                                                                  => 'yaml',
		'markdown'                                                              => 'md',
		'md'                                                                    => 'md',
		'vnd.ms-word.document.macroenabled.12'                                  => 'docm',
		'ms-word.document.macroenabled.12'                                      => 'docm',
		'vnd.ms-excel.sheet.macroenabled.12'                                    => 'xlsm',
		'ms-excel.sheet.macroenabled.12'                                        => 'xlsm',
		'vnd.ms-powerpoint.presentation.macroenabled.12'                        => 'pptm',
		'ms-powerpoint.presentation.macroenabled.12'                            => 'pptm',
		'vnd.oasis.opendocument.graphics'                                       => 'odg',
		'oasis.opendocument.graphics'                                           => 'odg',
		'vnd.ms-outlook'                                                        => 'msg',
		'rss+xml'                                                               => 'rss',
		'atom+xml'                                                              => 'atom',
		'vnd.openofficeorg.extension'                                           => 'oxt',
		'openofficeorg.extension'                                               => 'oxt',
		'vnd.lotus-1-2-3'                                                       => '123',
		'vnd.lotus-wordpro'                                                     => 'lwp',
		'vnd.ms-works'                                                          => 'wks',
		'vnd.visio2013'                                                         => 'vsdx',
		'ms-visio.drawing'                                                      => 'vsdx',
		'vnd.apple.keynote'                                                     => 'key',
		'vnd.apple.pages'                                                       => 'pages',
		'vnd.apple.numbers'                                                     => 'numbers',
		'vnd.sun.xml.writer'                                                    => 'sxw',
		'vnd.sun.xml.calc'                                                      => 'sxc',
		'vnd.sun.xml.impress'                                                   => 'sxi',
		'vnd.stardivision.writer'                                               => 'sdw',
		'vnd.stardivision.calc'                                                 => 'sdc',
		'vnd.stardivision.impress'                                              => 'sdd',
		'x-iwork-pages-sffpages'                                                => 'pages',
		'x-iwork-numbers-sffnumbers'                                            => 'numbers',
		'x-iwork-keynote-sffkey'                                                => 'key',
		'vnd.visio'                                                             => 'vsd',
		'visio'                                                                 => 'vsd',
		'vnd.wordperfect'                                                       => 'wpd',
		'vnd.ms-xpsdocument'                                                    => 'xps',
		'x-abiword'                                                             => 'abw',
		'vnd.amazon.ebook'                                                      => 'azw',
		'x-mobipocket-ebook'                                                    => 'prc',
		'vnd.ms-htmlhelp'                                                       => 'chm',
		'vnd.openxmlformats-officedocument.wordprocessingml.template'           => 'dotx',
		'vnd.openxmlformats-officedocument.spreadsheetml.template'              => 'xltx',
		'vnd.openxmlformats-officedocument.presentationml.template'             => 'potx',
		'vnd.openxmlformats-officedocument.presentationml.slideshow'            => 'ppsx',
		'vnd.ms-powerpoint.slideshow.macroenabled.12'                           => 'ppsm',
		'vnd.ms-project'                                                        => 'mpp',
		'vnd.oasis.opendocument.formula'                                        => 'odf',
		'x-msdos-program'                                                       => 'exe',
		'vnd.ms-works.spreadsheet.12'                                           => 'xlr',

		// Archives.
		'zip'                                                                   => 'zip',
		'x-zip-compressed'                                                      => 'zip',
		'zip-compressed'                                                        => 'zip',
		'x-rar-compressed'                                                      => 'rar',
		'rar'                                                                   => 'rar',
		'rar-compressed'                                                        => 'rar',
		'x-7z-compressed'                                                       => '7z',
		'7z-compressed'                                                         => '7z',
		'x-tar'                                                                 => 'tar',
		'tar'                                                                   => 'tar',
		'gzip'                                                                  => 'gz',
		'x-gzip'                                                                => 'gz',
		'gz'                                                                    => 'gz',
		'x-bzip'                                                                => 'bz',
		'bzip'                                                                  => 'bz',
		'x-bzip2'                                                               => 'bz2',
		'bzip2'                                                                 => 'bz2',
		'x-gtar'                                                                => 'gtar',
		'gtar'                                                                  => 'gtar',
		'x-apple-diskimage'                                                     => 'dmg',
		'apple-diskimage'                                                       => 'dmg',
		'x-stuffit'                                                             => 'sit',
		'stuffit'                                                               => 'sit',
		'x-stuffitx'                                                            => 'sitx',
		'x-debian-package'                                                      => 'deb',
		'deb'                                                                   => 'deb',
		'debian-package'                                                        => 'deb',
		'vnd.android.package-archive'                                           => 'apk',
		'android.package-archive'                                               => 'apk',
		'x-unix-archive'                                                        => 'arj',
		'x-lzh-compressed'                                                      => 'lzh',
		'x-lzx'                                                                 => 'lzx',
		'x-ace-compressed'                                                      => 'ace',
		'ace'                                                                   => 'ace',
		'x-cpio'                                                                => 'cpio',
		'x-shar'                                                                => 'shar',
		'x-sv4cpio'                                                             => 'sv4cpio',
		'x-sv4crc'                                                              => 'sv4crc',
		'x-ustar'                                                               => 'ustar',

		// Code/Programming.
		'javascript'                                                            => 'js',
		'x-javascript'                                                          => 'js',
		'ecmascript'                                                            => 'js',
		'x-python'                                                              => 'py',
		'python'                                                                => 'py',
		'x-java-source'                                                         => 'java',
		'java'                                                                  => 'java',
		'java-source'                                                           => 'java',
		'x-csharp'                                                              => 'cs',
		'csharp'                                                                => 'cs',
		'x-c'                                                                   => 'c',
		'c'                                                                     => 'c',
		'x-c++'                                                                 => 'cpp',
		'c++'                                                                   => 'cpp',
		'cpp'                                                                   => 'cpp',
		'x-php'                                                                 => 'php',
		'php'                                                                   => 'php',
		'x-ruby'                                                                => 'rb',
		'ruby'                                                                  => 'rb',
		'x-sh'                                                                  => 'sh',
		'sh'                                                                    => 'sh',
		'shellscript'                                                           => 'sh',
		'css'                                                                   => 'css',
		'x-sass'                                                                => 'sass',
		'sass'                                                                  => 'sass',
		'x-scss'                                                                => 'scss',
		'scss'                                                                  => 'scss',
		'typescript'                                                            => 'ts',
		'x-typescript'                                                          => 'ts',
		'x-perl'                                                                => 'pl',
		'perl'                                                                  => 'pl',
		'x-go'                                                                  => 'go',
		'go'                                                                    => 'go',
		'x-kotlin'                                                              => 'kt',
		'kotlin'                                                                => 'kt',
		'x-swift'                                                               => 'swift',
		'swift'                                                                 => 'swift',
		'x-rust'                                                                => 'rs',
		'rust'                                                                  => 'rs',
		'x-dart'                                                                => 'dart',
		'dart'                                                                  => 'dart',
		'x-asm'                                                                 => 's',
		'assembly'                                                              => 'asm',
		'x-fortran'                                                             => 'f',
		'fortran'                                                               => 'f',
		'sql'                                                                   => 'sql',
		'x-sql'                                                                 => 'sql',

		// Fonts.
		'x-font-ttf'                                                            => 'ttf',
		'font-ttf'                                                              => 'ttf',
		'ttf'                                                                   => 'ttf',
		'x-font-otf'                                                            => 'otf',
		'font-otf'                                                              => 'otf',
		'otf'                                                                   => 'otf',
		'font-woff'                                                             => 'woff',
		'x-font-woff'                                                           => 'woff',
		'woff'                                                                  => 'woff',
		'font-woff2'                                                            => 'woff2',
		'x-font-woff2'                                                          => 'woff2',
		'woff2'                                                                 => 'woff2',
		'vnd.ms-fontobject'                                                     => 'eot',
		'ms-fontobject'                                                         => 'eot',
		'x-font-bdf'                                                            => 'bdf',
		'x-font-ghostscript'                                                    => 'gsf',
		'x-font-linux-psf'                                                      => 'psf',
		'x-font-pcf'                                                            => 'pcf',
		'x-font-snf'                                                            => 'snf',
		'font-sfnt'                                                             => 'sfnt',
		'x-font-type1'                                                          => 'pfa',
		'font-tdpfr'                                                            => 'pfr',

		// CAD and 3D.
		'dwg'                                                                   => 'dwg',
		'dxf'                                                                   => 'dxf',
		'x-3ds'                                                                 => '3ds',
		'3ds'                                                                   => '3ds',
		'x-obj'                                                                 => 'obj',
		'obj'                                                                   => 'obj',
		'x-stl'                                                                 => 'stl',
		'stl'                                                                   => 'stl',
		'vnd.sketchup.skp'                                                      => 'skp',
		'skp'                                                                   => 'skp',
		'vnd.collada+xml'                                                       => 'dae',
		'model/mesh'                                                            => 'mesh',
		'x-world/x-vrml'                                                        => 'wrl',
		'vrml'                                                                  => 'wrl',
		'vnd.gdl'                                                               => 'gdl',
		'vnd.mts'                                                               => 'mts',
		'vnd.dwf'                                                               => 'dwf',
		'model/iges'                                                            => 'igs',
		'model/vrml'                                                            => 'wrl',
		'x-director'                                                            => 'dir',
		'vnd.google-earth.kml+xml'                                              => 'kml',
		'vnd.google-earth.kmz'                                                  => 'kmz',
		'x-3d+xml'                                                              => 'x3d',
		'vnd.hzn-3d-crossword'                                                  => 'x3d',
		'x-autocad'                                                             => 'dwg',

		// Others.
		'ms-project'                                                            => 'mpp',
		'ms-access'                                                             => 'mdb',
		'x-msaccess'                                                            => 'mdb',
		'adobe.illustrator'                                                     => 'ai',
		'illustrator'                                                           => 'ai',
		'x-msdownload'                                                          => 'exe',
		'oasis.opendocument.formula'                                            => 'odf',
		'x-pkcs7-certificates'                                                  => 'p7b',
		'x-pkcs7-certreqresp'                                                   => 'p7r',
		'x-pkcs12'                                                              => 'p12',
		'x-bittorrent'                                                          => 'torrent',
		'vnd.cups-ppd'                                                          => 'ppd',
		'vnd.adobe.xdp+xml'                                                     => 'xdp',
		'vnd.adobe.xfdf'                                                        => 'xfdf',
		'octet-stream'                                                          => 'bin',
		'x-chat'                                                                => 'chat',
		'x-conference'                                                          => 'nsc',
		'pgp-signature'                                                         => 'pgp',
		'x-x509-ca-cert'                                                        => 'der',
		'mathml+xml'                                                            => 'mathml',
		'vnd.3m.post-it-notes'                                                  => 'pwn',
		'vnd.3gpp.pic-bw-large'                                                 => 'plb',
		'vnd.3gpp.pic-bw-small'                                                 => 'psb',
		'vnd.3gpp.pic-bw-var'                                                   => 'pvb',
		'vnd.3gpp2.tcap'                                                        => 'tcap',
		'vnd.mseq'                                                              => 'mseq',
		'vnd.audiograph'                                                        => 'aep',
		'vnd.authorware-bin'                                                    => 'aab',
		'vnd.cups-raster'                                                       => 'raster',
		'vnd.cup-ppd'                                                           => 'ppd',
		'vnd.dece.data'                                                         => 'uvf',
		'vnd.dece.ttml+xml'                                                     => 'uvt',
		'vnd.dece.unspecified'                                                  => 'uvx',
		'vnd.dece.zip'                                                          => 'uvz',
		'vnd.denovo.fcselayout-link'                                            => 'fe_launch',
		'vnd.dvb.ait'                                                           => 'ait',
		'vnd.ecowin.chart'                                                      => 'mag',
		'vnd.etsi.asic-e+zip'                                                   => 'asice',
		'vnd.etsi.asic-s+zip'                                                   => 'asics',
		'vnd.figma'                                                             => 'fig',
		'vnd.fujixerox.ddd'                                                     => 'ddd',
		'vnd.fujixerox.docuworks'                                               => 'xdw',
		'vnd.fujixerox.docuworks.binder'                                        => 'xbd',
		'vnd.geogebra.file'                                                     => 'ggb',
		'vnd.geogebra.tool'                                                     => 'ggt',
		'x-vcard'                                                               => 'vcf',
		'vnd.microsoft.portable-executable'                                     => 'exe',
		'vnd.mpegurl'                                                           => 'm3u8',
		'vnd.sqlalchemy.imageurl'                                               => 'ima',
		'java-archive'                                                          => 'jar',
	);

	return isset( $normalized[ $subtype ] ) ? $normalized[ $subtype ] : $subtype;
}
