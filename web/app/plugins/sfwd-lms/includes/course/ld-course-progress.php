<?php
/**
 * Course Progress Functions
 *
 * @since 2.1.0
 *
 * @package LearnDash\Course
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// cspell:ignore accessable .

/**
 * Outputs the HTML output to mark a course complete.
 *
 * Must meet requirements of course to mark the course as complete.
 *
 * @since 2.1.0
 *
 * @param WP_Post $post The `WP_Post` lesson or topic object.
 * @param array   $atts Optional. An array of attributes to mark course complete. Default empty array.
 *
 * @return string HTML output to mark course complete
 */
function learndash_mark_complete( $post, $atts = array() ) {

	if ( ! is_user_logged_in() ) {
		return '';
	}

	$user_id = get_current_user_id();

	if ( isset( $_POST['sfwd_mark_complete'] ) && isset( $_POST['post'] ) && intval( $_POST['post'] ) == $post->ID ) {
		return '';
	}

	$bypass_course_limits_admin_users = learndash_can_user_bypass( $user_id, 'learndash_course_progression', $post->ID, $post );

	/**
	 * Bypass prerequisites.
	 *
	 * @since 2.4.0
	 *
	 * @param boolean $bypass  True/False is user is allowed to bypass.
	 * @param integer $user_id User ID.
	 * @param integer $post_id Post ID.
	 * @param object  $post    WP_Post instance
	 */
	$bypass_course_limits_admin_users = apply_filters( 'learndash_prerequities_bypass', $bypass_course_limits_admin_users, $user_id, $post->ID, $post ); // cspell:disable-line -- prerequities are prerequisites...

	$course_id = learndash_get_course_id( $post->ID );

	if ( ( learndash_lesson_progression_enabled() ) && ( ! $bypass_course_limits_admin_users ) ) {

		if ( ! sfwd_lms_has_access( $course_id, $user_id ) ) {
			return '';
		}

		// Check Course Prerequisites.
		if ( ! learndash_is_course_prerequities_completed( $course_id, $user_id ) ) { // cspell:disable-line -- prerequities are prerequisites...
			return '';
		}

		if ( ! learndash_check_user_course_points_access( $course_id, $user_id ) ) {
			return '';
		}

		$step_quiz_list = learndash_get_lesson_quiz_list( $post->ID, $user_id, $course_id );

		if ( ! empty( $step_quiz_list ) ) {
			foreach ( $step_quiz_list as $quiz ) {
				if ( 'notcompleted' === $quiz['status'] ) {
					return '';
				}
			}
		}

		if ( 'sfwd-lessons' === $post->post_type ) {
			$progress = learndash_get_course_progress( $user_id, $post->ID );

			if ( ! empty( $progress['this']->completed ) ) {
				/** This filter is documented in includes/class-ld-cpt-instance.php */
				if ( ! apply_filters( 'learndash_previous_step_completed', false, $progress['this']->ID, $user_id ) ) {
					return learndash_show_mark_incomplete( $post, $atts );
				}
			}

			if ( ! empty( $progress['prev'] ) && empty( $progress['prev']->completed ) && learndash_lesson_progression_enabled() ) {
				/** This filter is documented in includes/class-ld-cpt-instance.php */
				if ( ! apply_filters( 'learndash_previous_step_completed', false, $progress['prev']->ID, $user_id ) ) {
					return '';
				}
			}

			if ( ! learndash_lesson_topics_completed( $post->ID ) ) {
				/** This filter is documented in includes/class-ld-cpt-instance.php */
				if ( ! apply_filters( 'learndash_previous_step_completed', false, $post->ID, $user_id ) ) {
					return '';
				}
			}
		}

		if ( 'sfwd-topic' === $post->post_type ) {
			$progress = learndash_get_course_progress( $user_id, $post->ID );

			if ( ! empty( $progress['this']->completed ) ) {
				/** This filter is documented in includes/class-ld-cpt-instance.php */
				if ( ! apply_filters( 'learndash_previous_step_completed', false, $progress['this']->ID, $user_id ) ) {
					return learndash_show_mark_incomplete( $post, $atts );
				}
			}

			if ( ! empty( $progress['prev'] ) && empty( $progress['prev']->completed ) && learndash_lesson_progression_enabled() ) {
				/** This filter is documented in includes/class-ld-cpt-instance.php */
				if ( ! apply_filters( 'learndash_previous_step_completed', false, $progress['prev']->ID, $user_id ) ) {
					return '';
				}
			}

			if ( learndash_lesson_progression_enabled() ) {
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
					$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
				} else {
					$lesson_id = learndash_get_setting( $post, 'lesson' );
				}
				$lesson = get_post( $lesson_id );

				if ( ! learndash_is_previous_complete( $lesson ) ) {
					/** This filter is documented in includes/class-ld-cpt-instance.php */
					if ( ! apply_filters( 'learndash_previous_step_completed', false, $lesson->ID, $user_id ) ) {
						return '';
					}
				}
			}
		}

		$previous_item_id = learndash_user_progress_get_previous_incomplete_step( $user_id, $course_id, $post->ID );
		if ( ! empty( $previous_item_id ) && ( $previous_item_id !== $post->ID ) ) {
			return '';
		}
	} else {
		$progress = learndash_get_course_progress( $user_id, $post->ID );

		if ( ! empty( $progress['this']->completed ) ) {
			return '';
		}
	}

	if ( learndash_lesson_hasassignments( $post ) ) { // cspell:disable-line.
		global $learndash_assignment_upload_message;

		$ret = SFWD_LMS::get_template(
			'learndash_lesson_assignment_upload_form.php',
			array(
				'course_step_post'                => $post,
				'user_id'                         => $user_id,
				'assignment_upload_error_message' => $learndash_assignment_upload_message,
			)
		);
		if ( ! is_null( $ret ) ) {
			return $ret;
		}

		if ( ( ! learndash_is_admin_user( $user_id ) ) || ( ! $bypass_course_limits_admin_users ) ) {
			$assignments = learndash_get_user_assignments( $post->ID, $user_id, $course_id );
			if ( empty( $assignments ) ) {
				return false;
			} else {
				foreach ( $assignments as $assignment ) {
					if ( ! learndash_is_assignment_approved_by_meta( $assignment->ID ) ) {
						return false;
					}
				}
			}
		}
	}

	$return          = '';
	$button_disabled = '';
	$time            = 0;
	$time_value      = learndash_forced_lesson_time( $post );

	if ( ! empty( $time_value ) ) {
		$time = learndash_convert_lesson_time_time( $time_value );
	}

	if ( ( ! learndash_is_admin_user( $user_id ) ) || ( ! $bypass_course_limits_admin_users ) ) {

		if ( ! empty( $time ) ) {
			$time_cookie_key = learndash_forced_lesson_time_cookie_key( $post );
			if ( ! empty( $time_cookie_key ) ) {
				/**
				 * Note this is not a 100% check. We are only checking if the cookie
				 * key exists and is zero. But this cookie could have been set from
				 * external.
				 */
				if ( ( isset( $_COOKIE[ 'learndash_timer_cookie_' . $time_cookie_key ] ) ) && ( '0' === $_COOKIE[ 'learndash_timer_cookie_' . $time_cookie_key ] ) ) {
					$time = 0;
				}
			}

			if ( ! empty( $time ) ) {
				// Set the mark complete button disabled.
				$button_disabled = " disabled='disabled' ";

				wp_enqueue_script(
					'jquery-cookie',
					plugins_url( 'js/jquery.cookie' . learndash_min_asset() . '.js', WPPROQUIZ_FILE ),
					array( 'jquery' ),
					'1.4.0',
					true
				);
				global $learndash_assets_loaded;
				$learndash_assets_loaded['scripts']['jquery-cookie'] = __FUNCTION__;
			}
		}
	}

	/**
	 * Filters attributes for mark a course complete form.
	 *
	 * @since 3.0.0
	 *
	 * @param array   $attributes An array of form, button, and timer attributes to override id and class.
	 * @param WP_Post $post       WP_Post object being displayed.
	 */
	$atts = apply_filters( 'learndash_mark_complete_form_atts', $atts, $post );

	if ( isset( $atts['form']['id'] ) ) {
		$form_id = ' id="' . esc_attr( $atts['form']['id'] ) . '" ';
	} else {
		$form_id = '';
	}

	if ( isset( $atts['form']['class'] ) ) {
		$form_class = ' class="sfwd-mark-complete ' . esc_attr( $atts['form']['class'] ) . '" ';
	} else {
		$form_class = ' class="sfwd-mark-complete" ';
	}

	if ( isset( $atts['button']['id'] ) ) {
		$button_id = ' id="' . esc_attr( $atts['button']['id'] ) . '" ';
	} else {
		$button_id = '';
	}

	if ( isset( $atts['button']['class'] ) ) {
		$button_class = ' class="learndash_mark_complete_button ' . esc_attr( $atts['button']['class'] ) . '" ';
	} else {
		$button_class = ' class="learndash_mark_complete_button" ';
	}

	$form_fields = '<input type="hidden" value="' . $post->ID . '" name="post" />
				<input type="hidden" value="' . learndash_get_course_id( $post->ID ) . '" name="course_id" />
				<input type="hidden" value="' . wp_create_nonce( 'sfwd_mark_complete_' . get_current_user_id() . '_' . $post->ID ) . '" name="sfwd_mark_complete" />
				<input type="submit" ' . $button_id . ' value="' . LearnDash_Custom_Label::get_label( 'button_mark_complete' ) . '" ' . $button_class . ' ' . $button_disabled . '/>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
	/**
	 * Filters form fields to mark a course complete.
	 *
	 * @since 3.0.0
	 *
	 * @param string  $form_fields HTML output for course mark complete form fields.
	 * @param WP_Post $post       WP_Post object being displayed.
	 */
	$form_fields = apply_filters( 'learndash_mark_complete_form_fields', $form_fields, $post );

	$return .= '<form ' . $form_id . ' ' . $form_class . ' method="post" action="">' . $form_fields . '</form>';

	if ( ( ! learndash_is_admin_user( $user_id ) ) || ( ! $bypass_course_limits_admin_users ) ) {
		if ( ! empty( $time ) ) {
			if ( isset( $atts['timer']['id'] ) ) {
				$timer_id = ' id="' . esc_attr( $atts['timer']['id'] ) . '" ';
			} else {
				$timer_id = '';
			}

			$timer_class = ' class="learndash_timer';
			if ( isset( $atts['timer']['class'] ) ) {
				$timer_class .= ' ' . esc_attr( $atts['timer']['class'] );
			}
			$timer_class .= '" ';

			$return .= '<span ' . $timer_id . ' ' . $timer_class . ' data-timer-seconds="' . $time . '" data-button="input.learndash_mark_complete_button" data-cookie-key="' . $time_cookie_key . '"></span>';
		}
	}

	/**
	 * Filters HTML output to mark a course completion.
	 *
	 * @since 2.1.0
	 *
	 * @param string  $return HTML output to mark course complete.
	 * @param WP_Post $post   WP_Post object being displayed.
	 */
	return apply_filters( 'learndash_mark_complete', $return, $post );
}

/**
 * Handles the AJAX output to mark a quiz complete.
 *
 * @since 2.1.0
 *
 * @global WP_Post $post Global post object.
 *
 * @param int|null $quiz_id   Optional. Quiz ID. Default null.
 * @param int|null $lesson_id Optional. Lesson ID. Default null.
 */
function learndash_ajax_mark_complete( $quiz_id = null, $lesson_id = null ) {

	if ( empty( $quiz_id ) || empty( $lesson_id ) ) {
		return;
	}

	global $post;

	$current_user = wp_get_current_user();
	$user_id      = $current_user->ID;

	$can_attempt_again = learndash_can_attempt_again( $user_id, $quiz_id );

	if ( $can_attempt_again ) {
		$link = learndash_next_lesson_quiz( false, $user_id, $lesson_id, null );
	} else {
		$link = learndash_next_lesson_quiz( false, $user_id, $lesson_id, array( $quiz_id ) );
	}

}

/**
 * Checks if the lesson topics are completed.
 *
 * @since 2.1.0
 *
 * @param  int     $lesson_id            Lesson ID.
 * @param  boolean $mark_lesson_complete Optional. Whether to mark the lesson complete. Default false.
 *
 * @return boolean Returns true if the lesson is completed otherwise false.
 */
