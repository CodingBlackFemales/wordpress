<?php
/**
 * Deprecated functions from LD 3.4.0
 * The functions will be removed in a later version.
 *
 * @package LearnDash\Deprecated
 * @since 3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Other deprecated class functions.
 */
/**
 * In includes/class-ld-lms.php
 * $sfwd_lms->course_display_settings();
 * $sfwd_lms->lesson_display_settings();
 * $sfwd_lms->topic_display_settings();
 * $sfwd_lms->quiz_display_settings();
 */

if ( ! function_exists( 'is_quiz_accessable' ) ) {
	/**
	 * Checks if the quiz is accessible to the user.
	 *
	 * @since 2.1.0
	 * @deprecated 3.4.0 Use {@see 'learndash_is_quiz_accessable'} instead.
	 *
	 * @param int|null     $user_id $user_id  Optional. The ID of User to check.  Defaults to the current logged-in user. Default null.
	 * @param WP_Post|null $post              Optional. The `WP_Post` quiz object. Default null.
	 * @param boolean      $return_incomplete Optional. Whether to return last incomplete step. Default false.
	 * @param int          $course_id         Optional. Course ID. Default 0.
	 *
	 * @return int|WP_Post|void Returns 1 if the quiz is accessible by user otherwise 0. If the `$return_incomplete`
	 *                          parameter is set to true it may return `WP_Post` object for incomplete step.
	 */
	function is_quiz_accessable( $user_id = null, $post = null, $return_incomplete = false, $course_id = 0 ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_is_quiz_accessable' );
		}
		return learndash_is_quiz_accessable( $user_id, $post, $return_incomplete, $course_id );
	}
}

if ( ! function_exists( 'has_global_quizzes' ) ) {
	/**
	 * Checks if the resource has any quizzes.
	 *
	 * @since 2.1.0
	 * @deprecated 3.4.0 Use {@see 'learndash_has_global_quizzes'} instead.
	 *
	 * @param int|null $id Optional. The ID of the resource like course, lesson, topic, etc. Default null.
	 *
	 * @return boolean Returns true if the resource has quizzes otherwise false.
	 */
	function has_global_quizzes( $id = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_has_global_quizzes' );
		}

		return learndash_has_global_quizzes( $id );
	}
}

if ( ! function_exists( 'is_all_global_quizzes_complete' ) ) {
	/**
	 * Checks if all quizzes for a course are complete for the user.
	 *
	 * @since 2.1.0
	 * @deprecated 3.4.0 Use {@see 'learndash_is_all_global_quizzes_complete'} instead.
	 *
	 * @param int|null $user_id Optional. User ID. Default null.
	 * @param int|null $id      Optional. The ID of the resource. Default null.
	 *
	 * @return boolean
	 */
	function is_all_global_quizzes_complete( $user_id = null, $id = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_is_all_global_quizzes_complete' );
		}

		return learndash_is_all_global_quizzes_complete( $user_id, $id );
	}
}

if ( ! function_exists( 'learndash_get_course_steps_ORG' ) ) {
	/**
	 * Gets the list of course step IDs.
	 *
	 * @deprecated 3.4.0 Use {@see 'learndash_get_course_steps'} instead.
	 *
	 * @param int   $course_id          Optional. The ID of the course. Default 0.
	 * @param array $include_post_types Optional. An array of post types to include in course steps. Default array contains 'sfwd-lessons' and 'sfwd-topic'.
	 *
	 * @return array An array of course step IDs.
	 */
	function learndash_get_course_steps_ORG( $course_id = 0, $include_post_types = array( 'sfwd-lessons', 'sfwd-topic' ) ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_get_course_steps' );
		}

		return learndash_get_course_steps( $course_id, $include_post_types );
	}
}

if ( ! function_exists( 'learndash_quiz_continue_link_OLD' ) ) {
	/**
	 * Outputs the quiz continue link(old).
	 *
	 * @deprecated 3.4.0 Use {@see 'learndash_quiz_continue_link'} instead.
	 *
	 * @param int $id Quiz ID.
	 *
	 * @return string The quiz continue link output.
	 */
	function learndash_quiz_continue_link_OLD( $id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid

		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_quiz_continue_link' );
		}

		return learndash_quiz_continue_link( $id );
	}
}

