<?php
/**
 * Course info functions
 *
 * @since 2.1.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Outputs the course navigation template for widget.
 *
 * @since 2.1.0
 *
 * @param int   $course_id         Course ID.
 * @param array $widget_instance   Optional. An array of widget settings. Default empty array.
 * @param array $lesson_query_args Optional. An array of lesson `WP_Query` arguments. Default empty array.
 */
function learndash_course_navigation( $course_id, $widget_instance = array(), $lesson_query_args = array() ) {
	$course = get_post( $course_id );

	if ( empty( $course->ID ) || $course_id != $course->ID ) {
		return;
	}

	if ( empty( $course->ID ) || 'sfwd-courses' !== $course->post_type ) {
		return;
	}

	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
	} else {
		$user_id = 0;
	}

	$course_navigation_widget_pager = array();
	global $course_navigation_widget_pager;

	add_action(
		'learndash_course_lessons_list_pager',
		function( $query_result = null ) {
			global $course_navigation_widget_pager;

			$course_navigation_widget_pager['paged'] = 1;

			if ( ( isset( $query_result->query_vars['paged'] ) ) && ( $query_result->query_vars['paged'] > 1 ) ) {
				$course_navigation_widget_pager['paged'] = $query_result->query_vars['paged'];
			}

			$course_navigation_widget_pager['total_items'] = $query_result->found_posts;
			$course_navigation_widget_pager['total_pages'] = $query_result->max_num_pages;
		}
	);

	$lessons = learndash_get_course_lessons_list( $course, $user_id, $lesson_query_args );

	$template_file = SFWD_LMS::get_template(
		'course_navigation_widget',
		array(
			'course_id' => $course_id,
			'course'    => $course,
			'lessons'   => $lessons,
			'widget'    => $widget_instance,
		),
		null,
		true
	);

	if ( ! empty( $template_file ) ) {
		include $template_file;
	}
}

/**
 * Outputs the course navigation admin template for the widget.
 *
 * @since 2.1.0
 *
 * @param int   $course_id         Course ID.
 * @param array $instance          Optional. An array of widget settings. Default empty array.
 * @param array $lesson_query_args Optional. An array of lesson `WP_Query` arguments. Default empty array.
 */
function learndash_course_navigation_admin( $course_id, $instance = array(), $lesson_query_args = array() ) {
	$course = get_post( $course_id );

	if ( empty( $course->ID ) || $course_id != $course->ID ) {
		return;
	}

	$course = get_post( $course_id );

	if ( empty( $course->ID ) || 'sfwd-courses' !== $course->post_type ) {
		return;
	}

	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
	} else {
		$user_id = 0;
	}

	$course_navigation_admin_pager = array();
	global $course_navigation_admin_pager;

	add_action(
		'learndash_course_lessons_list_pager',
		function( $query_result = null ) {
			global $course_navigation_admin_pager;

			$course_navigation_admin_pager['paged'] = 1;

			if ( ( isset( $query_result->query_vars['paged'] ) ) && ( $query_result->query_vars['paged'] > 1 ) ) {
				$course_navigation_admin_pager['paged'] = $query_result->query_vars['paged'];
			}

			$course_navigation_admin_pager['total_items'] = $query_result->found_posts;
			$course_navigation_admin_pager['total_pages'] = $query_result->max_num_pages;
		}
	);

	$lessons = learndash_get_course_lessons_list( $course, $user_id, $lesson_query_args );
	$quizzes = learndash_get_course_quiz_list( $course_id, $user_id );

	SFWD_LMS::get_template(
		'course_navigation_admin',
		array(
			'user_id'          => $user_id,
			'course_id'        => $course_id,
			'course'           => $course,
			'lessons'          => $lessons,
			'course_quiz_list' => $quizzes,
			'widget'           => $instance,
		),
		true
	);
}

/**
 * Includes the course navigation switcher admin template.
 *
 * @since 2.5.0
 *
 * @param int $course_id Course ID.
 */
function learndash_course_switcher_admin( $course_id ) {
	$template_file = SFWD_LMS::get_template(
		'course_navigation_switcher_admin',
		array(),
		null,
		true
	);

	if ( ! empty( $template_file ) ) {
		include $template_file;
	}
}

/**
 * Enqueues the scripts and styles needed to handle pagination.
 *
 * Fires on `load-post.php` and `load-post-new.php` hook.
 *
 * @since 2.5.5
 */