function learndash_lesson_topics_completed( $lesson_id, $mark_lesson_complete = false ) {
	$topics = learndash_get_topic_list( $lesson_id );

	if ( empty( $topics[0]->ID ) ) {
		return true;
	}

	$progress = learndash_get_course_progress( null, $topics[0]->ID );

	if ( empty( $progress['posts'] ) || ! is_array( $progress['posts'] ) ) {
		return false;
	}

	foreach ( $progress['posts'] as $topic ) {
		if ( empty( $topic->completed ) ) {
			return false;
		}
	}

	if ( $mark_lesson_complete ) {
		$user_id = get_current_user_id();
		learndash_process_mark_complete( null, $lesson_id );
	}

	return true;
}

/**
 * Processes a request to mark a course complete.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 2.1.0
 *
 * @param WP_Post|null $post Optional. The `WP_Post` object. Defaults to global post object.
 */
function learndash_mark_complete_process( $post = null ) {
	// This is wrong. This function hooks into the 'wp' action. That action doesn't pass a post object or post_id.
	// The $post object set were is not even used. We only need the _POST[post] (post_id) variable from the form.
	if ( empty( $post ) ) {
		global $post;
	}

	if ( ( isset( $_POST['sfwd_mark_complete'] ) ) && ( ! empty( $_POST['sfwd_mark_complete'] ) ) && ( isset( $_POST['post'] ) ) && ( ! empty( $_POST['post'] ) ) ) {
		if ( empty( $post ) || empty( $post->ID ) ) {
			$post = get_post(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- I suppose it's what they wanted.
			if ( empty( $post ) || empty( $post->ID ) ) {
				return;
			}
		}

		$post_id = intval( $_POST['post'] );

		if ( isset( $_POST['course_id'] ) ) {
			$course_id = intval( $_POST['course_id'] );
		} else {
			$course_id = learndash_get_course_id( $post_id );
		}

		if ( isset( $_POST['userid'] ) ) {
			$userid = intval( $_POST['userid'] );
		} else {
			if ( ! is_user_logged_in() ) {
				return;
			}

			$userid = get_current_user_id();
		}

		/**
		 * Verify the form is valid
		 *
		 * @since 2.2.1.2
		 */
		if ( ! wp_verify_nonce( $_POST['sfwd_mark_complete'], 'sfwd_mark_complete_' . $userid . '_' . $post_id ) ) {
			return;
		}

		$return = learndash_process_mark_complete( $userid, $post_id, false, $course_id );

		if ( $return ) {
			// Remove the lesson/topic timer cookie once the step is completed.
			$timer_cookie_key = learndash_forced_lesson_time_cookie_key( $post_id );
			if ( ! empty( $timer_cookie_key ) ) {
				if ( isset( $_COOKIE[ 'learndash_timer_cookie_' . $timer_cookie_key ] ) ) {
					unset( $_COOKIE[ 'learndash_timer_cookie_' . $timer_cookie_key ] );
				}
				// empty value and expiration one hour before.
				$res = setcookie( 'learndash_timer_cookie_' . $timer_cookie_key, '', time() - 3600 );
			}

			// Remove the lesson/topic video progress cookie once the step is completed.
			learndash_video_delete_cookie_for_step( $post_id, $course_id, $userid );

			$next_lesson_redirect = learndash_get_next_lesson_redirect();
		} else {
			$next_lesson_redirect = get_permalink();
		}

		if ( ! empty( $next_lesson_redirect ) ) {

			/**
			 * Filters URL to redirect to after marking a course complete.
			 *
			 * @param string $redirect_url Next lesson redirect URL.
			 * @param int    $post_id      Post ID.
			 */
			$next_lesson_redirect = apply_filters( 'learndash_completion_redirect', $next_lesson_redirect, $post_id );
			if ( ! empty( $next_lesson_redirect ) ) {
				learndash_safe_redirect( $next_lesson_redirect );
			}
		}
	}
}
add_action( 'wp', 'learndash_mark_complete_process' );

/**
 * Gets the course permalink.
 *
 * @since 2.1.0
 *
 * @param int|null $id Optional. The ID of the resource like course, topic, lesson, quiz, etc. Default null.
 *
 * @return string The course permalink.
 */
function learndash_get_course_url( $id = null ) {

	if ( empty( $id ) ) {
		$id = learndash_get_course_id();
	}

	return get_permalink( $id );
}

/**
 * Redirects the user to next lesson.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 2.1.0
 *
 * @param WP_Post|null $post Optional. The `WP_Post` object. Defaults to global post object. Default null.
 *
 * @return string Returns empty string if the next lesson redirect link empty.
 */
function learndash_get_next_lesson_redirect( $post = null ) {
	if ( empty( $post->ID ) ) {
		global $post;
	}

	$next = learndash_next_post_link( '', true, $post );

	if ( ! empty( $next ) ) {
		$link = $next;
	} else {
		if ( 'sfwd-topic' === $post->post_type ) {
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
				$course_id = learndash_get_course_id( $post->ID );
				$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
			} else {
				$lesson_id = learndash_get_setting( $post, 'lesson' );
			}
			$link = get_permalink( $lesson_id );
		} else {
			$course_id = learndash_get_course_id( $post );
			$link      = learndash_next_global_quiz( true, null, $course_id );
		}
	}

	if ( ! empty( $link ) ) {

		/** This filter is documented in includes/course/ld-course-progress.php */
		$link = apply_filters( 'learndash_completion_redirect', $link, $post->ID );
		if ( ! empty( $link ) ) {
			learndash_safe_redirect( $link );
		}
	}

	return '';
}

/**
 * Redirects the user after quiz completion.
 *
 * Fires on `wp` hook.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 2.1.0
 */
function learndash_quiz_redirect() {
	global $post;

	$current_user = wp_get_current_user();
	$user_id      = $current_user->ID;

	if ( ! empty( $_GET['quiz_redirect'] ) && ! empty( $_GET['quiz_id'] ) && ! empty( $_GET['quiz_type'] ) && ! empty( $_GET['course_id'] ) && 'global' == $_GET['quiz_type'] ) {

		$quiz_id           = intval( $_GET['quiz_id'] );
		$can_attempt_again = learndash_can_attempt_again( $user_id, $quiz_id );

		if ( $can_attempt_again ) {
			$link = learndash_next_global_quiz();
		} else {
			$link = learndash_next_global_quiz( true, null, null, array( $quiz_id ) );
		}

		/** This filter is documented in includes/course/ld-course-progress.php */
		$link = apply_filters( 'learndash_completion_redirect', $link, $quiz_id );
		if ( ! empty( $link ) ) {
			learndash_safe_redirect( $link );
		}
	} else {

		if ( ! empty( $_GET['quiz_redirect'] ) && ! empty( $_GET['quiz_id'] ) && ! empty( $_GET['quiz_type'] ) && ! empty( $_GET['lesson_id'] ) && 'lesson' == $_GET['quiz_type'] ) {
			$quiz_id   = intval( $_GET['quiz_id'] );
			$lesson_id = intval( $_GET['lesson_id'] );

			if ( isset( $_GET['course_id'] ) ) {
				$course_id = absint( $_GET['course_id'] );
			}
			if ( empty( $course_id ) ) {
				$course_id = learndash_get_course_id();
			}

			$link = '';

			$next_step = 0;
			if ( isset( $_GET['next_step'] ) ) {
				$next_step = absint( $_GET['next_step'] );
			}

			if ( ( 1 === $next_step ) && ( ! empty( $user_id ) ) && ( ! empty( $course_id ) ) && ( ! empty( $quiz_id ) ) ) {
				$next_incomplete_step_id = learndash_user_progress_get_next_incomplete_step( $user_id, $course_id, $quiz_id );
				if ( ! empty( $next_incomplete_step_id ) ) {
					$link = learndash_get_step_permalink( $next_incomplete_step_id, $course_id );
				}

				if ( empty( $link ) ) {
					$link = get_permalink( $course_id );
				}
			} else {
				$link = learndash_next_lesson_quiz( true, $user_id, $lesson_id, null );
				if ( empty( $link ) ) {
					$link = learndash_next_post_link( '', true );
				}

				if ( empty( $link ) ) {
					$lesson_post = get_post( $lesson_id );
					if ( 'sfwd-topic' === $lesson_post->post_type ) {
						if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
							$course_id = learndash_get_course_id( $lesson_post->ID );
							$lesson    = learndash_course_get_single_parent_step( $course_id, $lesson_post->ID );
						} else {
							$lesson = learndash_get_setting( $lesson_post, 'lesson' );
						}
						$link = learndash_get_step_permalink( $lesson, $course_id );
					} else {
						$link = learndash_next_global_quiz();
					}
				}
			}

			if ( ! empty( $link ) ) {

				/** This filter is documented in includes/course/ld-course-progress.php */
				$link = apply_filters( 'learndash_completion_redirect', $link, $quiz_id );
				if ( ! empty( $link ) ) {
					learndash_safe_redirect( $link );
				}
			}
		}
	}
}
add_action( 'wp', 'learndash_quiz_redirect' );

/**
 * Checks whether a user can attempt the quiz again.
 *
 * @since 2.1.0
 *
 * @param int $user_id User ID.
 * @param int $quiz_id Quiz ID.
 *
 * @return boolean Returns true if the user can attempt quiz again otherwise false.
 */