if ( ! function_exists( 'learndash_get_global_quiz_list_OLD' ) ) {
	/**
	 * Gets the quiz list for a resource(old).
	 *
	 * @global WP_Post $post Global post object.
	 *
	 * @since 2.1.0
	 * @deprecated 3.4.0 Use {@see 'learndash_get_global_quiz_list'} instead.
	 *
	 * @param int|null $id An ID of the resource.
	 *
	 * @return array An array of quizzes.
	 */
	function learndash_get_global_quiz_list_OLD( $id = null ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid

		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_get_global_quiz_list' );
		}

		return learndash_get_global_quiz_list( $id );
	}
}

if ( ! function_exists( 'learndash_course_get_steps_by_type_ORG1' ) ) {
	/**
	 * Gets the course steps by type.
	 *
	 * @deprecated 3.4.0 Use {@see 'learndash_course_get_steps_by_type'} instead.
	 *
	 * @param int    $course_id Optional. Course ID. Default 0.
	 * @param string $step_type Optional. The type of the step. Default empty.
	 *
	 * @return array An array of course step IDs.
	 */
	function learndash_course_get_steps_by_type_ORG1( $course_id = 0, $step_type = '' ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid

		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_course_get_steps_by_type' );
		}

		return learndash_course_get_steps_by_type( $course_id, $step_type );
	}
}

if ( ! function_exists( 'learndash_get_legacy_course_id' ) ) {
	/**
	 * Gets the legacy course ID for a resource.
	 *
	 * @deprecated 3.4.0
	 *
	 * Determine the type of ID is being passed in.  Should be the ID of
	 * anything that belongs to a course (Lesson, Topic, Quiz, etc).
	 *
	 * @global wpdb    $wpdb WordPress database abstraction object.
	 * @global WP_Post $post Global post object.
	 *
	 * @since 2.1.0
	 *
	 * @param  WP_Post|int|null $id Optional. ID of the resource. Default null.
	 *
	 * @return string ID of the course.
	 */
	function learndash_get_legacy_course_id( $id = null ) {

		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0' );
		}

		global $post;

		if ( empty( $id ) ) {
			if ( ! is_single() || is_home() ) {
				return false;
			}

			$id = $post->ID;
		}

		$terms = wp_get_post_terms( $id, 'courses' );

		if ( empty( $terms ) || empty( $terms[0] ) || empty( $terms[0]->slug ) ) {
			return 0;
		}

		$courseslug = $terms[0]->slug;

		global $wpdb;

		$term_taxonomy_id = $wpdb->get_var(
			$wpdb->prepare(
				"
			SELECT `term_taxonomy_id` FROM $wpdb->term_taxonomy tt, $wpdb->terms t
			WHERE slug = %s
			AND t.term_id = tt.term_id
			AND tt.taxonomy = 'courses'
			",
				$courseslug
			)
		);

		$course_id = $wpdb->get_var(
			$wpdb->prepare(
				"
			SELECT `ID` FROM $wpdb->term_relationships, $wpdb->posts
			WHERE `ID` = `object_id`
			AND `term_taxonomy_id` = %d
			AND `post_type` = 'sfwd-courses'
			AND `post_status` = 'publish'
			",
				$term_taxonomy_id
			)
		);

		return $course_id;
	}
}


if ( ! function_exists( 'should_enqueue_assets' ) ) {
	/**
	 * Checks if the course builder assets should be enqueued.
	 *
	 * @deprecated 3.4.0
	 *
	 * @return boolean Returns true if the assets should be enqueued otherwise false.
	 */
	function should_enqueue_assets() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0' );
		}

		$screen    = get_current_screen();
		$course_id = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : get_the_ID(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Enqueue course builder assets only when required.
		if ( ( 'post' === $screen->base && 'sfwd-courses' === get_post_type( $course_id ) ) ||
			'sfwd-courses_page_courses-builder' === $screen->id ) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'learndash_filter_lesson_options' ) ) {
	/**
	 * Updates the filter lesson options.
	 *
	 * @deprecated 3.4.0
	 *
	 * @param array  $options  Setting options.
	 * @param string $location Location index.
	 * @param array  $values   Current options stored for a location.
	 *
	 * @return array An array of lesson options.
	 */
	function learndash_filter_lesson_options( $options, $location, $values ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0' );
		}

		if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$viewed_course_id = intval( $_GET['course_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( ( isset( $values[ $location . '_course' ] ) ) && ( ! empty( $values[ $location . '_course' ] ) ) && ( intval( $values[ $location . '_course' ] ) !== intval( $_GET['course_id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $options[ $location . '_course' ] ) ) {
					unset( $options[ $location . '_course' ] );
				}
				if ( isset( $options[ $location . '_lesson' ] ) ) {
					unset( $options[ $location . '_lesson' ] );
				}
			}
		}

		return $options;
	}
}