function learndash_course_step_edit_init() {
	global $learndash_assets_loaded;

	$screen = get_current_screen();
	if ( ( 'post' == $screen->base ) && ( in_array( $screen->post_type, array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ), true ) ) ) {

		$filepath = SFWD_LMS::get_template( 'learndash_pager.css', null, null, true );
		if ( ! empty( $filepath ) ) {
			wp_enqueue_style( 'learndash_pager_css', learndash_template_url_from_path( $filepath ), array(), LEARNDASH_SCRIPT_VERSION_TOKEN );
			wp_style_add_data( 'learndash_pager_css', 'rtl', 'replace' );
			$learndash_assets_loaded['styles']['learndash_pager_css'] = __FUNCTION__;
		}

		$filepath = SFWD_LMS::get_template( 'learndash_pager.js', null, null, true );
		if ( ! empty( $filepath ) ) {
			wp_enqueue_script( 'learndash_pager_js', learndash_template_url_from_path( $filepath ), array( 'jquery' ), LEARNDASH_SCRIPT_VERSION_TOKEN, true );
			$learndash_assets_loaded['scripts']['learndash_pager_js'] = __FUNCTION__;
		}
	}
}
add_action( 'load-post.php', 'learndash_course_step_edit_init' );
add_action( 'load-post-new.php', 'learndash_course_step_edit_init' );


/**
 * Adds the content to the course navigation meta box for admin.
 *
 * @since 2.1.0
 */
function learndash_course_navigation_admin_box_content() {
	if ( ( isset( $_GET['post'] ) ) && ( ! empty( $_GET['post'] ) ) ) {
		$course_id = learndash_get_course_id( intval( $_GET['post'] ) );

		if ( ! empty( $course_id ) ) {

			$instance                        = array();
			$instance['show_widget_wrapper'] = true;
			$instance['course_id']           = $course_id;
			$instance['current_lesson_id']   = 0;
			$instance['current_step_id']     = 0;

			$lesson_query_args               = array();
			$lesson_query_args['pagination'] = 'true';
			$lesson_query_args['paged']      = 1;

			$current_post = get_post( intval( $_GET['post'] ) );
			if ( ( is_a( $current_post, 'WP_Post' ) ) && ( is_user_logged_in() ) && ( in_array( $current_post->post_type, array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ), true ) ) ) {

				$course_lessons_per_page = learndash_get_course_lessons_per_page( $course_id );
				if ( $course_lessons_per_page > 0 ) {
					if ( in_array( $current_post->post_type, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ), true ) ) {

						$instance['current_step_id'] = $current_post->ID;
						if ( 'sfwd-lessons' === $current_post->post_type ) {
							$instance['current_lesson_id'] = $instance['current_step_id'];
						} elseif ( in_array( $current_post->post_type, array( 'sfwd-topic', 'sfwd-quiz' ), true ) ) {
							$instance['current_lesson_id'] = learndash_course_get_single_parent_step( $course_id, $instance['current_step_id'], 'sfwd-lessons' );
						}

						if ( ! empty( $instance['current_lesson_id'] ) ) {
							$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
							$course_lesson_ids      = $ld_course_steps_object->get_children_steps( $course_id, 'sfwd-lessons' );

							if ( ! empty( $course_lesson_ids ) ) {
								$course_lessons_paged = array_chunk( $course_lesson_ids, $course_lessons_per_page, true );
								$lessons_paged        = 0;
								foreach ( $course_lessons_paged as $paged => $paged_set ) {
									if ( in_array( (int) $instance['current_lesson_id'], array_map( 'absint', $paged_set ), true ) ) {
										$lessons_paged = $paged + 1;
										break;
									}
								}

								if ( ! empty( $lessons_paged ) ) {
									$lesson_query_args['pagination'] = 'true';
									$lesson_query_args['paged']      = $lessons_paged;
								}
							}
						} elseif ( in_array( $current_post->post_type, array( 'sfwd-quiz' ), true ) ) {
							// If here we have a global Quiz. So we set the pager to the max number.
							$course_lesson_ids = learndash_course_get_steps_by_type( $course_id, 'sfwd-lessons' );
							if ( ! empty( $course_lesson_ids ) ) {
								$course_lessons_paged       = array_chunk( $course_lesson_ids, $course_lessons_per_page, true );
								$lesson_query_args['paged'] = count( $course_lessons_paged );
							}
						}
					}
				} else {
					$lesson_query_args['pagination'] = 'false';

					if ( in_array( $current_post->post_type, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ), true ) ) {
						$instance['current_step_id'] = $current_post->ID;
						if ( 'sfwd-lessons' === $current_post->post_type ) {
							$instance['current_lesson_id'] = $current_post->ID;
						} elseif ( in_array( $current_post->post_type, array( 'sfwd-topic', 'sfwd-quiz' ), true ) ) {
							$instance['current_lesson_id'] = learndash_course_get_single_parent_step( $course_id, $current_post->ID, 'sfwd-lessons' );
						}
					}
				}
			}

			learndash_course_navigation_admin( $course_id, $instance, $lesson_query_args );
		} else {
			echo sprintf(
				// translators: placeholder: Course.
				esc_html_x( 'No associated %s', 'placeholder: Course', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'course' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
			);
		}

		if ( learndash_is_course_shared_steps_enabled() ) {
			learndash_course_switcher_admin( $course_id );
		}
	}
}