function learndash_can_attempt_again( $user_id, $quiz_id ) {
	$quizmeta = get_post_meta( $quiz_id, '_sfwd-quiz', true );

	if ( isset( $quizmeta['sfwd-quiz_repeats'] ) ) {
		$repeats = $quizmeta['sfwd-quiz_repeats'];
	} else {
		$repeats = '';
	}

	/**
	 * Filters number of quiz attempts allowed for a user.
	 *
	 * @param int $repeats Number of quiz attempts allowed.
	 * @param int $user_id User ID.
	 * @param int $quiz_id Quiz ID.
	 */
	$repeats = apply_filters( 'learndash_allowed_repeats', $repeats, $user_id, $quiz_id );

	if ( '' == $repeats ) {
		return true;
	}

	$quiz_results = get_user_meta( $user_id, '_sfwd-quizzes', true );

	$count = 0;

	if ( ! empty( $quiz_results ) ) {
		foreach ( $quiz_results as $quiz ) {
			if ( $quiz['quiz'] == $quiz_id ) {
				$count++;
			}
		}
	}

	if ( $repeats > $count - 1 ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Checks if the previous topic or lesson is complete.
 *
 * @since 3.4.0
 *
 * @param  WP_Post $post The `WP_Post` object of lesson or topic.
 *
 * @return int Returns 1 if the previous lesson or topic is completed otherwise 0.
 */
function learndash_is_previous_complete( $post ) {
	$progress = learndash_get_course_progress( null, $post->ID );

	if ( empty( $progress ) ) {
		return 1;
	}

	if ( ! empty( $progress['prev'] ) && empty( $progress['prev']->completed ) ) {
		return 0;
	} else {
		return 1;
	}
}

/**
 * Returns the previous lesson/topic to be completed.
 *
 * @since 2.3.0
 *
 * @param WP_Post $post The `WP_Post` object.
 *
 * @return WP_Post|null The `WP_Post` object of lesson/topic to be completed.
 */
function learndash_get_previous( $post ) {
	$progress = learndash_get_course_progress( null, $post->ID );
	if ( ! empty( $progress['prev'] ) ) {
		return $progress['prev'];
	}

	return null;
}

/**
 * Updates the user meta with completion status for any resource.
 *
 * @since 2.1.0
 *
 * @param int|null $user_id       Optional. User ID. Default null.
 * @param int|null $postid        Optional. The ID of the resource like course, lesson, topic, etc. Default null.
 * @param boolean  $onlycalculate Optional. Whether to mark the resource as complete. Default false.
 * @param int      $course_id     Optional. Course ID. Default 0.
 *
 * @return boolean Returns true if the meta is updated successfully otherwise false.
 */
function learndash_process_mark_complete( $user_id = null, $postid = null, $onlycalculate = false, $course_id = 0 ) {
	if ( ( defined( 'LEARNDASH_COURSE_FUNCTIONS_LEGACY' ) ) && ( true === LEARNDASH_COURSE_FUNCTIONS_LEGACY ) ) {
		return learndash_process_mark_complete_legacy( $user_id, $postid, $onlycalculate, $course_id );
	}

	if ( empty( $user_id ) ) {
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$user_id      = $current_user->ID;
		} else {
			return false;
		}
	} else {
		$current_user = get_user_by( 'id', $user_id );
	}

	$post = get_post( $postid );
	if ( ! ( $post instanceof WP_Post ) ) {
		return false;
	}

	if ( ! $onlycalculate ) {

		/**
		 * Filters whether to mark a process complete.
		 *
		 * @since 2.1.0
		 *
		 * @param boolean $mark_complete Whether to mark a process complete.
		 * @param WP_Post $post          WP_Post object to be checked.
		 * @param WP_User $current_user  Current logged in WP_User object.
		 */
		$process_completion = apply_filters( 'learndash_process_mark_complete', true, $post, $current_user );

		if ( ! $process_completion ) {
			return false;
		}
	}

	if ( 'sfwd-topic' === $post->post_type ) {
		if ( learndash_is_course_builder_enabled() ) {
			if ( empty( $course_id ) ) {
				$course_id = learndash_get_course_id( $post->ID );
			}
			$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
		} else {
			$lesson_id = learndash_get_setting( $post, 'lesson' );
		}
	}

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id( $postid );
	}

	if ( empty( $course_id ) ) {
		return false;
	}

	$course_progress = learndash_user_get_course_progress( $user_id, $course_id, 'legacy' );
	if ( ( empty( $course_progress ) ) || ( ! is_array( $course_progress ) ) ) {
		$course_progress = array(
			'lessons' => array(),
			'topics'  => array(),
		);
	}

	if ( ( ! isset( $course_progress['lessons'] ) ) || ( empty( $course_progress['lessons'] ) ) ) {
		$course_progress['lessons'] = array();
	}

	if ( ( ! isset( $course_progress['topics'] ) ) || ( empty( $course_progress['topics'] ) ) ) {
		$course_progress['topics'] = array();
	}

	if ( 'sfwd-topic' === $post->post_type && empty( $course_progress['topics'][ $lesson_id ] ) ) {
		$course_progress['topics'][ $lesson_id ] = array();
	}

	$lesson_completed = false;
	$topic_completed  = false;

	if ( ! $onlycalculate && 'sfwd-lessons' === $post->post_type && empty( $course_progress['lessons'][ $postid ] ) ) {
		$course_progress['lessons'][ $postid ] = 1;
		$lesson_completed                      = true;
	}

	if ( ! $onlycalculate && 'sfwd-topic' === $post->post_type && empty( $course_progress['topics'][ $lesson_id ][ $postid ] ) ) {
		$course_progress['topics'][ $lesson_id ][ $postid ] = 1;
		$topic_completed                                    = true;
	}

	$completed_old = isset( $course_progress['completed'] ) ? $course_progress['completed'] : 0;

	$completed = learndash_course_get_completed_steps( $user_id, $course_id, $course_progress );

	$course_progress['completed'] = $completed;
	$course_progress['total']     = learndash_get_course_steps_count( $course_id );

	/**
	 * Track the last post_id (Lesson, Topic, Quiz) seen by user.
	 *
	 * @since 2.1.0
	 */
	if ( in_array( $post->post_type, learndash_get_post_types( 'course_steps' ), true ) ) {
		$course_progress['last_id'] = $post->ID;
	}

	$course_completed_time = time();

	$course_earliest_completed_time = learndash_activity_course_get_earliest_started( $current_user->ID, $course_id, $course_completed_time );

	// If course is completed.
	if ( ( $course_progress['completed'] >= $completed_old ) && ( $course_progress['completed'] >= $course_progress['total'] ) ) {

		/**
		 * Fires before the course is marked completed.
		 *
		 * @since 2.1.0
		 *
		 * @param array $course_data An array of course complete data.
		 */
		do_action(
			'learndash_before_course_completed',
			array(
				'user'           => $current_user,
				'course'         => get_post( $course_id ),
				'progress'       => array( $course_id => $course_progress ),
				'completed_time' => $course_completed_time,
			)
		);
		add_user_meta( $current_user->ID, 'course_completed_' . $course_id, $course_completed_time, true );
	} else {
		delete_user_meta( $current_user->ID, 'course_completed_' . $course_id );
	}

	learndash_user_set_course_progress( $user_id, $course_id, $course_progress );

	if ( ! empty( $topic_completed ) ) {
		$course_activity = learndash_activity_start_course( $current_user->ID, $course_id, $course_earliest_completed_time );
		if ( $course_activity ) {
			learndash_activity_update_meta_set(
				$course_activity->activity_id,
				array(
					'steps_completed' => learndash_course_get_completed_steps( $current_user->ID, $course_id ),
					'steps_last_id'   => $post->ID,
				)
			);
		}
		learndash_activity_start_lesson( $current_user->ID, $course_id, $lesson_id, $course_completed_time );
		learndash_activity_complete_topic( $current_user->ID, $course_id, $post->ID, $course_completed_time );
	}

	if ( ! empty( $lesson_completed ) ) {
		$course_activity = learndash_activity_start_course( $current_user->ID, $course_id, $course_earliest_completed_time );
		if ( $course_activity ) {
			learndash_activity_update_meta_set(
				$course_activity->activity_id,
				array(
					'steps_completed' => learndash_course_get_completed_steps( $current_user->ID, $course_id ),
					'steps_last_id'   => $post->ID,
				)
			);
		}

		learndash_activity_complete_lesson( $current_user->ID, $course_id, $post->ID, $course_completed_time );
	}

	$course_args = array(
		'course_id'     => $course_id,
		'user_id'       => $current_user->ID,
		'post_id'       => $course_id,
		'activity_type' => 'course',
	);

	$course_activity = learndash_get_user_activity( $course_args );
	if ( ! empty( $course_activity ) ) {
		$course_activity = json_decode( wp_json_encode( $course_activity ), true );
	} else {
		$course_activity = $course_args;
	}

	if ( in_array( $post->post_type, learndash_get_post_types( 'course_steps' ), true ) ) {
		$course_activity['activity_meta'] = array(
			'steps_last_id' => $post->ID,
		);
	}

	$do_course_complete_action = false;
	if ( $course_progress['completed'] >= $completed_old && $course_progress['total'] == $course_progress['completed'] ) {
		if ( ! $course_activity['activity_status'] ) {
			$course_activity['activity_status']    = true;
			$course_activity['activity_completed'] = $course_completed_time;
			$course_activity['activity_updated']   = $course_completed_time;

			if ( empty( $course_activity['activity_started'] ) ) {
				$course_activity['activity_started'] = $course_earliest_completed_time;
			}

			$do_course_complete_action = true;
		}
	} else {
		$course_activity['activity_completed'] = 0;
		$course_activity['activity_status']    = false;
		$course_activity['activity_updated']   = $course_completed_time;
	}
	learndash_update_user_activity( $course_activity );

	$return = false;
	if ( ! empty( $lesson_completed ) ) {

		/**
		 * Fires after the lesson is marked completed.
		 *
		 * @since 2.1.0
		 *
		 * @param array $lesson_data An array of lesson complete data.
		 */
		do_action(
			'learndash_lesson_completed',
			array(
				'user'     => $current_user,
				'course'   => get_post( $course_id ),
				'lesson'   => $post,
				'progress' => $course_progress,
			)
		);

		$return = true;
	}

	if ( ! empty( $topic_completed ) ) {

		/**
		 * Fires after the topic is marked completed.
		 *
		 * @since 2.1.0
		 *
		 * @param array $topic_data An array of topic complete data.
		 */
		do_action(
			'learndash_topic_completed',
			array(
				'user'     => $current_user,
				'course'   => get_post( $course_id ),
				'lesson'   => get_post( $lesson_id ),
				'topic'    => $post,
				'progress' => $course_progress,
			)
		);

		$return = true;
	}

	if ( true == $do_course_complete_action ) {

		/**
		 * Fires after the course is marked completed.
		 *
		 * @since 2.1.0
		 *
		 * @param array $course_data An array of course complete data.
		 */
		do_action(
			'learndash_course_completed',
			array(
				'user'             => $current_user,
				'course'           => get_post( $course_id ),
				'progress'         => array( $course_id => $course_progress ),
				'course_completed' => $course_completed_time,
			)
		);

		$return = true;
	}

	/**
	 * LEARNDASH-5883 - Always return true if we've made it this far.
	 */
	$return = true;

	return $return;

}

/**
 * Marks a resource complete.
 *
 * @todo  seems redundant, function already exists
 *
 * @since 2.1.0
 *
 * @param int $user_id Optional. User ID. Default null.
 * @param int $postid  Optional. The ID of the resource. Default null.
 */
function learndash_update_completion( $user_id = null, $postid = null ) {
	if ( empty( $postid ) ) {
		global $post;
		if ( ( $post ) && ( is_a( $post, 'WP_Post' ) ) ) {
			$postid = $post->ID;
		}
	}

	if ( ! empty( $postid ) ) {
		learndash_process_mark_complete( $user_id, $postid, true );
	}
}

/**
 * Checks whether a quiz is complete for a user.
 *
 * @since 2.1.0
 *
 * @param int|null $user_id   Optional. User ID. Default null.
 * @param int      $quiz_id   Quiz ID.
 * @param int      $course_id Optional. Course ID. Default 0.
 *
 * @return boolean Returns true if the quiz is completed for a user otherwise false.
 */
function learndash_is_quiz_complete( $user_id = null, $quiz_id = 0, $course_id = 0 ) {
	return ! learndash_is_quiz_notcomplete( $user_id, array( $quiz_id => 1 ), false, $course_id );
}

/**
 * Checks whether a quiz is not completed for a user.
 *
 * Checks against quizzes in user meta and passing percentage of the quiz itself
 *
 * @since 2.1.0
 * @since 2.3.1 Added $return_incomplete_quiz_ids parameter.
 *
 * @param int|null   $user_id                    Optional. User ID for quizzes. Default null.
 * @param array|null $quizzes                    Optional. Quiz ID to search user quizzes. Default null.
 * @param boolean    $return_incomplete_quiz_ids Optional. If true will return the array of incomplete quizzes. Default is false. Default false.
 * @param int        $course_id                  Optional. The Course ID to match. If -1 is passed then course match is not performed. Default 0.
 *
 * @return bool     Returns true if the quiz(es) NOT complete otherwise false.
 */
function learndash_is_quiz_notcomplete( $user_id = null, $quizzes = null, $return_incomplete_quiz_ids = false, $course_id = 0 ) {
	$user_id   = (int) $user_id;
	$course_id = (int) $course_id;

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			return true;
		}
	}

	if ( ( is_null( $quizzes ) ) || ( ! is_array( $quizzes ) ) || ( empty( $quizzes ) ) ) {
		return;
	}

	$quiz_results = get_user_meta( $user_id, '_sfwd-quizzes', true );

	if ( ( ! empty( $quiz_results ) ) && ( is_array( $quiz_results ) ) ) {
		foreach ( $quiz_results as $quiz ) {
			if ( ( ! isset( $quiz['quiz'] ) ) || ( empty( $quiz['quiz'] ) ) ) {
				continue;
			}

			if ( ! isset( $quizzes[ $quiz['quiz'] ] ) ) {
				continue;
			}

			// Because we don't want to alter the original $course_id function param.
			$quiz_course_id = $course_id;

			if ( ( -1 !== $quiz_course_id ) && ( empty( $quiz_course_id ) ) ) {
				$quiz_course_id = (int) learndash_get_course_id( intval( $quiz['quiz'] ) );
			}

			if ( ! isset( $quiz['course'] ) ) {
				if ( ! learndash_is_course_shared_steps_enabled() ) {
					/**
					 * If shared steps is not enabled we can determine the related
					 * course from the quiz post meta.
					 */
					$quiz['course'] = (int) learndash_get_setting( $quiz['quiz'], 'course' );
				} else {
					$quiz['course'] = learndash_get_course_id( $quiz['quiz'] );
				}
			}
			$quiz['course'] = intval( $quiz['course'] );

			$pass = false;

			if ( ( -1 === $course_id ) || ( $course_id === $quiz['course'] ) ) {
				if ( isset( $quiz['pass'] ) ) {
					$pass = ( 1 == $quiz['pass'] ) ? 1 : 0;
				} else {
					$passingpercentage = (int) learndash_get_setting( $quiz['quiz'], 'passingpercentage' );
					$pass              = ( ! empty( $quiz['count'] ) && $quiz['score'] * 100 / $quiz['count'] >= $passingpercentage ) ? 1 : 0;
				}
			}

			if ( $pass ) {
				unset( $quizzes[ $quiz['quiz'] ] );
			}

			// Break if empty. No need to loop through the rest of user quiz progress.
			if ( empty( $quizzes ) ) {
				break;
			}
		}
	}

	if ( empty( $quizzes ) ) {
		return 0;
	} else {
		if ( true == $return_incomplete_quiz_ids ) {
			return $quizzes;
		} else {
			return 1;
		}
	}
}