if ( ! function_exists( 'learndash_transition_course_shared_steps' ) ) {
	/**
	 * Transitions the course steps logic from using shared steps to legacy.
	 *
	 * @since 3.0.0
	 * @deprecated 3.4.0
	 *
	 * @param int $course_id Optional. Course ID to process. Default 0.
	 */
	function learndash_transition_course_shared_steps( $course_id = 0 ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0' );
		}

		if ( ! empty( $course_id ) ) {
			if ( 'yes' !== LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
				$course_steps = get_post_meta( $course_id, 'ld_course_steps', true );
				if ( isset( $course_steps['h'] ) ) {
					// If here then Shared Steps was enabled.

					$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
					$ld_course_steps_object->set_steps( $course_steps['h'] );
				}
			}
		}
	}
}

if ( ! function_exists( 'is_previous_complete' ) ) {
	/**
	 * Checks if the previous topic or lesson is complete.
	 *
	 * @since 2.1.0
	 * @deprecated 3.4.0 Use {@see 'learndash_is_previous_complete'} instead.
	 *
	 * @param  WP_Post $post The `WP_Post` object of lesson or topic.
	 *
	 * @return int Returns 1 if the previous lesson or topic is completed otherwise 0.
	 */
	function is_previous_complete( $post ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_is_previous_complete' );
		}

		return learndash_is_previous_complete( $post );
	}
}

if ( ! function_exists( 'learndash_course_set_lessons_start_page' ) ) {
	/**
	 * Redirects users to the next available lesson page when course lesson pagination is enabled.
	 *
	 * For example, we have a course with 100 lessons and the course has per page set to 10. The student can complete
	 * up to lesson 73. When the student returns to the course we don't want to default to show the first page
	 * (lessons 1-10). Instead, we want to redirect the user to page 7 showing lessons 71-80.
	 *
	 * @since 2.5.4
	 * @deprecated 3.4.0
	 */
	function learndash_course_set_lessons_start_page() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0' );
		}
	}
}

if ( ! function_exists( 'wp_ajax_ld_course_registered_pager' ) ) {
	/**
	 * Handles the AJAX pagination for the courses registered.
	 *
	 * Fires on `ld_course_registered_pager` AJAX action.
	 *
	 * @deprecated 3.4.0 Use {@see 'learndash_ajax_course_registered_pager'} instead.
	 *
	 * @return void|string
	 */
	function wp_ajax_ld_course_registered_pager() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_ajax_course_registered_pager' );
		}

		return learndash_ajax_course_registered_pager();
	}
}

if ( ! function_exists( 'wp_ajax_ld_course_progress_pager' ) ) {
	/**
	 * Handles the AJAX pagination for the course progress.
	 *
	 * Fires on `ld_course_progress_pager` AJAX action.
	 *
	 * @deprecated 3.4.0 Use {@see 'learndash_ajax_course_progress_pager'} instead.
	 *
	 * @return void|string
	 */
	function wp_ajax_ld_course_progress_pager() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_ajax_course_progress_pager' );
		}

		return learndash_ajax_course_progress_pager();
	}
}

if ( ! function_exists( 'wp_ajax_ld_quiz_progress_pager' ) ) {
	/**
	 * Handles the AJAX pagination for the quiz progress.
	 *
	 * Fires on `ld_course_progress_pager` AJAX action.
	 *
	 * @deprecated 3.4.0 Use {@see 'learndash_ajax_quiz_progress_pager'} instead.
	 *
	 * @return void|string
	 */
	function wp_ajax_ld_quiz_progress_pager() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_ajax_quiz_progress_pager' );
		}

		return learndash_ajax_quiz_progress_pager();
	}
}

if ( ! function_exists( 'wp_ajax_ld_course_navigation_pager' ) ) {
	/**
	 * Handles the AJAX pagination for the courses navigation.
	 *
	 * Fires on `ld_course_navigation_pager` AJAX action.
	 *
	 * @since 2.5.4
	 * @deprecated 3.4.0 Use {@see 'learndash_ajax_course_navigation_pager'} instead.
	 */
	function wp_ajax_ld_course_navigation_pager() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_ajax_course_navigation_pager' );
		}

		return learndash_ajax_course_navigation_pager();
	}
}