/**
 * Gets the course info HTML output for user.
 *
 * @since 2.1.0
 *
 * @param int   $user_id User ID.
 * @param array $atts    Optional. An array of widget attributes. Default empty array.
 *
 * @return string The course info output.
 */
function learndash_course_info( $user_id, $atts = array() ) {
	return SFWD_LMS::get_course_info( $user_id, $atts );
}

/**
 * Handles the AJAX pagination for the courses registered.
 *
 * @since 3.4.0
 *
 * Fires on `ld_course_registered_pager` AJAX action.
 *
 * @return void|string
 */
function learndash_ajax_course_registered_pager() {
	if ( ! is_user_logged_in() ) {
		return '';
	}
	if ( ! current_user_can( 'read' ) ) {
		return '';
	}

	if ( ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( $_POST['nonce'], 'learndash-pager' ) ) ) {

		add_filter(
			'learndash_course_info_paged',
			function( $paged = 1, $context = '' ) {
				if ( ( 'registered' == $context ) && ( isset( $_POST['paged'] ) ) && ( ! empty( $_POST['paged'] ) ) ) {
					$paged = intval( $_POST['paged'] );
				}

				// Always return $paged.
				return $paged;
			},
			10,
			2
		);

		$reply_data = array();

		if ( isset( $_POST['shortcode_atts'] ) ) {
			$shortcode_atts = $_POST['shortcode_atts'];
		} else {
			$shortcode_atts = array();
		}

		$user_id = get_current_user_id();
		if ( learndash_is_group_leader_user() ) {
			if ( ( isset( $shortcode_atts['user_id'] ) ) && ( ! empty( $shortcode_atts['user_id'] ) ) ) {
				if ( learndash_is_group_leader_of_user( $user_id, $shortcode_atts['user_id'] ) ) {
					$user_id = intval( $shortcode_atts['user_id'] );
				}
			}
		} elseif ( learndash_is_admin_user() ) {
			if ( ( isset( $shortcode_atts['user_id'] ) ) && ( ! empty( $shortcode_atts['user_id'] ) ) ) {
				$user_id = intval( $shortcode_atts['user_id'] );
			}
		}

		$shortcode_atts['return'] = true;
		$shortcode_atts['type']   = 'registered';

		// Setup the pager filter.
		if ( ! learndash_ajax_pager_verify_atts( $user_id, $shortcode_atts ) ) {
			return '';
		}

		$user_progress = SFWD_LMS::get_course_info( $user_id, $shortcode_atts );

		if ( ( isset( $user_progress['courses_registered'] ) ) && ( ! empty( $user_progress['courses_registered'] ) ) ) {
			$courses_registered = $user_progress['courses_registered'];

			$level = ob_get_level();
			ob_start();

			$template_file = SFWD_LMS::get_template(
				'course_registered_rows',
				null,
				null,
				true
			);
			if ( ! empty( $template_file ) ) {
				include $template_file;
			}
			$reply_data['content'] = learndash_ob_get_clean( $level );
		}

		if ( isset( $user_progress['courses_registered_pager'] ) ) {
			$reply_data['pager'] = SFWD_LMS::get_template(
				'learndash_pager.php',
				array(
					'pager_results' => $user_progress['courses_registered_pager'],
					'pager_context' => 'course_info_registered',
				)
			);
		}
	}
	echo wp_json_encode( $reply_data );
	die();
}
add_action( 'wp_ajax_ld_course_registered_pager', 'learndash_ajax_course_registered_pager' );