/**
 * Gets the user's current course progress.
 *
 * @since 2.1.0
 *
 * @param int|null $user_id   Optional. User ID. Default null.
 * @param int|null $postid    Optional. Post ID. Default null.
 * @param int|null $course_id Optional. Course ID. Default null.
 *
 * @return array An array of user's current course progress.
 */
function learndash_get_course_progress( $user_id = null, $postid = null, $course_id = null ) {
	if ( ( defined( 'LEARNDASH_COURSE_FUNCTIONS_LEGACY' ) ) && ( true === LEARNDASH_COURSE_FUNCTIONS_LEGACY ) ) {
		return learndash_get_course_progress_legacy( $user_id, $postid, $course_id );
	}

	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );

	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();

		if ( empty( $current_user->ID ) ) {
			return null;
		}

		$user_id = $current_user->ID;
	}

	$posts = array();

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id( $postid );
	}

	$this_post = get_post( $postid );
	if ( ( ! $this_post ) || ( ! is_a( $this_post, 'WP_Post' ) ) ) {
		return null;
	}

	$ld_course_object = LDLMS_Factory_Post::course( intval( $course_id ) );
	if ( ! $ld_course_object ) {
		return null;
	}

	$course_progress = learndash_user_get_course_progress( $user_id, $course_id, 'legacy' );

	if ( empty( $course_progress ) ) {
		$course_progress = array();
	}

	if ( 'sfwd-lessons' === $this_post->post_type ) {
		$posts = $ld_course_object->get_lessons( array( 'nopaging' => true ) );

		if ( empty( $course_progress ) || empty( $course_progress['lessons'] ) ) {
			$completed_posts = array();
		} else {
			$completed_posts = $course_progress['lessons'];
		}
	} elseif ( 'sfwd-topic' === $this_post->post_type ) {
		$lesson_id = learndash_course_get_single_parent_step( $course_id, $this_post->ID );
		if ( ! $lesson_id ) {
			return null;
		}

		$posts = $ld_course_object->get_topics( $lesson_id, array( 'nopaging' => true ) );

		if ( empty( $course_progress ) || empty( $course_progress['topics'][ $lesson_id ] ) ) {
			$completed_posts = array();
		} else {
			$completed_posts = $course_progress['topics'][ $lesson_id ];
		}
	}

	$temp   = '';
	$prev_p = '';
	$next_p = '';
	$this_p = '';

	if ( ! empty( $posts ) ) {
		foreach ( $posts as $k => $post ) {

			if ( $post instanceof WP_Post ) {

				if ( ! empty( $completed_posts[ $post->ID ] ) ) {
					$posts[ $k ]->completed = 1;
				} else {
					$posts[ $k ]->completed = 0;
				}

				if ( $post->ID == $postid ) {
					$this_p = $post;
					$prev_p = $temp;
				}

				if ( ! empty( $temp->ID ) && $temp->ID == $postid ) {
					$next_p = $post;
				}

				$temp = $post;
			}
		}
	} else {
		$posts = array();
	}

	return array(
		'posts' => $posts,
		'this'  => $this_p,
		'prev'  => $prev_p,
		'next'  => $next_p,
	);
}

/**
 * Checks if a lesson is complete.
 *
 * @since 2.1.0
 *
 * @param int|null $user_id   Optional. User ID. Defaults to the current logged-in user. Default null.
 * @param int      $lesson_id Lesson ID.
 * @param int      $course_id Optional. Course ID. Default 0.
 *
 * @return boolean Return true if the lesson is complete otherwise false.
 */
function learndash_is_lesson_complete( $user_id = null, $lesson_id = 0, $course_id = 0 ) {
	return ! learndash_is_lesson_notcomplete( $user_id, array( $lesson_id => 1 ), $course_id );
}

/**
 * Checks if a lesson is not complete.
 *
 * @since 2.1.0
 *
 * @param int|null $user_id   Optional. User ID. Defaults to the current logged-in user. Default null.
 * @param array    $lessons   An array of lesson IDs.
 * @param int      $course_id Optional. Course ID. Default 0.
 *
 * @return boolean Returns true if the lesson is not complete otherwise false.
 */
function learndash_is_lesson_notcomplete( $user_id = null, $lessons = array(), $course_id = 0 ) {
	if ( ( defined( 'LEARNDASH_COURSE_FUNCTIONS_LEGACY' ) ) && ( true === LEARNDASH_COURSE_FUNCTIONS_LEGACY ) ) {
		return learndash_is_lesson_notcomplete_legacy( $user_id, $lessons, $course_id );
	}

	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;
	}

	$course_id = absint( $course_id );

	if ( ! empty( $lessons ) ) {
		foreach ( $lessons as $lesson => $v ) {
			if ( empty( $course_id ) ) {
				$course_id = learndash_get_course_id( $lesson );
			}

			if ( ! empty( $course_id ) ) {
				$course_progress = learndash_user_get_course_progress( $user_id, $course_id, 'legacy' );

				if ( ( isset( $course_progress['lessons'][ $lesson ] ) ) && ( ! empty( $course_progress['lessons'][ $lesson ] ) ) ) {
					unset( $lessons[ $lesson ] );
				}
			}
		}
	}

	if ( empty( $lessons ) ) {
		return 0;
	} else {
		return 1;
	}
}

/**
 * Checks if a topic is complete.
 *
 * @since 2.3.1
 * @since 3.2.0 Added $course_id
 *
 * @param int $user_id  Optional. User ID. Defaults to the current logged-in user. Default null.
 * @param int $topic_id Topic ID.
 * @param int $course_id Course ID.
 *
 * @return boolean Returns true if the topic is completed otherwise false.
 */
function learndash_is_topic_complete( $user_id = null, $topic_id = 0, $course_id = 0 ) {
	return ! learndash_is_topic_notcomplete( $user_id, array( $topic_id => 1 ), $course_id );
}

/**
 * Checks if a topic is not complete.
 *
 * @since 2.3.1
 * @since 3.2.0 Added $course_id
 *
 * @param int|null $user_id Optional. User ID. Defaults to the current logged-in user. Default null.
 * @param array    $topics  An array of topic IDs.
 * @param int      $course_id Course ID.
 *
 * @return boolean Returns true if the topic is not completed otherwise false.
 */
function learndash_is_topic_notcomplete( $user_id = null, $topics = array(), $course_id = 0 ) {
	if ( ( defined( 'LEARNDASH_COURSE_FUNCTIONS_LEGACY' ) ) && ( true === LEARNDASH_COURSE_FUNCTIONS_LEGACY ) ) {
		return learndash_is_topic_notcomplete_legacy( $user_id, $topics, $course_id );
	}

	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;
	}
	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );

	if ( ! empty( $topics ) ) {
		foreach ( $topics as $topic_id => $v ) {
			if ( empty( $course_id ) ) {
				$course_id = learndash_get_course_id( $topic_id );
			}

			if ( ! empty( $course_id ) ) {

				$course_progress = learndash_user_get_course_progress( $user_id, $course_id, 'legacy' );

				$lesson_id = learndash_course_get_single_parent_step( $course_id, $topic_id );
				if ( ! empty( $lesson_id ) ) {

					if ( ( isset( $course_progress['topics'] ) )
						&& ( ! empty( $course_progress['topics'] ) )
						&& ( isset( $course_progress['topics'][ $lesson_id ][ $topic_id ] ) )
						&& ( ! empty( $course_progress['topics'][ $lesson_id ][ $topic_id ] ) ) ) {
						unset( $topics[ $topic_id ] );
					}
				}
			}
		}
	}

	if ( empty( $topics ) ) {
		return 0;
	} else {
		return 1;
	}
}

/**
 * Outputs the current status of the course.
 *
 * @since 2.1.0
 * @since 2.5.8 Added $return_slug parameter.
 *
 * @param int      $course_id   Course ID to get status.
 * @param int|null $user_id     Optional. User ID. Default null.
 * @param boolean  $return_slug Optional. If false will return translatable string otherwise the status slug. Default false.
 *
 * @return string The current status of the course.
 */
function learndash_course_status( $course_id, $user_id = null, $return_slug = false ) {
	global $learndash_course_statuses;

	if ( ( defined( 'LEARNDASH_COURSE_FUNCTIONS_LEGACY' ) ) && ( true === LEARNDASH_COURSE_FUNCTIONS_LEGACY ) ) {
		return learndash_course_status_legacy( $course_id, $user_id, $return_slug );
	}
	$course_status_slug = '';

	$course_id = absint( $course_id );
	$user_id   = absint( $user_id );

	if ( empty( $user_id ) ) {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}
	}

	if ( ( empty( $course_id ) ) || ( empty( $user_id ) ) ) {
		return $course_status_slug;
	}

	$course_progress = learndash_user_get_course_progress( $user_id, $course_id, 'legacy' );
	if ( isset( $course_progress['status'] ) ) {
		$course_status_slug = $course_progress['status'];
	}

	/**
	 * Sort of a kludge for now. If the course is not complete and the 'total' steps is
	 * not zero. Call the the mark complete function.
	 *
	 * This logic should be within User Course Progression class.
	 */
	if ( in_array( $course_status_slug, array( 'in_progress', 'not_started' ), true ) ) {
		if ( ( $course_progress['total'] > 0 ) && ( $course_progress['completed'] === $course_progress['total'] ) ) {
			if ( learndash_process_mark_complete( $user_id, $course_id ) ) {
				// If the Mark Complete was success we call to get the progress again.
				$course_progress = learndash_user_get_course_progress( $user_id, $course_id, 'legacy' );
				if ( isset( $course_progress['status'] ) ) {
					$course_status_slug = $course_progress['status'];
				}
			}
		}
	}

	if ( true === $return_slug ) {
		return $course_status_slug;
	} else {
		if ( isset( $learndash_course_statuses[ $course_status_slug ] ) ) {
			/**
			 * Filters the current status of the course.
			 *
			 * @param string $course_status_str The translatable current course status string.
			 * @param int    $course_id         Course ID.
			 * @param int    $user_id           User ID.
			 * @param array  $course_progress   Current course progress.
			 */
			return apply_filters(
				'learndash_course_status',
				learndash_course_status_label( $course_status_slug ),
				$course_id,
				$user_id,
				isset( $course_progress ) ? $course_progress : array()
			);
		}
	}

	return $course_status_slug;
}

/**
 * Gets the course status index from the course status label.
 *
 * In various places with LD the course status is expressed as a string as in 'Not Started', 'In Progress' or 'Complete'.
 * the problem with using this string is it will be translated depending on the locale(). This means comparative logic can
 * possible fails.
 * The purpose of this function is to help use an internal key to keep track of the course status value.
 *
 * @global array $learndash_course_statuses An array of course status.
 *
 * @since 2.3.0
 *
 * @param string $course_status_label Optional. The current translatable text for course status. Default empty.
 *
 * @return string The index/key of the course status string if found in the `$learndash_course_statuses` global array.
 */
function learndash_course_status_idx( $course_status_label = '' ) {
	global $learndash_course_statuses;

	return array_search( $course_status_label, $learndash_course_statuses, true );
}

/**
 * Get the Course Status label from slug.
 *
 * @since 3.4.0
 *
 * @param string $course_status_slug Course Status slug.
 *
 * @return string|null.
 */
function learndash_course_status_label( $course_status_slug = '' ) {
	global $learndash_course_statuses;

	if ( isset( $learndash_course_statuses[ $course_status_slug ] ) ) {
		return $learndash_course_statuses[ $course_status_slug ];
	}
}

/**
 * Checks if the quiz is accessible to the user.
 *
 * @since 3.4.0
 *
 * @param int|null     $user_id $user_id  Optional. The ID of User to check.  Defaults to the current logged-in user. Default null.
 * @param WP_Post|null $post              Optional. The `WP_Post` quiz object. Default null.
 * @param boolean      $return_incomplete Optional. Whether to return last incomplete step. Default false.
 * @param int          $course_id         Optional. Course ID. Default 0.
 *
 * @return int|WP_Post|void Returns 1 if the quiz is accessible by user otherwise 0. If the `$return_incomplete`
 *                          parameter is set to true it may return `WP_Post` object for incomplete step.
 */