if ( ! function_exists( 'wp_ajax_ld_course_navigation_admin_pager' ) ) {
	/**
	 * Handles the AJAX pagination for the admin courses navigation.
	 *
	 * Fires on `ld_course_navigation_admin_pager` AJAX action.
	 *
	 * @since 2.5.4
	 * @deprecated 3.4.0 Use {@see 'learndash_ajax_course_navigation_admin_pager'} instead.
	 */
	function wp_ajax_ld_course_navigation_admin_pager() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_ajax_course_navigation_admin_pager' );
		}

		return learndash_ajax_course_navigation_admin_pager();
	}
}

if ( ! function_exists( 'lesson_hasassignments' ) ) { // cspell:disable-line.
	/**
	 * Utility function to check whether a lesson has an assignment.
	 *
	 * @since 2.1.0
	 * @deprecated 3.4.0 Use {@see 'learndash_lesson_hasassignments'} instead. // cspell:disable-line.
	 *
	 * @param WP_Post $post The assignment `WP_Post` object.
	 *
	 * @return boolean
	 */
	function lesson_hasassignments( $post ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound // cspell:disable-line.
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_lesson_hasassignments' ); // cspell:disable-line.
		}

		return learndash_lesson_hasassignments( $post ); // cspell:disable-line.
	}
}

if ( ! function_exists( 'get_course_groups_users_access' ) ) {
	/**
	 * Gets the group's user IDs if the course is associated with the group.
	 *
	 * @since 2.3.0
	 * @deprecated 3.4.0 Use {@see 'learndash_get_course_groups_users_access'} instead.
	 *
	 * @param int $course_id Optional. Course ID. Default 0.
	 *
	 * @return array An array of user IDs.
	 */
	function get_course_groups_users_access( $course_id = 0 ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_get_course_groups_users_access' );
		}

		return learndash_get_course_groups_users_access( $course_id );
	}
}

if ( ! function_exists( 'array_map_r' ) ) {
	/**
	 * Utility function to traverse the multidimensional array and apply user function.
	 *
	 * @since 2.1.2
	 * @deprecated 3.4.0 Use {@see 'learndash_array_map_r'} instead.
	 *
	 * @param callable $func The Callable user defined or system function. This
	 *                       should be 'esc_attr', or some similar function.
	 * @param array    $arr  The array to traverse and cleanup.
	 *
	 * @return array $arr The cleaned array after calling user functions.
	 */
	function array_map_r( $func, $arr ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_array_map_r' );
		}

		return learndash_array_map_r( $func, $arr );
	}
}

if ( ! function_exists( 'learndash_convert_settings_to_single' ) ) {
	/**
	 * Saves the course, lesson, topic, and quiz settings meta to separate post meta.
	 *
	 * Normally Course, Lesson, Topic and Quiz settings are stored into a single post meta array. This
	 * function runs after after that save and will save the array elements into individual postmeta
	 * fields.
	 *
	 * @since 2.4.3
	 * @deprecated 3.4.0
	 *
	 * @param int    $post_id   Optional. Course ID. Default 0.
	 * @param array  $settings  Optional. An array of settings to be stored. Default empty array.
	 * @param string $prefix     Optional. The post meta prefix. Default empty.
	 */
	function learndash_convert_settings_to_single( $post_id = 0, $settings = array(), $prefix = '' ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0' );
		}
	}
}

if ( ! function_exists( 'learndash_check_convert_settings_to_single' ) ) {
	/**
	 * Saves the course, lesson, topic, and quiz settings meta to separate post meta if not already converted.
	 *
	 * @deprecated 3.4.0
	 *
	 * @param int    $post_id Optional. Post ID. Default 0.
	 * @param string $prefix   Optional. The post meta key prefix. Default empty.
	 */
	function learndash_check_convert_settings_to_single( $post_id = 0, $prefix = '' ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0' );
		}
	}
}

