<?php
/**
 * MemberpressLMS integration filters
 *
 * @package BuddyBossPro\Integration\MemberpressLMS
 * @since 2.6.30
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use memberpress\courses\models as models;
use memberpress\courses as base;
use memberpress\courses\helpers as helpers;

add_filter( 'bb_feed_excluded_post_types', 'bb_feed_not_allowed_meprlms_post_types' );
add_filter( 'bb_nouveau_get_activity_entry_bubble_buttons', 'bb_mepr_nouveau_remove_edit_activity_entry_buttons', 999, 2 );
add_filter( 'bp_is_post_type_feed_enable', 'bb_meprlms_post_type_feed_is_enable', 10, 3 );
add_filter( 'bb_enable_blog_feed', 'bb_meprlms_enable_existing_blog_feed_option', 10, 2 );
add_filter( 'template_include', 'bb_meprlms_override_template', PHP_INT_MAX );
add_action( 'wp_enqueue_scripts', 'bb_meprlms_enqueue_scripts', PHP_INT_MAX );
add_filter( 'bp_nouveau_feedback_messages', 'bb_meprlms_nouveau_feedback_messages' );
add_filter( 'mepr_view_paths', 'bb_meprlms_template_paths', PHP_INT_MAX );
add_filter( 'body_class', 'bb_meprlms_body_class' );
add_filter( 'mepr_mpcs_gradebook_view_paths', 'bb_meprlms_gradebook_template_paths', PHP_INT_MAX );
add_filter( 'mepr_mpcs_quizzes_view_paths', 'bb_meprlms_quizzes_template_paths', PHP_INT_MAX );
add_filter( 'template_redirect', 'bb_meprlms_template_redirect', PHP_INT_MAX );
add_filter( 'bp_activity_pre_transition_post_type_status', 'bb_meprlms_activity_pre_transition_post_type_status', 10, 4 );
add_filter( 'mpcs_account_nav_courses_output', 'bb_meprlms_account_remove_my_courses_link', PHP_INT_MAX );
add_action( 'add_meta_boxes', 'bb_meprlms_course_add_meta_boxes', 50 );
add_filter( 'bb_readylaunch_left_sidebar_middle_content', 'bb_readylaunch_middle_content_meprlms_courses', 20, 1 );

/**
 * Function to exclude MemberpressLMS CPT from Activity setting screen.
 *
 * @since 2.6.30
 *
 * @param array $post_types Array of post types.
 *
 * @return array
 */
function bb_feed_not_allowed_meprlms_post_types( $post_types ) {

	$bb_meprlms_post_types = ! empty( bb_meprlms_get_post_types() ) ? bb_meprlms_get_post_types() : array();

	if ( ! empty( $post_types ) ) {
		$post_types = array_merge( $post_types, $bb_meprlms_post_types );
	} else {
		$post_types = $bb_meprlms_post_types;
	}

	return $post_types;
}

/**
 * We're removing the Edit Button for MemberpressLMS activity.
 *
 * @since 2.6.30
 *
 * @param array $buttons     Buttons Argument.
 * @param int   $activity_id Activity ID.
 *
 * @return array
 */