function learndash_is_quiz_accessable( $user_id = null, $post = null, $return_incomplete = false, $course_id = 0 ) {

	// Allow using the legacy function in case of issues with new logic.
	if ( ( defined( 'LEARNDASH_IS_QUIZ_ACCESSABLE_LEGACY' ) && ( LEARNDASH_IS_QUIZ_ACCESSABLE_LEGACY === true ) ) ) {
		return learndash_is_quiz_accessable_legacy( $user_id, $post, $course_id );
	}

	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();

		if ( empty( $current_user->ID ) ) {
			return 1;
		}

		$user_id = $current_user->ID;
	}

	if ( ( empty( $post ) ) || ( ! is_a( $post, 'WP_Post' ) ) ) {
		return;
	}

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id( $post );
	}
	$course_id = absint( $course_id );

	// If we have a Quiz but the Quiz is not part of a course then return 1 for valid.
	if ( empty( $course_id ) ) {
		return 1;
	}

	$bypass_course_limits_admin_users = learndash_can_user_bypass( $user_id, 'learndash_quiz_accessable', $post, $course_id );
	if ( true === $bypass_course_limits_admin_users ) {
		return 1;
	}

	$course_progress_co = learndash_user_get_course_progress( $user_id, $course_id, 'co' );

	if ( learndash_is_course_builder_enabled() ) {
		$quiz_parent_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
	} else {
		$quiz_parent_id = learndash_get_setting( $post, 'lesson' );
	}
	$quiz_parent_id = absint( $quiz_parent_id );
	if ( ! empty( $quiz_parent_id ) ) {
		$quiz_parent_post = get_post( $quiz_parent_id );
		if ( is_a( $quiz_parent_post, 'WP_Post' ) ) {
			if ( learndash_get_post_type_slug( 'topic' ) === $quiz_parent_post->post_type ) {
				$quiz_parent_topic_post  = $quiz_parent_post;
				$quiz_parent_lesson_id   = learndash_get_setting( $quiz_parent_topic_post, 'lesson' );
				$quiz_parent_lesson_post = get_post( $quiz_parent_lesson_id );

				$parent_topic_quizzes = learndash_get_lesson_quiz_list( $quiz_parent_topic_post, $user_id, $course_id );
				if ( ! empty( $parent_topic_quizzes ) ) {
					// loop until we get to the first status == 'notcompleted'.
					foreach ( $parent_topic_quizzes as $quiz ) {
						if ( $quiz['post']->ID === $post->ID ) {
							break;
						} elseif ( 'completed' !== $quiz['status'] ) {
							if ( true === $return_incomplete ) {
								return $quiz['post'];
							} else {
								return 0;
							}
						}
					}
				}

				$lesson_topics_progress = learndash_get_course_progress( $user_id, $quiz_parent_topic_post->ID );
				if ( ( isset( $lesson_topics_progress['posts'] ) ) && ( ! empty( $lesson_topics_progress['posts'] ) ) ) {
					foreach ( $lesson_topics_progress['posts'] as $topic ) {
						if ( $topic->ID === $quiz_parent_topic_post->ID ) {
							if ( ! empty( $topic->completed ) ) {
								return 1;
							}
							break;
						}
						if ( empty( $topic->completed ) ) {
							if ( true === $return_incomplete ) {
								return $topic;
							} else {
								return 0;
							}
							break;
						}
					}
				}

				if ( 'on' === learndash_get_setting( $quiz_parent_topic_post->ID, 'lesson_video_enabled' ) ) {
					if ( ! empty( learndash_get_setting( $quiz_parent_topic_post->ID, 'lesson_video_url' ) ) ) {
						if ( 'BEFORE' === learndash_get_setting( $quiz_parent_topic_post->ID, 'lesson_video_shown' ) ) {
							if ( ! learndash_video_complete_for_step( $quiz_parent_topic_post->ID, $course_id, $user_id ) ) {
								if ( true === $return_incomplete ) {
									return $quiz_parent_topic_post;
								} else {
									return 0;
								}
							}
						}
					}
				}

				$lesson_progress = learndash_get_course_progress( $user_id, $quiz_parent_lesson_post->ID, $course_id );
				if ( ( isset( $lesson_progress['posts'] ) ) && ( ! empty( $lesson_progress['posts'] ) ) ) {
					foreach ( $lesson_progress['posts'] as $lesson ) {
						if ( $lesson->ID === $quiz_parent_lesson_post->ID ) {
							break;
						}
						if ( empty( $lesson->completed ) ) {
							if ( true === $return_incomplete ) {
								return $lesson;
							} else {
								return 0;
							}
							break;
						}
					}
				}

				return 1;

			} elseif ( learndash_get_post_type_slug( 'lesson' ) === $quiz_parent_post->post_type ) {
				$quiz_parent_lesson_post = $quiz_parent_post;
				$sibling_completed_steps = 0;

				$lesson_topics = learndash_get_topic_list( $quiz_parent_lesson_post->ID );
				if ( ! empty( $lesson_topics ) ) {
					$lesson_topics_progress = learndash_get_course_progress( $user_id, $lesson_topics[0]->ID );
					if ( ( isset( $lesson_topics_progress['posts'] ) ) && ( ! empty( $lesson_topics_progress['posts'] ) ) ) {
						foreach ( $lesson_topics_progress['posts'] as $topic ) {
							if ( empty( $topic->completed ) ) {
								if ( true === $return_incomplete ) {
									return $topic;
								} else {
									return 0;
								}
								break;
							} else {
								++$sibling_completed_steps;
							}
						}
					}
				}

				$quizzes = learndash_get_lesson_quiz_list( $quiz_parent_lesson_post, $user_id, $course_id );
				if ( ! empty( $quizzes ) ) {
					// loop until we get to the first status == 'notcompleted'.
					foreach ( $quizzes as $quiz ) {
						if ( $quiz['post']->ID === $post->ID ) {
							break;
						}

						if ( 'completed' !== $quiz['status'] ) {
							if ( true === $return_incomplete ) {
								return $quiz['post'];
							} else {
								return 0;
							}
						} else {
							++$sibling_completed_steps;
						}
					}
				}

				$lesson_progress = learndash_get_course_progress( $user_id, $quiz_parent_lesson_post->ID );
				if ( ( isset( $lesson_progress['posts'] ) ) && ( ! empty( $lesson_progress['posts'] ) ) ) {
					foreach ( $lesson_progress['posts'] as $lesson ) {
						if ( $lesson->ID === $quiz_parent_lesson_post->ID ) {
							if ( ! empty( $lesson->completed ) ) {
								return 1;
							}
							break;
						}
						if ( empty( $lesson->completed ) ) {
							if ( true === $return_incomplete ) {
								return $lesson;
							} else {
								return 0;
							}
							break;
						}
					}
				}

				if ( empty( $sibling_completed_steps ) ) {
					if ( 'on' === learndash_get_setting( $quiz_parent_lesson_post->ID, 'lesson_video_enabled' ) ) {
						if ( ! empty( learndash_get_setting( $quiz_parent_lesson_post->ID, 'lesson_video_url' ) ) ) {
							if ( 'BEFORE' === learndash_get_setting( $quiz_parent_lesson_post->ID, 'lesson_video_shown' ) ) {
								if ( ! learndash_video_complete_for_step( $quiz_parent_lesson_post->ID, $course_id, $user_id ) ) {
									if ( true === $return_incomplete ) {
										return $quiz_parent_lesson_post;
									} else {
										return 0;
									}
								}
							}
						}
					}
				}

				return 1;
			}
		}
	} else {
		// First we check if all course lessons are completed.
		$lessons = learndash_get_course_lessons_list( $course_id, $user_id, array( 'num' => 0 ) );
		if ( ! empty( $lessons ) ) {
			foreach ( $lessons as $lesson ) {
				if ( 'completed' !== $lesson['status'] ) {
					if ( true === $return_incomplete ) {
						return $lesson['post'];
					} else {
						return 0;
					}
				}
			}
		}

		// Next we check if other global quizzes are completed.
		$quizzes = learndash_get_global_quiz_list( $course_id );
		if ( ! empty( $quizzes ) ) {
			// loop until we get to the first status == 'notcompleted'.
			foreach ( $quizzes as $quiz ) {
				if ( $quiz->ID === $post->ID ) {
					return 1;
				} elseif ( ! learndash_is_quiz_complete( $user_id, $quiz->ID, $course_id ) ) {
					if ( true === $return_incomplete ) {
						return $quiz;
					} else {
						return 0;
					}
				}
			}
		}
	}
	return 0;
}

/**
 * Checks if all quizzes for a course are complete for the user.
 *
 * @since 3.4.0
 *
 * @param int|null $user_id Optional. User ID. Default null.
 * @param int|null $id      Optional. The ID of the resource. Default null.
 *
 * @return boolean
 */
function learndash_is_all_global_quizzes_complete( $user_id = null, $id = null ) {
	$quizzes = learndash_get_global_quiz_list( $id );
	$return  = true;

	if ( ! empty( $quizzes ) ) {
		foreach ( $quizzes as $quiz ) {
			if ( learndash_is_quiz_notcomplete( $user_id, array( $quiz->ID => 1 ), false, $id ) ) {
				$return = false;
			}
		}
	}

	return $return;
}

/**
 * Gets the next quiz for a course.
 *
 * @since 2.1.0
 *
 * @param  boolean  $url     Optional. Whether to return URL for the next quiz. Default true.
 * @param  int|null $user_id Optional. User ID.  Defaults to the current logged-in user. Default null.
 * @param  int|null $id      Optional. The ID of the resource. Default null.
 * @param  array    $exclude Optional. An array of quiz IDs to exclude. Default empty array.
 *
 * @return int|string The ID or the URL of the quiz.
 */
function learndash_next_global_quiz( $url = true, $user_id = null, $id = null, $exclude = array() ) {
	if ( empty( $id ) ) {
		$id = learndash_get_course_id();
	}

	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;
	}

	$quizzes = learndash_get_global_quiz_list( $id );
	$return  = get_permalink( $id );

	if ( ! empty( $quizzes ) ) {
		foreach ( $quizzes as $quiz ) {
			if ( ! in_array( $quiz->ID, $exclude, true ) && learndash_is_quiz_notcomplete( $user_id, array( $quiz->ID => 1 ), false, $id ) && learndash_can_attempt_again( $user_id, $quiz->ID ) ) {
				if ( $url ) {
					return get_permalink( $quiz->ID );
				} else {
					return $quiz->ID;
				}
			}
		}
	}

	/**
	 * Filters ID or URL of the next quiz of the course.
	 *
	 * @todo  filter name does not seem correct
	 *        in context of function
	 *
	 * @since 2.1.0
	 *
	 * @param int|string $next_quiz ID or URL of next quiz of the course.
	 * @param int        $course_id Course ID.
	 */
	$return = apply_filters( 'learndash_course_completion_url', $return, $id );
	return $return;
}

/**
 * Gets the next quiz for current lesson for a user.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 2.1.0
 *
 * @param  boolean  $url       Optional. Whether to return URL for the next quiz. Default true.
 * @param  int|null $user_id   Optional. User ID.  Defaults to the current logged-in user. Default null.
 * @param  int|null $lesson_id Optional. Lesson ID. Default null.
 * @param  array    $exclude   Optional. An array of quiz IDs to exclude. Default empty array.
 *
 * @return int|string The ID or the URL of the quiz.
 */
function learndash_next_lesson_quiz( $url = true, $user_id = null, $lesson_id = null, $exclude = array() ) {
	global $post;

	$return = false;

	if ( empty( $lesson_id ) ) {
		$lesson_id = $post->ID;
	}

	if ( empty( $exclude ) ) {
		$exclude = array();
	}

	if ( empty( $user_id ) ) {
		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;
	}

	$course_id = learndash_get_course_id();

	// Assumption here is the learndash_get_lesson_quiz_list returns the quizzes in the order they should be taken.
	$quizzes = learndash_get_lesson_quiz_list( $lesson_id, $user_id );
	if ( ( ! empty( $quizzes ) ) && ( is_array( $quizzes ) ) ) {
		foreach ( $quizzes as $quiz ) {
			// The logic here is we need to check all the quizzes in this lesson. If all the quizzes are complete
			// (including the current one) then we set the parent (lesson) to complete.
			if ( 'completed' == $quiz['status'] ) {
				continue;
			}

			// If not complete AND the user CAN take the quiz again...
			if ( learndash_can_attempt_again( $user_id, $quiz['post']->ID ) ) {
				$return = ( $url ) ? get_permalink( $quiz['post']->ID ) : $quiz['post']->ID;
				break;
			}

			$return = ( $url ) ? get_permalink( $quiz['post']->ID ) : $quiz['post']->ID;
			break;
		}
	}

	if ( empty( $return ) ) {
		if ( ( learndash_lesson_progression_enabled( $course_id ) ) && ( ! learndash_can_complete_step( $user_id, $lesson_id, $course_id ) ) ) {
			$return = learndash_get_step_permalink( $lesson_id, $course_id );
		} elseif ( learndash_user_is_course_children_progress_complete( $user_id, $course_id, $lesson_id ) ) {
			learndash_process_mark_complete( $user_id, $lesson_id, false, $course_id );
		}
	}

	return $return;
}