if ( ! function_exists( 'learndash_get_report_user_ids_NEW_PP21' ) ) {
	/**
	 * Gets the list of user IDs for the report.
	 *
	 * This function will determine the list of users the current user can see. For example for
	 * group leader, it will show the only user within the leader's groups. For admin, it will
	 * show all users.
	 *
	 * @deprecated 3.4.0 Use {@see 'learndash_get_report_user_ids'} instead.
	 *
	 * @param int   $user_id    Optional. User ID. Defaults to the current user ID. Default 0.
	 * @param array $query_args Optional. User query arguments. Default empty array.
	 *
	 * @return array An array of user IDs.
	 */
	function learndash_get_report_user_ids_NEW_PP21( $user_id = 0, $query_args = array() ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_get_report_user_ids' );
		}

		return learndash_get_report_user_ids( $user_id, $query_args );
	}
}

if ( ! function_exists( 'ld_remove_lessons_and_quizzes_page' ) ) {
	/**
	 * Redirects to the home page if the user lands on archive pages for lesson or quiz post types.
	 *
	 * Fires on `wp` hook.
	 *
	 * @since 2.1.0
	 * @deprecated 3.4.0 Use {@see 'learndash_remove_lessons_and_quizzes_page'} instead.
	 *
	 * @param WP $wp The `WP` object.
	 */
	function ld_remove_lessons_and_quizzes_page( $wp ) { //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_remove_lessons_and_quizzes_page' );
		}

		return learndash_remove_lessons_and_quizzes_page( $wp );
	}
}

if ( ! function_exists( 'ld_footer_payment_buttons' ) ) {
	/**
	 * Prints the dropdown button to the footer.
	 *
	 * Fires on `wp_footer` hook.
	 *
	 * @deprecated 3.4.0 Use {@see 'learndash_footer_payment_buttons'} instead.
	 *
	 * @global string $dropdown_button Dropdown button markup.
	 */
	function ld_footer_payment_buttons() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_footer_payment_buttons' );
		}

		return learndash_footer_payment_buttons();
	}
}

if ( ! function_exists( 'wpLD_tiny_mce_before_init' ) ) {
	/**
	 * Loads the certificate image as the background for the visual editor.
	 *
	 * Fires on `tiny_mce_before_init` hook.
	 *
	 * @todo  confirm intent of function and if it's still needed
	 *        not currently functional
	 *
	 * @since 2.1.0
	 * @deprecated 3.4.0 Use {@see 'learndash_wp_tiny_mce_before_init'} instead.
	 *
	 * @param array $init_array The tinymce editor settings.
	 *
	 * @return array The tinymce editor settings.
	 */
	function wpLD_tiny_mce_before_init( $init_array ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_wp_tiny_mce_before_init' );
		}

		return learndash_wp_tiny_mce_before_init( $init_array );
	}
}

if ( ! function_exists( 'filter_mce_css' ) ) {
	/**
	 * Loads editor styles for LearnDash.
	 *
	 * Fires on `mce_css` hook.
	 * We need to add the LD custom CSS to the function parameter. Not replace it
	 * see https://codex.wordpress.org/Plugin_API/Filter_Reference/mce_css
	 *
	 * @since 2.1.0
	 * @deprecated 3.4.0 Use {@see 'learndash_filter_mce_css'} instead.
	 *
	 * @param string $mce_css Optional. Comma-delimited list of stylesheets. Default empty.
	 *
	 * @return string Comma-delimited list of stylesheets.
	 */
	function filter_mce_css( $mce_css = '' ) { //phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_filter_mce_css' );
		}

		return learndash_filter_mce_css( $mce_css );
	}
}

if ( ! function_exists( 'ldp' ) ) {
	/**
	 * Prints the given string in preformated text.
	 *
	 * @since 2.1.0
	 * @deprecated 3.4.0
	 *
	 * @param string $msg The message to print in preformated text.
	 */
	function ldp( $msg ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0' );
		}

		echo '<pre>';
		print_r( $msg ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		echo '</pre>';
	}
}

if ( ! function_exists( 'ld_debug' ) ) {

	/**
	 * Log debug messages to file.
	 *
	 * @deprecated 3.4.0
	 *
	 * @param int|str|arr|obj|bool $msg Data to log.
	 */
	function ld_debug( $msg ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0' );
		}
	}
}

if ( ! function_exists( 'is_learndash_license_valid' ) ) {
	/**
	 * Checks Whether the learndash license is valid or not.
	 *
	 * @since 2.1.0
	 * @deprecated 3.4.0 Use {@see 'learndash_is_learndash_license_valid'} instead.
	 *
	 * @return boolean
	 */
	function is_learndash_license_valid() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.4.0', 'learndash_is_learndash_license_valid' );
		}

		return learndash_is_learndash_license_valid();
	}
}