function bb_mepr_nouveau_remove_edit_activity_entry_buttons( $buttons, $activity_id ) {
	if (
		! (
			class_exists( 'memberpress\courses\helpers\Courses' ) &&
			bb_meprlms_enable()
		)
	) {
		return $buttons;
	}

	$exclude_action_arr = array_keys( bb_meprlms_course_activities() );
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
 * Function to check if MemberpressLMS integration is disabled then activity should
 * not be record for any new MemberpressLMS post type.
 *
 * @since 2.6.30
 *
 * @param bool   $retval    Current value.
 * @param string $post_type Post type.
 * @param bool   $default   Default value.
 *
 * @return bool
 */
function bb_meprlms_post_type_feed_is_enable( $retval, $post_type, $default ) {
	if (
		! empty( $post_type ) &&
		in_array( $post_type, bb_meprlms_get_post_types(), true ) &&
		! bb_meprlms_enable()
	) {
		return false;
	}

	return $retval;
}

/**
 * Function to check existing blog is enabled or not.
 *
 * @since 2.6.30
 *
 * @param bool   $retval    Current value.
 * @param string $post_type Post type.
 *
 * @return bool
 */
function bb_meprlms_enable_existing_blog_feed_option( $retval, $post_type ) {
	if (
		! empty( $post_type ) &&
		in_array( $post_type, bb_meprlms_get_post_types(), true ) &&
		bb_meprlms_enable() &&
		bp_get_option( bb_post_type_feed_option_name( $post_type ) )
	) {
		return true;
	}

	return $retval;
}

/**
 * Override default template with the courses page template.
 *
 * @since 2.6.30
 *
 * @param string $template current template.
 *
 * @return string $template modified template.
 */
function bb_meprlms_override_template( $template ) {
	global $post;

	if ( bb_meprlms_enable() && class_exists( 'memberpress\courses\helpers\App' ) && ! helpers\App::is_classroom() && isset( $post ) && is_a( $post, 'WP_Post' ) ) {
		$post_type       = $post->post_type;
		$custom_template = false;
		if ( class_exists( 'memberpress\courses\models\Course' ) && models\Course::$cpt === $post_type ) {

			// Handle course archives.
			if ( is_post_type_archive( models\Course::$cpt ) ) {
				$custom_template = bb_meprlms_get_template_path( 'archive-mpcs-courses.php' );
			} elseif ( is_single() ) {

				// Handle single course pages.
				$custom_template = bb_meprlms_get_template_path( 'single-mpcs-course.php' );
			}
		} elseif ( is_single() && class_exists( 'memberpress\courses\models\Lesson' ) && models\Lesson::$cpt === $post_type ) {

			// Handle single lesson pages.
			$custom_template = bb_meprlms_get_template_path( 'single-mpcs-lesson.php' );
		} elseif ( is_single() && class_exists( 'memberpress\assignments\models\Assignment' ) && memberpress\assignments\models\Assignment::$cpt === $post_type ) {

			// Handle single assignment pages.
			$custom_template = bb_meprlms_get_template_path( 'single-mpcs-assignment.php', 'assignments' );
		} elseif ( is_single() && class_exists( 'memberpress\quizzes\models\Quiz' ) && memberpress\quizzes\models\Quiz::$cpt === $post_type ) {

			// Handle single quiz pages.
			$custom_template = bb_meprlms_get_template_path( 'single-mpcs-quiz.php', 'quizzes' );
		}

		if ( $custom_template ) {
			return $custom_template;
		}
	}

	return $template;
}

/**
 * Enquue styles.
 *
 * @since 2.6.30
 */
function bb_meprlms_enqueue_scripts() {
	if ( bb_meprlms_enable() && class_exists( 'memberpress\courses\helpers\App' ) && ! helpers\App::is_classroom() ) {
		global $post;
		if ( isset( $post ) && is_a( $post, 'WP_Post' ) && ( models\Course::$cpt === $post->post_type || models\Lesson::$cpt === $post->post_type ) ) {
			wp_enqueue_script( 'mpcs-clipboard-js', base\JS_URL . '/vendor/clipboard.min.js', array(), base\VERSION );
			wp_enqueue_style( 'mpcs-tooltipster', base\CSS_URL . '/vendor/tooltipster.bundle.min.css', array(), base\VERSION );
			wp_enqueue_style( 'mpcs-tooltipster-borderless', base\CSS_URL . '/vendor/tooltipster-sideTip-borderless.min.css', array(), base\VERSION );
			wp_enqueue_script( 'mpcs-tooltipster', base\JS_URL . '/vendor/tooltipster.bundle.min.js', array( 'jquery' ), base\VERSION );
			wp_enqueue_style( 'wp-block-gallery' );
			wp_enqueue_script( 'mpcs-classroom-js', base\JS_URL . '/classroom.js', array( 'jquery' ), base\VERSION );
			wp_enqueue_style( 'mpcs-fontello-styles', base\FONTS_URL . '/fontello/css/mp-courses.css', array(), base\VERSION );
		}
	}
}

/**
 * Adds custom MemberPress template paths.
 *
 * @since 2.6.30
 *
 * @param array $paths Existing template paths.
 *
 * @return array Modified template paths.
 */
function bb_meprlms_template_paths( $paths ) {
	return bb_meprlms_add_template_paths( $paths, 'memberpress' );
}

/**
 * Adds custom MemberPress quizzes template paths.
 *
 * @since 2.6.30
 *
 * @param array $paths Existing template paths.
 *
 * @return array Modified template paths.
 */
function bb_meprlms_quizzes_template_paths( $paths ) {
	return bb_meprlms_add_template_paths( $paths, 'memberpress/quizzes' );
}

/**
 * Adds custom MemberPress gradebook template paths.
 *
 * @since 2.6.30
 *
 * @param array $paths Existing template paths.
 *
 * @return array Modified template paths.
 */
function bb_meprlms_gradebook_template_paths( $paths ) {
	return bb_meprlms_add_template_paths( $paths, 'memberpress/assignments' );
}

/**
 * Template redirect for memberpress account my courses to buddyboss account.
 *
 * @since 2.6.30
 */
function bb_meprlms_template_redirect() {
	global $post;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
	if ( bb_meprlms_enable() && is_user_logged_in() && class_exists( 'MeprUser' ) && MeprUser::is_account_page( $post ) && 'courses' === $action ) {

		// Get the current user's ID.
		$user_id = get_current_user_id();

		// Get the current user's profile URL.
		$profile_url = bp_core_get_user_domain( $user_id );

		wp_safe_redirect( $profile_url . '/courses' );
		exit;
	}
}

/**
 * Add body class.
 *
 * @param array $classes Existing classes array.
 *
 * @since 2.6.30
 */
function bb_meprlms_body_class( $classes ) {
	if ( class_exists( 'memberpress\courses\helpers\App' ) && ! helpers\App::is_classroom() ) {
		global $post;
		if (
			isset( $post ) &&
			is_a( $post, 'WP_Post' ) &&
			in_array(
				$post->post_type,
				array(
					models\Course::$cpt,
					models\Lesson::$cpt,
					'mpcs-assignment',
					'mpcs-quiz',
				),
				true
			)
		) {
			$classes[] = 'mpcs-classroom';
		}
	}

	return $classes;
}

/**
 * Stop to add unlinked Lessons, Quizzes and Assignments acvitity.
 *
 * @since 2.6.30
 *
 * @param bool   $bool       Whether to proceed with the activity entry.
 * @param string $new_status The new status of the post.
 * @param string $old_status The old status of the post.
 * @param object $post       The post object.
 *
 * @return bool $bool Returns false to prevent activity entry, or the original bool.
 */
function bb_meprlms_activity_pre_transition_post_type_status( $bool, $new_status, $old_status, $post ) {
	if (
		! empty( $post )
		&& (
			'mpcs-lesson' === $post->post_type ||
			'mpcs-assignment' === $post->post_type ||
			'mpcs-quiz' === $post->post_type
		)
	) {

		switch ( $post->post_type ) {
			case 'mpcs-lesson':
				if ( class_exists( 'memberpress\courses\models\Lesson' ) ) {
					$obj = new models\Lesson( $post->ID );
				}
				break;
			case 'mpcs-assignment':
				if ( class_exists( 'memberpress\assignments\models\Assignment' ) ) {
					$obj = new memberpress\assignments\models\Assignment( $post->ID );
				}
				break;
			case 'mpcs-quiz':
				if ( class_exists( 'memberpress\quizzes\models\Quiz' ) ) {
					$obj = new memberpress\quizzes\models\Quiz( $post->ID );
				}
				break;
		}

		if ( ! empty( $obj ) ) {
			$course = $obj->course();
			if ( empty( $course ) || 'publish' !== $course->post_status ) {
				return false;
			}
		}
	} elseif (
		! empty( $post ) &&
		'mpcs-course' === $post->post_type
	) {

		$model_course = new models\Course( $post->ID );

		// Get lesson, assignment, quiz.
		$curriculums = $model_course->lessons();
		if ( ! empty( $curriculums ) ) {
			foreach ( $curriculums as $curriculum ) {
				if ( 'publish' === $curriculum->post_status ) {
					$cpost = get_post( $curriculum->ID );

					$cpost->post_date_gmt = $model_course->post_date_gmt;
					$cpost->post_date     = $model_course->post_date;

					if (
						'publish' === $new_status &&
						$new_status !== $old_status
					) {
						bp_activity_post_type_publish( $curriculum->ID, $cpost );
					} elseif (
						'publish' !== $new_status &&
						'publish' === $old_status
					) {
						bp_activity_post_type_unpublish( $curriculum->ID, $cpost );
					}
				}
			}
		}
	}

	return $bool;
}

/**
 * Remove my courses link from memberpress account page.
 *
 * @param bool $content Content to filter and remove the my courses link.
 *
 * @since 2.6.30
 *
 * @return string
 */
function bb_meprlms_account_remove_my_courses_link( $content ) {
	if ( bb_meprlms_enable() ) {
		$content = '';
	}
	return $content;
}

/**
 * Publish Activity for lessons, quizzes and assignments with appropriate conditions.
 *
 * @since 2.6.30
 */
function bb_meprlms_course_add_meta_boxes() {
	global $post;

	if ( ! bp_is_active( 'activity' ) || empty( $post ) ) {
		return;
	}

	if (
		in_array( $post->post_type, bb_meprlms_get_post_types(), true ) &&
		! bb_meprlms_enable() &&
		! post_type_supports( $post->post_type, 'buddypress-activity' )
	) {
		return;
	}

	// Add lesson, assignment, quiz activity when course is published.
	if (
		'mpcs-course' === $post->post_type &&
		'publish' === $post->post_status
	) {

		$model_course = new models\Course( $post->ID );

		// Get lesson, assignment, quiz.
		$curriculums = $model_course->lessons();
		if ( ! empty( $curriculums ) ) {
			foreach ( $curriculums as $curriculum ) {
				if ( 'publish' === $curriculum->post_status ) {
					bp_activity_post_type_publish( $curriculum->ID, get_post( $curriculum->ID ) );
				}
			}
		}
	}
}

/**
 * Function to get the user enrolled course or all courses.
 *
 * This function retrieves the courses a user is enrolled in if the MeprLMS integration is enabled.
 * It fetches the courses for the logged-in user and includes the course title, permalink, and thumbnail.
 *
 * @since 2.7.50
 *
 * @param array $args Array of arguments.
 *
 * @return array $args User enrolled courses.
 */
function bb_readylaunch_middle_content_meprlms_courses( $args = array() ) {
	if ( bb_meprlms_enable() ) {
		$course_data['integration'] = 'meprlms';
		if ( $args['has_sidebar_data'] && $args['is_sidebar_enabled_for_courses'] ) {
			$user_id = bp_displayed_user_id();
			if ( ! empty( $user_id ) ) {
				$courses = bb_meprlms_get_user_courses( bp_loggedin_user_id(), '', 0, 5 );
			} else {
				$courses = bb_meprlms_get_courses( array( 'posts_per_page' => 5 ) );
			}
			if ( ! empty( $courses->posts ) ) {
				foreach ( $courses->posts as $post ) {
					if ( has_post_thumbnail( $post->ID ) ) {
						$thumbnail_url = get_the_post_thumbnail( $post->ID, apply_filters( 'mpcs_course_thumbnail_size', 'mpcs-course-thumbnail' ), array( 'class' => 'img-responsive' ) );
					} else {
						$thumbnail_url = '<img src="' . esc_url( bb_meprlms_integration_url( '/assets/images/course-placeholder.jpg' ) ) . '" class="img-responsive" alt="" />';
					}

					$course_data['items'][ $post->ID ] = array(
						'title'     => get_the_title( $post->ID ),
						'permalink' => get_the_permalink( $post->ID ),
						'thumbnail' => $thumbnail_url,
					);
				}
			}
		}
		$args['courses'] = $course_data;
	}

	return $args;
}