/**
 * Check if the user can complete the current step.
 *
 * This is mostly used before auto-completing a parent step like a quiz
 * parent lesson.
 *
 * @since 3.2.3
 * @since 4.0.3 Added `$ignore_lesson_timer` parameter.
 *
 * @param int  $user_id             User ID.
 * @param int  $step_id             Course Step ID.
 * @param int  $course_id           Course ID.
 * @param bool $ignore_lesson_timer Whether to ignore the lesson timer. Default false. @since 4.0.3.
 *
 * @return bool True if can complete.
 */
function learndash_can_complete_step( $user_id = 0, $step_id = 0, $course_id = 0, $ignore_lesson_timer = false ) {
	$user_id = absint( $user_id );
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}
	$step_id   = absint( $step_id );
	$course_id = absint( $course_id );
	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id( $step_id );
	}

	if ( ( ! empty( $user_id ) ) && ( ! empty( $step_id ) ) && ( ! empty( $course_id ) ) ) {
		// If we have ANY previous incomplete steps then we can't complete this step.
		if ( ! learndash_lesson_progression_enabled( $course_id ) ) {
			$incomplete_child_steps = learndash_user_progression_get_incomplete_child_steps( $user_id, $course_id, $step_id );
			if ( empty( ! $incomplete_child_steps ) ) {
				return false;
			}
		} else {
			$incomplete_step_id = learndash_user_progress_get_previous_incomplete_step( $user_id, $course_id, $step_id );
			if ( ! empty( $incomplete_step_id ) && ( $incomplete_step_id !== $step_id ) ) {
				return false;
			}
		}

		if ( in_array( get_post_type( $step_id ), learndash_get_post_type_slug( array( 'lesson', 'topic' ) ), true ) ) {

			// Check the Lesson Timer...
			if ( false === $ignore_lesson_timer ) {
				$step_timer_time = learndash_forced_lesson_time( $step_id );
				if ( ! empty( $step_timer_time ) ) {
					$time_cookie_key = learndash_forced_lesson_time_cookie_key( $step_id );
					if ( ! empty( $time_cookie_key ) ) {

						/**
						 * Note this is not a 100% check. We are only checking if the cookie
						 * key exists and is zero. But this cookie could have been set from
						 * external.
						 */
						if ( ( ! isset( $_COOKIE[ 'learndash_timer_cookie_' . $time_cookie_key ] ) ) || ( '0' !== $_COOKIE[ 'learndash_timer_cookie_' . $time_cookie_key ] ) ) {
							return false;
						}
					}
				}
			}

			// Next check the Lesson Assignment.
			if ( 'on' === learndash_get_setting( $step_id, 'lesson_assignment_upload' ) ) {
				$assignments = learndash_get_user_assignments( $step_id, $user_id, $course_id );
				if ( empty( $assignments ) ) {
					return false;
				} else {
					foreach ( $assignments as $assignment ) {
						if ( ! learndash_is_assignment_approved_by_meta( $assignment->ID ) ) {
							return false;
						}
					}
				}
			}

			// Next check if all child steps are completed.
			if ( ! learndash_user_is_course_children_progress_complete( $user_id, $course_id, $step_id ) ) {
				return false;
			}
		}

		return true;
	}

	return false;
}

/**
 * Checks if the resource has any quizzes.
 *
 * @since 3.4.0
 *
 * @param int|null $id Optional. The ID of the resource like course, lesson, topic, etc. Default null.
 *
 * @return boolean Returns true if the resource has quizzes otherwise false.
 */
function learndash_has_global_quizzes( $id = null ) {
	$quizzes = learndash_get_global_quiz_list( $id );
	return ! empty( $quizzes );
}

/**
 * Outputs the course progress HTML for the user.
 *
 * @todo consider for deprecation, not in use
 *
 * @since 2.1.0
 *
 * @param array $atts An array of course progress attributes.
 */
function learndash_course_progress_widget( $atts ) {
	echo learndash_course_progress( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
}

/**
 * Checks whether the lesson progression is enabled or not.
 *
 * @since 2.1.0
 *
 * @param int $course_id Optional. Course ID to check. Default 0.
 *
 * @return boolean Returns true if the lesson progression is enabled otherwise false.
 */
function learndash_lesson_progression_enabled( $course_id = 0 ) {
	$course_id = intval( $course_id );

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id();
	}

	if ( ! empty( $course_id ) ) {
		$setting = learndash_get_setting( $course_id, 'course_disable_lesson_progression' );

		/**
		 * Filter for Course Progression Enabled.
		 *
		 * @since 3.4.0
		 *
		 * @param bool $setting   true if course progression is enabled.
		 * @param int  $course_id Course ID.
		 */
		return apply_filters( 'learndash_course_progression_enabled', empty( $setting ), $course_id );
	}

	return false;
}

/**
 * Gets the lesson time for a lesson if it exists.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 2.1.0
 *
 * @param string|int|WP_Post $lesson_topic_post Optional. The `WP_Post` lesson topic post object or ID. Defaults to global post object. Default empty.
 *
 * @return int|string Returns lesson time if it exists otherwise 0.
 */
function learndash_forced_lesson_time( $lesson_topic_post = '' ) {

	if ( empty( $lesson_topic_post ) ) {
		global $post;
		$lesson_topic_post = $post;
	}

	if ( ! is_a( $lesson_topic_post, 'WP_Post' ) ) {
		$post_id = absint( $lesson_topic_post );
		if ( empty( $post_id ) ) {
			return 0;
		}
		$lesson_topic_post = get_post( $post_id );
		if ( ( ! $lesson_topic_post ) || ( ! is_a( $lesson_topic_post, 'WP_Post' ) ) ) {
			return 0;
		}
	}

	if ( ! in_array( $lesson_topic_post->post_type, array( learndash_get_post_type_slug( 'lesson' ), learndash_get_post_type_slug( 'topic' ) ), true ) ) {
		return 0;
	}

	$meta = get_post_meta( $lesson_topic_post->ID, '_' . $lesson_topic_post->post_type, true );
	if ( ! is_array( $meta ) ) {
		$meta = array();
	}
	if ( ! isset( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time_enabled' ] ) ) {
		if ( ( isset( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ] ) ) && ( ! empty( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ] ) ) ) {
			$meta[ $lesson_topic_post->post_type . '_forced_lesson_time_enabled' ] = 'on';
		} else {
			$meta[ $lesson_topic_post->post_type . '_forced_lesson_time_enabled' ] = '';
		}
	}

	if ( 'on' === $meta[ $lesson_topic_post->post_type . '_forced_lesson_time_enabled' ] ) {
		if ( ( isset( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ] ) ) && ( ! empty( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ] ) ) ) {
			return $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ];
		}
	}

	return 0;
}

/**
 * Gets the lesson time cookie key for lesson/topic.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 3.0.0
 *
 * @param string|int|WP_Post $lesson_topic_post Optional. The `WP_Post` lesson topic post object or ID. Defaults to global post object.
 *
 * @return string The cookie key value or empty string.
 */