/**
 * Handles the AJAX pagination for the course progress.
 *
 * @since 3.4.0
 *
 * Fires on `ld_course_progress_pager` AJAX action.
 *
 * @return void|string
 */
function learndash_ajax_course_progress_pager() {
	// Not sure why this is here since we have the 'wp_ajax_nopriv_ld_course_progress_pager' action setup.
	if ( ! is_user_logged_in() ) {
		return '';
	}

	if ( ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( $_POST['nonce'], 'learndash-pager' ) ) ) {

		add_filter(
			'learndash_course_info_paged',
			function( $paged = 1, $context = '' ) {
				if ( ( 'courses' == $context ) && ( isset( $_POST['paged'] ) ) && ( ! empty( $_POST['paged'] ) ) ) {
					$paged = intval( $_POST['paged'] );
				}

				// Always return $paged.
				return $paged;
			},
			10,
			2
		);

		$reply_data = array();

		if ( isset( $_POST['shortcode_atts'] ) ) {
			$shortcode_atts = $_POST['shortcode_atts'];
		} else {
			$shortcode_atts = array();
		}

		$user_id = get_current_user_id();
		if ( ( isset( $shortcode_atts['user_id'] ) ) && ( ! empty( $shortcode_atts['user_id'] ) ) ) {
			$shortcode_atts['user_id'] = absint( $shortcode_atts['user_id'] );
			if ( $user_id !== $shortcode_atts['user_id'] ) {
				if ( ( learndash_is_group_leader_user() ) && ( learndash_is_group_leader_of_user( $user_id, $shortcode_atts['user_id'] ) ) ) {
					$user_id = intval( $shortcode_atts['user_id'] );
				} elseif ( learndash_is_admin_user() ) {
					$user_id = intval( $shortcode_atts['user_id'] );
				}
			}
		}

		$shortcode_atts['return'] = true;
		$shortcode_atts['type']   = 'course';

		// Setup the pager filter.
		if ( ! learndash_ajax_pager_verify_atts( $user_id, $shortcode_atts ) ) {
			return '';
		}

		$user_progress = SFWD_LMS::get_course_info( $user_id, $shortcode_atts );

		if ( ( isset( $user_progress['course_progress'] ) ) && ( ! empty( $user_progress['course_progress'] ) ) ) {
			$courses_registered = $user_progress['courses_registered'];
			$course_progress    = $user_progress['course_progress'];

			$level = ob_get_level();
			ob_start();

			$template_file = SFWD_LMS::get_template(
				'course_progress_rows',
				null,
				null,
				true
			);

			if ( ! empty( $template_file ) ) {
				include $template_file;
			}
			$reply_data['content'] = learndash_ob_get_clean( $level );
		}

		if ( isset( $user_progress['course_progress_pager'] ) ) {
			$reply_data['pager'] = SFWD_LMS::get_template(
				'learndash_pager.php',
				array(
					'pager_results' => $user_progress['course_progress_pager'],
					'pager_context' => 'course_info_courses',
				)
			);
		}
	}
	echo wp_json_encode( $reply_data );
	die();
}
add_action( 'wp_ajax_ld_course_progress_pager', 'learndash_ajax_course_progress_pager' );
add_action( 'wp_ajax_nopriv_ld_course_progress_pager', 'learndash_ajax_course_progress_pager' );

/**
 * Handles the AJAX pagination for the quiz progress.
 *
 * Fires on `ld_course_progress_pager` AJAX action.
 *
 * @since 3.4.0
 *
 * @return void|string
 */