function learndash_forced_lesson_time_cookie_key( $lesson_topic_post = '' ) {

	if ( empty( $lesson_topic_post ) ) {
		global $post;
		$lesson_topic_post = $post;
	}

	if ( ! is_a( $lesson_topic_post, 'WP_Post' ) ) {
		$post_id = absint( $lesson_topic_post );
		if ( empty( $post_id ) ) {
			return 0;
		}
		$lesson_topic_post = get_post( $post_id );
		if ( ( ! $lesson_topic_post ) || ( ! is_a( $lesson_topic_post, 'WP_Post' ) ) ) {
			return 0;
		}
	}

	if ( ! in_array( $lesson_topic_post->post_type, array( learndash_get_post_type_slug( 'lesson' ), learndash_get_post_type_slug( 'topic' ) ), true ) ) {
		return 0;
	}

	$meta = get_post_meta( $lesson_topic_post->ID, '_' . $lesson_topic_post->post_type, true );
	if ( ! is_array( $meta ) ) {
		$meta = array();
	}
	if ( ! isset( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time_enabled' ] ) ) {
		if ( ( isset( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ] ) ) && ( ! empty( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ] ) ) ) {
			$meta[ $lesson_topic_post->post_type . '_forced_lesson_time_enabled' ] = 'on';
		} else {
			$meta[ $lesson_topic_post->post_type . '_forced_lesson_time_enabled' ] = '';
		}
	}

	if ( 'on' === $meta[ $lesson_topic_post->post_type . '_forced_lesson_time_enabled' ] ) {
		if ( ( isset( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ] ) ) && ( ! empty( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time' ] ) ) ) {
			$cookie_key = get_current_user_id() . '_' . learndash_get_course_id( $lesson_topic_post ) . '_' . $lesson_topic_post->ID;

			if ( ( isset( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time_cookie_key' ] ) ) && ( ! empty( $meta[ $lesson_topic_post->post_type . '_forced_lesson_time_cookie_key' ] ) ) ) {
				$cookie_key .= '_' . $meta[ $lesson_topic_post->post_type . '_forced_lesson_time_cookie_key' ];
			}
			return $cookie_key;
		}
	}

	return '';
}

/**
 * Checks if a course is completed for a user.
 *
 * @since 2.1.0
 *
 * @param int $user_id   User ID.
 * @param int $course_id Course ID.
 *
 * @return boolean Returns true if the course is completed otherwise false.
 */
function learndash_course_completed( $user_id, $course_id ) {
	if ( learndash_course_status( $course_id, $user_id ) == esc_html__( 'Completed', 'learndash' ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Adds the course completion date to user meta.
 *
 * @since 2.1.0
 *
 * @param array $data An array of course completion data.
 */
function learndash_course_completed_store_time( $data ) {
	$user_id    = $data['user']->ID;
	$course_id  = $data['course']->ID;
	$meta_key   = 'course_completed_' . $course_id;
	$meta_value = time();

	$course_completed = get_user_meta( $user_id, $meta_key );

	if ( empty( $course_completed ) ) {
		update_user_meta( $user_id, $meta_key, $meta_value );
	}
}
add_action( 'learndash_before_course_completed', 'learndash_course_completed_store_time', 10, 1 );

/**
 * Deletes the course progress for a user.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.1.0
 *
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
function learndash_delete_course_progress( $course_id, $user_id ) {
	global $wpdb;
	$usermeta = get_user_meta( $user_id, '_sfwd-course_progress', true );

	if ( isset( $usermeta[ $course_id ] ) ) {
		unset( $usermeta[ $course_id ] );
		update_user_meta( $user_id, '_sfwd-course_progress', $usermeta );
	}

	delete_user_meta( $user_id, 'course_completed_' . $course_id );

	// The reason we don't use the methods above is we want to ensure all old data is removed
	// from the quiz attempt history not just for quizzes currently associated with the course.
	$quizzes          = array();
	$usermeta_quizzes = get_user_meta( $user_id, '_sfwd-quizzes', true );
	if ( ! is_array( $usermeta_quizzes ) ) {
		$usermeta_quizzes = array();
	}
	if ( ! empty( $usermeta_quizzes ) ) {
		foreach ( $usermeta_quizzes as $quiz_item ) {
			if ( ( isset( $quiz_item['course'] ) ) && ( intval( $course_id ) == intval( $quiz_item['course'] ) ) ) {
				if ( isset( $quiz_item['quiz'] ) ) {
					$quiz_id             = intval( $quiz_item['quiz'] );
					$quizzes[ $quiz_id ] = $quiz_id;
				}
			}
		}
	}

	if ( ! empty( $quizzes ) ) {
		foreach ( $quizzes as $quiz_id ) {
			learndash_delete_quiz_progress( $user_id, $quiz_id );
		}
	}
}

/**
 * Deletes the quiz progress for a user.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.1.0
 *
 * @param int $user_id User ID.
 * @param int $quiz_id Quiz ID.
 */
function learndash_delete_quiz_progress( $user_id, $quiz_id ) {
	global $wpdb;

	// Clear User Meta.
	$usermeta = get_user_meta( $user_id, '_sfwd-quizzes', true );

	if ( ! empty( $usermeta ) && is_array( $usermeta ) ) {
		$usermeta_new = array();
		foreach ( $usermeta as $key => $quizmeta ) {
			if ( $quizmeta['quiz'] != $quiz_id ) {
				$usermeta_new[] = $quizmeta;
			}
		}
		update_user_meta( $user_id, '_sfwd-quizzes', $usermeta_new );
	}

	// ProQuiz Data.
	$pro_quiz_id = learndash_get_setting( $quiz_id, 'quiz_pro' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$ref_ids = $wpdb->get_col(
		$wpdb->prepare( 'SELECT statistic_ref_id FROM ' . esc_sql( LDLMS_DB::get_table_name( 'quiz_statistic_ref' ) ) . ' WHERE  user_id = %d AND quiz_id = %d ', $user_id, $pro_quiz_id )
	);

	if ( ! empty( $ref_ids[0] ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			LDLMS_DB::get_table_name( 'quiz_statistic_ref' ),
			array(
				'user_id' => $user_id,
				'quiz_id' => $pro_quiz_id,
			)
		);

		$ref_ids = array_map( 'absint', $ref_ids );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared -- IN clause
			$wpdb->prepare( 'DELETE FROM ' . esc_sql( LDLMS_DB::get_table_name( 'quiz_statistic' ) ) . ' WHERE statistic_ref_id IN (' . LDLMS_DB::escape_IN_clause_placeholders( $ref_ids ) . ')', LDLMS_DB::escape_IN_clause_values( $ref_ids ) )
		);
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->delete(
		LDLMS_DB::get_table_name( 'quiz_toplist' ),
		array(
			'user_id' => $user_id,
			'quiz_id' => $pro_quiz_id,
		)
	);
}

/**
 * Removes the user quiz statistics by the reference ID.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.5.0
 *
 * @param int $ref_id Optional. Quiz statistic reference ID. Default 0.
 */
function learndash_quiz_remove_user_statistics_by_ref( $ref_id = 0 ) {
	global $wpdb;

	if ( ! empty( $ref_id ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( LDLMS_DB::get_table_name( 'quiz_statistic_ref' ), array( 'statistic_ref_id' => $ref_id ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare( 'DELETE FROM ' . esc_sql( LDLMS_DB::get_table_name( 'quiz_statistic' ) ) . ' WHERE statistic_ref_id = %d', $ref_id )
		);
	}
}

/**
 * Removes the quiz user toplist.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 3.1.0
 *
 * @param int $user_id    Optional. User ID. Default 0.
 * @param int $quiz_time  Optional. Quiz time. Default 0.
 * @param int $pro_quizid Optional. Pro quiz ID. Default 0.
 *
 * @return void|int|false The number of rows updated, or false on error.
 */
function learndash_quiz_remove_user_toplist( $user_id = 0, $quiz_time = 0, $pro_quizid = 0 ) {
	global $wpdb;

	if ( ( ! empty( $user_id ) ) && ( ! empty( $quiz_time ) ) && ( ! empty( $pro_quizid ) ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->delete(
			LDLMS_DB::get_table_name( 'quiz_toplist' ),
			array(
				'user_id' => $user_id,
				'date'    => $quiz_time,
				'quiz_id' => $pro_quizid,
			),
			array( '%d', '%d', '%d' )
		);
	}
}

/**
 * Marks a course step incomplete for a course.
 *
 * Used to set a course step ( lesson or topic only ) back to not complete status.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 2.5.0
 *
 * @param int     $user_id       Optional. User ID. Default 0.
 * @param int     $course_id     Optional. Course ID. Default 0.
 * @param int     $step_id       Optional. Step ID. Default 0.
 * @param boolean $step_complete Optional. Unused. Default false.
 *
 * @return int|boolean Returns true if the update is successful otherwise false or meta ID
 *                     if the meta does not exist.
 */
function learndash_process_mark_incomplete( $user_id = 0, $course_id = 0, $step_id = 0, $step_complete = false ) {
	$user_id = absint( $user_id );
	if ( empty( $user_id ) ) {
		return;
	}

	$course_id = absint( $course_id );
	if ( empty( $course_id ) ) {
		return;
	}

	$step_id = absint( $step_id );
	if ( empty( $step_id ) ) {
		global $post;
		if ( ( isset( $post ) ) && ( $post instanceof WP_Post ) && ( ( in_array( $post->post_type, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ), true ) ) ) ) {
			$step_id = $post->ID;
		} else {
			return;
		}
	}

	$subtracted_completed_steps = 0;

	$course_step_parents = learndash_course_get_all_parent_step_ids( $course_id, $step_id );

	$user_course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );
	if ( ! isset( $user_course_progress[ $course_id ] ) ) {
		return;
	}

	$step_post_type = get_post_type( $step_id );
	if ( 'sfwd-quiz' === $step_post_type ) {
		if ( ! empty( $course_step_parents ) ) {
			if ( ( 2 == count( $course_step_parents ) ) && ( 'sfwd-lessons' == get_post_type( $course_step_parents[0] ) ) && ( 'sfwd-topic' == get_post_type( $course_step_parents[1] ) ) ) {
				$lesson_id = $course_step_parents[0];
				$topic_id  = $course_step_parents[1];

				if ( ( isset( $user_course_progress[ $course_id ]['topics'][ $lesson_id ][ $topic_id ] ) ) && ( true == $user_course_progress[ $course_id ]['topics'][ $lesson_id ][ $topic_id ] ) ) {

					$user_course_progress[ $course_id ]['topics'][ $lesson_id ][ $topic_id ] = 0;
					$user_course_progress[ $course_id ]['completed']                        -= 1;

					/**
					 * Fires on marking a course incomplete.
					 *
					 * @param int $user_id   User ID.
					 * @param int $course_id Course ID.
					 * @param int $topic_id  Topic ID.
					 */
					do_action( 'learndash_mark_incomplete_process', $user_id, $course_id, $topic_id );

					$topic_args     = array(
						'course_id'     => $course_id,
						'user_id'       => $user_id,
						'post_id'       => $topic_id,
						'activity_type' => 'topic',
					);
					$topic_activity = learndash_get_user_activity( $topic_args );
					if ( $topic_activity ) {
						$topic_activity = (array) $topic_activity;

						$topic_activity['activity_status']    = false;
						$topic_activity['activity_completed'] = false;
						learndash_update_user_activity( $topic_activity );
					}
				}

				if ( ( isset( $user_course_progress[ $course_id ]['lessons'][ $lesson_id ] ) ) && ( true == $user_course_progress[ $course_id ]['lessons'][ $lesson_id ] ) ) {
					$user_course_progress[ $course_id ]['lessons'][ $lesson_id ] = 0;
					$user_course_progress[ $course_id ]['completed']            -= 1;

					/** This filter is documented in includes/course/ld-course-progress.php */
					do_action( 'learndash_mark_incomplete_process', $user_id, $course_id, $lesson_id );

					$lesson_args     = array(
						'course_id'     => $course_id,
						'user_id'       => $user_id,
						'post_id'       => $lesson_id,
						'activity_type' => 'lesson',
					);
					$lesson_activity = learndash_get_user_activity( $lesson_args );
					if ( $lesson_args ) {
						$lesson_args                       = (array) $lesson_args;
						$lesson_args['activity_status']    = false;
						$lesson_args['activity_completed'] = false;
						learndash_update_user_activity( $lesson_args );
					}
				}
			} elseif ( ( 1 == count( $course_step_parents ) ) && ( 'sfwd-lessons' == get_post_type( $course_step_parents[0] ) ) ) {
				$lesson_id = $course_step_parents[0];

				if ( ( isset( $user_course_progress[ $course_id ]['lessons'][ $lesson_id ] ) ) && ( true == $user_course_progress[ $course_id ]['lessons'][ $lesson_id ] ) ) {
					$user_course_progress[ $course_id ]['lessons'][ $lesson_id ] = 0;
					$user_course_progress[ $course_id ]['completed']            -= 1;

					/** This filter is documented in includes/course/ld-course-progress.php */
					do_action( 'learndash_mark_incomplete_process', $user_id, $course_id, $lesson_id );

					$lesson_args     = array(
						'course_id'     => $course_id,
						'user_id'       => $user_id,
						'post_id'       => $lesson_id,
						'activity_type' => 'lesson',
					);
					$lesson_activity = learndash_get_user_activity( $lesson_args );
					if ( $lesson_args ) {
						$lesson_args                       = (array) $lesson_args;
						$lesson_args['activity_status']    = false;
						$lesson_args['activity_completed'] = false;
						learndash_update_user_activity( $lesson_args );
					}
				}
			}
		}
	} elseif ( 'sfwd-topic' === $step_post_type ) {
		$step_parent_id = learndash_course_get_single_parent_step( $course_id, $step_id );
		if ( ! empty( $step_parent_id ) ) {
			if ( ( isset( $user_course_progress[ $course_id ]['topics'][ $step_parent_id ][ $step_id ] ) ) && ( true == $user_course_progress[ $course_id ]['topics'][ $step_parent_id ][ $step_id ] ) ) {
				$user_course_progress[ $course_id ]['topics'][ $step_parent_id ][ $step_id ] = 0;
				$user_course_progress[ $course_id ]['completed']                            -= 1;

				/** This filter is documented in includes/course/ld-course-progress.php */
				do_action( 'learndash_mark_incomplete_process', $user_id, $course_id, $step_id );

				$topic_args     = array(
					'course_id'     => $course_id,
					'user_id'       => $user_id,
					'post_id'       => $step_id,
					'activity_type' => 'topic',
				);
				$topic_activity = learndash_get_user_activity( $topic_args );
				if ( $topic_activity ) {
					$topic_activity = (array) $topic_activity;

					$topic_activity['activity_status']    = false;
					$topic_activity['activity_completed'] = false;
					learndash_update_user_activity( $topic_activity );
				}
			}
			if ( ( isset( $user_course_progress[ $course_id ]['lessons'][ $step_parent_id ] ) ) && ( true == $user_course_progress[ $course_id ]['lessons'][ $step_parent_id ] ) ) {
				$user_course_progress[ $course_id ]['lessons'][ $step_parent_id ] = 0;
				$user_course_progress[ $course_id ]['completed']                 -= 1;

				/** This filter is documented in includes/course/ld-course-progress.php */
				do_action( 'learndash_mark_incomplete_process', $user_id, $course_id, $step_parent_id );

				$lesson_args     = array(
					'course_id'     => $course_id,
					'user_id'       => $user_id,
					'post_id'       => $step_parent_id,
					'activity_type' => 'lesson',
				);
				$lesson_activity = learndash_get_user_activity( $lesson_args );
				if ( $lesson_args ) {
					$lesson_args                       = (array) $lesson_args;
					$lesson_args['activity_status']    = false;
					$lesson_args['activity_completed'] = false;
					learndash_update_user_activity( $lesson_args );
				}
			}
		}
	} elseif ( 'sfwd-lessons' === $step_post_type ) {
		if ( ( isset( $user_course_progress[ $course_id ]['lessons'][ $step_id ] ) ) && ( true == $user_course_progress[ $course_id ]['lessons'][ $step_id ] ) ) {
			$user_course_progress[ $course_id ]['lessons'][ $step_id ] = 0;
			$user_course_progress[ $course_id ]['completed']          -= 1;

			/** This filter is documented in includes/course/ld-course-progress.php */
			do_action( 'learndash_mark_incomplete_process', $user_id, $course_id, $step_id );

			$lesson_args     = array(
				'course_id'     => $course_id,
				'user_id'       => $user_id,
				'post_id'       => $step_id,
				'activity_type' => 'lesson',
			);
			$lesson_activity = learndash_get_user_activity( $lesson_args );
			if ( $lesson_args ) {
				$lesson_args                       = (array) $lesson_args;
				$lesson_args['activity_status']    = false;
				$lesson_args['activity_completed'] = false;
				learndash_update_user_activity( $lesson_args );
			}
		}
	}

	if ( ! isset( $user_course_progress[ $course_id ] ) ) {
		return;
	}

	if ( isset( $user_course_progress[ $course_id ]['completed'] ) ) {
		$user_course_progress[ $course_id ]['completed'] = absint( $user_course_progress[ $course_id ]['completed'] );
	} else {
		$user_course_progress[ $course_id ]['completed'] = 0;
	}

	if ( isset( $user_course_progress[ $course_id ]['total'] ) ) {
		$user_course_progress[ $course_id ]['total'] = absint( $user_course_progress[ $course_id ]['total'] );
	} else {
		$user_course_progress[ $course_id ]['total'] = 0;
	}

	if ( $user_course_progress[ $course_id ]['completed'] !== $user_course_progress[ $course_id ]['total'] ) {
		delete_user_meta( $user_id, 'course_completed_' . $course_id );

		/** This filter is documented in includes/course/ld-course-progress.php */
		do_action( 'learndash_mark_incomplete_process', $user_id, $course_id, $course_id );
		$course_args     = array(
			'course_id'     => $course_id,
			'user_id'       => $user_id,
			'post_id'       => $course_id,
			'activity_type' => 'course',
		);
		$course_activity = learndash_get_user_activity( $course_args );
		if ( $course_args ) {
			$course_args                       = (array) $course_args;
			$course_args['activity_status']    = false;
			$course_args['activity_completed'] = false;
			learndash_update_user_activity( $course_args );
		}
	}

	return update_user_meta( $user_id, '_sfwd-course_progress', $user_course_progress );

}

/**
 * Gets the quiz attempt meta for a given user.
 *
 * @since 2.3.0
 *
 * @param int   $user_id Optional. User ID. Default 0.
 * @param array $args    Optional. An array of items to match. Default empty array.
 *
 * @return array An array of user quiz attempt meta.
 */
function learndash_get_user_quiz_attempt( $user_id = 0, $args = array() ) {
	if ( ( ! empty( $user_id ) ) && ( ! empty( $args ) ) ) {
		$user_quizzes = get_user_meta( $user_id, '_sfwd-quizzes', true );
		if ( ! empty( $user_quizzes ) ) {
			foreach ( $user_quizzes as $idx => $user_quiz ) {
				foreach ( $args as $arg_key => $arg_val ) {
					if ( ( ! isset( $user_quiz[ $arg_key ] ) ) || ( $user_quiz[ $arg_key ] != $arg_val ) ) {
						unset( $user_quizzes[ $idx ] );
					}
				}
			}
		}

		return $user_quizzes;
	}

	return array();
}

/**
 * Removes the quiz attempt meta for a given user.
 *
 * @since 2.5.0
 *
 * @param int   $user_id Optional. User ID. Default 0.
 * @param array $args    Optional. An array of items to match. Default empty array.
 *
 * @return array An array of updated quiz attempt meta.
 */
function learndash_remove_user_quiz_attempt( $user_id = 0, $args = array() ) {
	if ( ( ! empty( $user_id ) ) && ( ! empty( $args ) ) ) {

		$changes      = false;
		$user_quizzes = get_user_meta( $user_id, '_sfwd-quizzes', true );

		if ( ! empty( $user_quizzes ) ) {
			$changed_user_quizzes = array();

			foreach ( $user_quizzes as $idx => $user_quiz ) {
				$match_found = true;

				foreach ( $args as $arg_key => $arg_val ) {
					if ( ( ! isset( $user_quiz[ $arg_key ] ) ) || ( $user_quiz[ $arg_key ] != $arg_val ) ) {
						$match_found = false;
						break;
					}
				}

				if ( true === $match_found ) {

					/**
					 * Fires before user single quiz attempt has been removed.
					 *
					 * @since 3.2.3
					 *
					 * @param int   $user_id   User ID for the Quiz.
					 * @param array $user_quiz User meta quiz item to be deleted.
					 */
					do_action( 'learndash_user_quiz_delete_single_before', $user_id, $user_quiz );

					if ( ( isset( $user_quiz['time'] ) ) && ( ! empty( $user_quiz['time'] ) ) ) {
						if ( ( isset( $user_quiz['pro_quizid'] ) ) && ( ! empty( $user_quiz['pro_quizid'] ) ) ) {
							learndash_quiz_remove_user_toplist( $user_id, $user_quiz['time'], $user_quiz['pro_quizid'] );
						}
					}

					// If we have a statistics reference we need to remove this set of records.
					if ( ( isset( $user_quiz['statistic_ref_id'] ) ) && ( ! empty( $user_quiz['statistic_ref_id'] ) ) ) {
						learndash_quiz_remove_user_statistics_by_ref( $user_quiz['statistic_ref_id'] );
					}

					if ( ( ! isset( $user_quiz['course'] ) ) || ( empty( $user_quiz['course'] ) ) ) {
						$user_quiz['course'] = learndash_get_course_id( $user_quiz['quiz'] );
					}

					// If this quiz has graded items they all need to be moved to trash or deleted.
					if ( ( isset( $user_quiz['graded'] ) ) && ( ! empty( $user_quiz['graded'] ) ) ) {
						foreach ( $user_quiz['graded'] as $question_id => $graded_set ) {
							if ( ( isset( $graded_set['post_id'] ) ) && ( ! empty( $graded_set['post_id'] ) ) ) {
								wp_delete_post( $graded_set['post_id'], true );
							}
						}
					}

					// Remove the user activity record.
					$quiz_args     = array(
						'course_id'          => isset( $user_quiz['course'] ) ? absint( $user_quiz['course'] ) : 0,
						'user_id'            => $user_id,
						'post_id'            => $user_quiz['quiz'],
						'activity_type'      => 'quiz',
						'activity_completed' => isset( $user_quiz['completed'] ) ? absint( $user_quiz['completed'] ) : 0,
					);
					$quiz_activity = learndash_get_user_activity( $quiz_args );
					if ( ! empty( $quiz_activity ) ) {
						learndash_delete_user_activity( $quiz_activity->activity_id );
					}

					$changed_user_quizzes[] = $user_quiz;

					unset( $user_quizzes[ $idx ] );
					$changes = true;

					/**
					 * Fires after user single quiz attempt has been removed.
					 *
					 * @since 3.2.3
					 *
					 * @param int   $user_id   User ID for the Quiz.
					 * @param array $user_quiz User meta quiz item to be deleted.
					 */
					do_action( 'learndash_user_quiz_delete_single_after', $user_id, $user_quiz );
				}
			}

			if ( true === $changes ) {
				// If not empty then we reset the keys.
				if ( ! empty( $user_quizzes ) ) {
					$user_quizzes = array_values( $user_quizzes );
				}

				update_user_meta( $user_id, '_sfwd-quizzes', $user_quizzes );

				if ( ! empty( $changed_user_quizzes ) ) {

					/**
					 * Fires after user all quiz attempts have been removed.
					 *
					 * @since 3.2.3
					 *
					 * @param int   $user_id              User ID for the Quiz.
					 * @param array $changed_user_quizzes Array of all quiz items deleted.
					 */
					do_action( 'learndash_user_quiz_delete_all_after', $user_id, $changed_user_quizzes );

					foreach ( $changed_user_quizzes as $user_quiz ) {

						if ( ! learndash_is_quiz_complete( $user_id, $user_quiz['quiz'], $user_quiz['course'] ) ) {
							learndash_process_mark_incomplete( $user_id, $user_quiz['course'], $user_quiz['quiz'], false );
						}

						/**
						 * Legacy: Call the `learndash_process_mark_complete` function after the quiz entry is removed.
						 *
						 * The call to this function was removed as of LD 3.5.1 because it should not be needed. But
						 * retaining for legacy purposes.
						 *
						 * @since 3.5.1
						 *
						 * @param bool  $call_function Default is false.
						 * @param array $user_quiz     User meta quiz item to be deleted.
						 * @param int   $user_id       User ID for the Quiz.
						 */
						if ( true === apply_filters( 'learndash_quiz_call_mark_complete_after_remove', false, $user_quiz, $user_id ) ) {
							learndash_process_mark_complete( $user_id, $user_quiz['quiz'], false, $user_quiz['course'] );
						}
					}
				}
			}
		}

		return $user_quizzes;
	}

	return array();
}

/**
 * Outputs HTML output to mark a step incomplete.
 *
 * Must meet requirements of course to mark incomplete.
 *
 * @since 3.1.4
 *
 * @param WP_Post $post The `WP_Post` for lesson, topic.
 * @param array   $atts Optional. An array of attributes for mark incomplete output. Default empty array.
 *
 * @return string The HTML output to mark course incomplete.
 */
function learndash_show_mark_incomplete( $post, $atts = array() ) {
	$course_mark_incomplete_enabled = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Management_Display', 'course_mark_incomplete_enabled', false );
	if ( empty( $course_mark_incomplete_enabled ) ) {
		return '';
	}

	if ( ! is_user_logged_in() ) {
		return '';
	}

	$user_id   = get_current_user_id();
	$course_id = learndash_get_course_id( $post->ID );

	/**
	 * Filters whether to show mark course incomplete form.
	 *
	 * @param boolean $show_form Whether to show mark incomplete form.
	 * @param int     $course_id Course ID.
	 * @param WP_Post $post      `WP_Post` object being displayed.
	 * @param array   $atts      An array of attributes to mark a course incomplete.
	 */
	if ( ! apply_filters( 'learndash_show_mark_incomplete_form', true, $course_id, $post, $atts ) ) {
		return '';
	}

	/**
	 * Filters attributes to mark a course incomplete.
	 *
	 * @param array   $atts An array of attributes to mark a course incomplete.
	 * @param WP_Post $post `WP_Post` object being displayed.
	 */
	$atts = apply_filters( 'learndash_mark_incomplete_form_atts', $atts, $post );

	if ( isset( $atts['form']['id'] ) ) {
		$form_id = ' id="' . esc_attr( $atts['form']['id'] ) . '" ';
	} else {
		$form_id = '';
	}

	if ( isset( $atts['form']['class'] ) ) {
		$form_class = ' class="sfwd-mark-incomplete sfwd-mark-complete ' . esc_attr( $atts['form']['class'] ) . '" ';
	} else {
		$form_class = ' class="sfwd-mark-incomplete sfwd-mark-complete" ';
	}

	if ( isset( $atts['button']['id'] ) ) {
		$button_id = ' id="' . esc_attr( $atts['button']['id'] ) . '" ';
	} else {
		$button_id = '';
	}

	$button_disabled = '';
	if ( isset( $atts['button']['class'] ) ) {
		$button_class = ' class="learndash_mark_incomplete_button learndash_mark_complete_button ' . esc_attr( $atts['button']['class'] ) . '" ';
	} else {
		$button_class = ' class="learndash_mark_incomplete_button learndash_mark_complete_button" ';
	}

	$button_label = LearnDash_Custom_Label::get_label( 'button_mark_incomplete' );
	if ( empty( $button_label ) ) {
		$button_label = esc_html__( 'Mark Incomplete', 'learndash' );
	}
	$form_fields = '<input type="hidden" value="' . $post->ID . '" name="post" />
				<input type="hidden" value="' . learndash_get_course_id( $post->ID ) . '" name="course_id" />
				<input type="hidden" value="' . wp_create_nonce( 'sfwd_mark_incomplete_' . $user_id . '_' . $post->ID ) . '" name="sfwd_mark_incomplete" />
				<input type="submit" ' . $button_id . ' value="' . esc_attr( $button_label ) . '" ' . $button_class . ' ' . $button_disabled . '/>';

	/** This filter is documented in includes/course/ld-course-progress.php */
	$form_fields = apply_filters( 'learndash_mark_complete_form_fields', $form_fields, $post );

	$return = '<form ' . $form_id . ' ' . $form_class . ' method="post" action="">' . $form_fields . '</form>';

	return $return;
}

/**
 * Processes the request to mark a course or step incomplete.
 *
 * @since 3.1.4
 *
 * @global WP_Post $post Global post object.
 */
function learndash_mark_incomplete_process() {
	if ( ( isset( $_POST['sfwd_mark_incomplete'] ) ) && ( ! empty( $_POST['sfwd_mark_incomplete'] ) ) && ( isset( $_POST['post'] ) ) && ( ! empty( $_POST['post'] ) ) ) {
		$post_id = intval( $_POST['post'] );

		if ( isset( $_POST['course_id'] ) ) {
			$course_id = intval( $_POST['course_id'] );
		} else {
			$course_id = learndash_get_course_id( $post_id );
		}

		if ( isset( $_POST['userid'] ) ) {
			$user_id = intval( $_POST['userid'] );
		} else {
			if ( ! is_user_logged_in() ) {
				return;
			}

			$user_id = get_current_user_id();
		}

		/**
		 * Verify the form is valid
		 *
		 * @since 3.1.4
		 */
		if ( ! wp_verify_nonce( $_POST['sfwd_mark_incomplete'], 'sfwd_mark_incomplete_' . $user_id . '_' . $post_id ) ) {
			return;
		}

		return learndash_process_mark_incomplete( $user_id, $course_id, $post_id, false );
	}
	return false;
}
add_action( 'wp', 'learndash_mark_incomplete_process' );