function learndash_ajax_quiz_progress_pager() {
	$reply_data = array();

	if ( ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( $_POST['nonce'], 'learndash-pager' ) ) ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		if ( ! current_user_can( 'read' ) ) {
			return '';
		}

		add_filter(
			'learndash_quiz_info_paged',
			function( $paged = 1 ) {
				if ( ( isset( $_POST['paged'] ) ) && ( ! empty( $_POST['paged'] ) ) ) {
					$paged = intval( $_POST['paged'] );
				}
				return $paged;
			}
		);

		if ( isset( $_POST['shortcode_atts'] ) ) {
			$shortcode_atts = $_POST['shortcode_atts'];
		} else {
			$shortcode_atts = array();
		}

		$user_id = get_current_user_id();
		if ( ( isset( $shortcode_atts['user_id'] ) ) && ( ! empty( $shortcode_atts['user_id'] ) ) ) {
			$shortcode_atts['user_id'] = absint( $shortcode_atts['user_id'] );
			if ( $user_id !== $shortcode_atts['user_id'] ) {
				if ( ( learndash_is_group_leader_user() ) && ( learndash_is_group_leader_of_user( $user_id, $shortcode_atts['user_id'] ) ) ) {
					$user_id = intval( $shortcode_atts['user_id'] );
				} elseif ( learndash_is_admin_user() ) {
					$user_id = intval( $shortcode_atts['user_id'] );
				}
			}
		}

		$shortcode_atts['return'] = true;
		$shortcode_atts['type']   = 'quiz';

		// Setup the pager filter.
		if ( ! learndash_ajax_pager_verify_atts( $user_id, $shortcode_atts ) ) {
			return '';
		}

		$user_progress = SFWD_LMS::get_course_info( $user_id, $shortcode_atts );

		if ( ( isset( $user_progress['quizzes'] ) ) && ( ! empty( $user_progress['quizzes'] ) ) ) {
			$quizzes = $user_progress['quizzes'];

			$level = ob_get_level();
			ob_start();

			$template_file = SFWD_LMS::get_template(
				'quiz_progress_rows',
				null,
				null,
				true
			);

			if ( ! empty( $template_file ) ) {
				include $template_file;
			}

			$reply_data['content'] = learndash_ob_get_clean( $level );
		}

		if ( isset( $user_progress['quizzes_pager'] ) ) {
			$reply_data['pager'] = SFWD_LMS::get_template(
				'learndash_pager.php',
				array(
					'pager_results' => $user_progress['quizzes_pager'],
					'pager_context' => 'course_info_quizzes',
				)
			);
		}
	}
	echo wp_json_encode( $reply_data );
	die();
}

add_action( 'wp_ajax_ld_quiz_progress_pager', 'learndash_ajax_quiz_progress_pager' );

/**
 * Handles the AJAX pagination for the courses navigation.
 *
 * Fires on `ld_course_navigation_pager` AJAX action.
 *
 * @since 3.4.0
 */
function learndash_ajax_course_navigation_pager() {
	$reply_data = array();

	if ( ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( $_POST['nonce'], 'learndash-pager' ) ) ) {

		if ( ( isset( $_POST['paged'] ) ) && ( ! empty( $_POST['paged'] ) ) ) {
			$paged = intval( $_POST['paged'] );
		} else {
			$paged = 1;
		}

		if ( ( isset( $_POST['widget_data']['course_id'] ) ) && ( ! empty( $_POST['widget_data']['course_id'] ) ) ) {
			$course_id = intval( $_POST['widget_data']['course_id'] );
		} else {
			$course_id = 0;
		}

		if ( ( isset( $_POST['widget_data']['widget_instance'] ) ) && ( ! empty( $_POST['widget_data']['widget_instance'] ) ) ) {
			$widget_instance = $_POST['widget_data']['widget_instance'];
		} else {
			$widget_instance = array();
		}

		if ( ( ! empty( $course_id ) ) && ( ! empty( $widget_instance ) ) ) {

			$lesson_query_args       = array();
			$course_lessons_per_page = learndash_get_course_lessons_per_page( $course_id );
			if ( $course_lessons_per_page > 0 ) {
				$lesson_query_args['pagination'] = 'true';
				$lesson_query_args['paged']      = $paged;
			}
			$widget_instance['show_widget_wrapper'] = false;

			$level = ob_get_level();
			ob_start();
			learndash_course_navigation( $course_id, $widget_instance, $lesson_query_args );
			$reply_data['content'] = learndash_ob_get_clean( $level );
		}
	}

	echo wp_json_encode( $reply_data );
	die();
}

add_action( 'wp_ajax_ld_course_navigation_pager', 'learndash_ajax_course_navigation_pager' );
add_action( 'wp_ajax_nopriv_ld_course_navigation_pager', 'learndash_ajax_course_navigation_pager' );

/**
 * Handles the AJAX pagination for the admin courses navigation.
 *
 * Fires on `ld_course_navigation_admin_pager` AJAX action.
 *
 * @since 3.4.0
 */
function learndash_ajax_course_navigation_admin_pager() {
	$reply_data = array();

	if ( ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( $_POST['nonce'], 'learndash-pager' ) ) ) {

		if ( ( isset( $_POST['paged'] ) ) && ( ! empty( $_POST['paged'] ) ) ) {
			$paged = intval( $_POST['paged'] );
		} else {
			$paged = 1;
		}

		if ( ( isset( $_POST['widget_data'] ) ) && ( ! empty( $_POST['widget_data'] ) ) ) {
			$widget_data = $_POST['widget_data'];
		} else {
			$widget_data = array();
		}

		if ( ( isset( $widget_data['course_id'] ) ) && ( ! empty( $widget_data['course_id'] ) ) ) {
			$course_id = intval( $widget_data['course_id'] );
		} else {
			$course_id = 0;
		}

		if ( ( ! empty( $course_id ) ) && ( ! empty( $widget_data ) ) ) {

			if ( ( isset( $_POST['widget_data']['nonce'] ) ) && ( ! empty( $_POST['widget_data']['nonce'] ) ) && ( wp_verify_nonce( $_POST['widget_data']['nonce'], 'ld_course_navigation_admin_pager_nonce_' . $course_id . '_' . get_current_user_id() ) ) ) {

				$lesson_query_args                  = array();
				$lesson_query_args['pagination']    = 'true';
				$lesson_query_args['paged']         = $paged;
				$widget_data['show_widget_wrapper'] = false;

				$level = ob_get_level();
				ob_start();
				learndash_course_navigation_admin( $course_id, $widget_data, $lesson_query_args );
				$reply_data['content'] = learndash_ob_get_clean( $level );
			}
		}
	}
	echo wp_json_encode( $reply_data );
	die();
}

add_action( 'wp_ajax_ld_course_navigation_admin_pager', 'learndash_ajax_course_navigation_admin_pager' );

/**
 * Verifies the attributes for AJAX pagination.
 *
 * @since 2.5.7
 *
 * @param int   $user_id        User ID.
 * @param array $shortcode_atts Shortcode attributes.
 *
 * @return boolean Returns true if the attributes are verified otherwise false.
 */
function learndash_ajax_pager_verify_atts( $user_id, $shortcode_atts ) {
	$use_filter = false;

	if ( ( ! empty( $user_id ) ) && ( isset( $shortcode_atts['pagenow'] ) ) ) {
		if ( ( isset( $shortcode_atts['pagenow_nonce'] ) ) && ( ! empty( $shortcode_atts['pagenow_nonce'] ) ) ) {
			if ( ( 'profile.php' == $shortcode_atts['pagenow'] ) || ( 'user-edit.php' == $shortcode_atts['pagenow'] ) ) {
				if ( wp_verify_nonce( $shortcode_atts['pagenow_nonce'], $shortcode_atts['pagenow'] . '-' . $user_id ) ) {
					$use_filter = true;
				}
			} elseif ( 'group_admin_page' == $shortcode_atts['pagenow'] ) {
				if ( ( isset( $shortcode_atts['group_id'] ) ) && ( intval( $shortcode_atts['group_id'] ) ) ) {
					if ( wp_verify_nonce( $shortcode_atts['pagenow_nonce'], $shortcode_atts['pagenow'] . '-' . intval( $shortcode_atts['group_id'] ) . '-' . $user_id ) ) {
						$use_filter = true;
					}
				}
			} elseif ( 'learndash' == $shortcode_atts['pagenow'] ) {
				if ( wp_verify_nonce( $shortcode_atts['pagenow_nonce'], $shortcode_atts['pagenow'] . '-' . $user_id ) ) {
					// Hard return here because we don't want to set $user_filter to true as that will trigger the
					// logic below to show the admin only details link.
					return true;
				}
			}
		}

		if ( true == $use_filter ) {
			// The following filter is called during the template output. Normally if the admin is viewing profile.php
			// We show the edit options. but via AJAX we don't know from where the user is viewing. It may be a front-end
			// page etc. So as part of the shortcode atts we store the pagenow and a nonce we then verify within the logic below.
			add_filter(
				'learndash_show_user_course_complete_options',
				function( $show_admin_options, $user_id = 0 ) {
					if ( current_user_can( 'edit_users' ) ) {
						$show_admin_options = true;
					}

					return $show_admin_options;
				},
				1,
				2
			);
		}
	}

	return $use_filter;
}
