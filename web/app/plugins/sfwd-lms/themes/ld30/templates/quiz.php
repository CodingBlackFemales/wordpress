<?php
/**
 * LearnDash LD30 Displays a quiz.
 *
 * Available Variables:
 *
 * $course_id                   : (int) ID of the course
 * $course                      : (object) Post object of the course
 * $course_settings             : (array) Settings specific to current course
 * $course_status               : Course Status
 * $has_access                  : User has access to course or is enrolled.
 *
 * $courses_options             : Options/Settings as configured on Course Options page
 * $lessons_options             : Options/Settings as configured on Lessons Options page
 * $quizzes_options             : Options/Settings as configured on Quiz Options page
 *
 * $user_id                     : (object) Current User ID
 * $logged_in                   : (true/false) User is logged in
 * $current_user                : (object) Currently logged in user object
 * $post                        : (object) The quiz post object () (Deprecated in LD 3.1. User $quiz_post instead).
 * $quiz_post                   : (object) The quiz post object ().
 * $lesson_progression_enabled  : (true/false)
 * $show_content                : (true/false) true if user is logged in and lesson progression is disabled or if previous lesson and topic is completed.
 * $attempts_left               : (true/false)
 * $attempts_count              : (integer) No of attempts already made
 * $quiz_settings               : (array)
 *
 * Note:
 *
 * To get lesson/topic post object under which the quiz is added:
 * $lesson_post = !empty($quiz_settings["lesson"])? get_post($quiz_settings["lesson"]):null;
 *
 * @since 2.1.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! isset( $quiz_post ) ) || ( ! is_a( $quiz_post, 'WP_Post' ) ) ) {
	return;
}
?>
<div class="<?php echo esc_attr( learndash_the_wrapper_class() ); ?>">
<?php
	/**
	 * Fires before the quiz content starts.
	 *
	 * @since 3.0.0
	 *
	 * @param int $quiz_id Quiz ID.
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	do_action( 'learndash-quiz-before', $quiz_post->ID, $course_id, $user_id );

	if ( ( defined( 'LEARNDASH_TEMPLATE_CONTENT_METHOD' ) ) && ( 'shortcode' === LEARNDASH_TEMPLATE_CONTENT_METHOD ) ) {
		$shown_content_key = 'learndash-shortcode-wrap-ld_infobar-' . absint( $course_id ) . '_' . (int) get_the_ID() . '_' . absint( $user_id );
		if ( false === strstr( $content, $shown_content_key ) ) {
			$shortcode_out = do_shortcode( '[ld_infobar course_id="' . $course_id . '" user_id="' . $user_id . '" post_id="' . get_the_ID() . '"]' );
			if ( ! empty( $shortcode_out ) ) {
				echo $shortcode_out;
			}
		}
	} else {
		learndash_get_template_part(
			'modules/infobar.php',
			array(
				'context'   => 'quiz',
				'course_id' => $course_id,
				'user_id'   => $user_id,
				'post'      => $quiz_post,
			),
			true
		);
	}

	if ( ! empty( $lesson_progression_enabled ) ) :
		$last_incomplete_step = learndash_is_quiz_accessable( $user_id, $quiz_post, true, $course_id );
		if ( ! empty( $user_id ) ) {
			if ( learndash_user_progress_is_step_complete( $user_id, $course_id, $quiz_post->ID ) ) {
				$show_content = true;
			} else {
				if ( $bypass_course_limits_admin_users ) {
					remove_filter( 'learndash_content', 'lesson_visible_after', 1, 2 );
					$previous_lesson_completed = true;
				} else {
					$previous_step_post_id = learndash_user_progress_get_parent_incomplete_step( $user_id, $course_id, $quiz_post->ID );
					if ( ( ! empty( $previous_step_post_id ) ) && ( $previous_step_post_id !== $quiz_post->ID ) ) {
						$previous_lesson_completed = false;

						$last_incomplete_step = get_post( $previous_step_post_id );
					} else {
						$previous_step_post_id     = learndash_user_progress_get_previous_incomplete_step( $user_id, $course_id, $quiz_post->ID );
						$previous_lesson_completed = true;
						if ( ( ! empty( $previous_step_post_id ) ) && ( $previous_step_post_id !== $quiz_post->ID ) ) {
							$previous_lesson_completed = false;

							$last_incomplete_step = get_post( $previous_step_post_id );
						}
					}

					/**
					 * Filter to override previous step completed.
					 *
					 * @param bool $previous_lesson_completed True if previous step completed.
					 * @param int  $step_id                   Step Post ID.
					 * @param int  $user_id                   User ID.
					 */
					$previous_lesson_completed = apply_filters( 'learndash_previous_step_completed', $previous_lesson_completed, $quiz_post->ID, $user_id );
				}

				$show_content = $previous_lesson_completed;
			}

			if ( ( learndash_is_sample( $quiz_post ) ) /* && ( true !== (bool) $has_access ) */ ) {
				$show_content = true;
			} elseif ( ( $last_incomplete_step ) && ( is_a( $last_incomplete_step, 'WP_Post' ) ) ) {
				$show_content = false;

				$sub_context = '';
				if ( 'on' === learndash_get_setting( $last_incomplete_step->ID, 'lesson_video_enabled' ) ) {
					if ( ! empty( learndash_get_setting( $last_incomplete_step->ID, 'lesson_video_url' ) ) ) {
						if ( 'BEFORE' === learndash_get_setting( $last_incomplete_step->ID, 'lesson_video_shown' ) ) {
							if ( ! learndash_video_complete_for_step( $last_incomplete_step->ID, $course_id, $user_id ) ) {
								$sub_context = 'video_progression';
							}
						}
					}
				}

				/**
				 * Fires before the quiz progression.
				 *
				 * @since 3.0.0
				 *
				 * @param int $quiz_id   Quiz ID.
				 * @param int $course_id Course ID.
				 * @param int $user_id   User ID.
				 */
				do_action( 'learndash-quiz-progression-before', $quiz_post->ID, $course_id, $user_id );

				learndash_get_template_part(
						'modules/messages/lesson-progression.php',
						array(
							'previous_item' => $last_incomplete_step,
							'course_id'     => $course_id,
							'user_id'       => $user_id,
							'context'       => 'quiz',
							'sub_context'   => $sub_context,
						),
						true
					);

				/**
				 * Fires after the quiz progress.
				 *
				 * @since 3.0.0
				 *
				 * @param int $quiz_id   Quiz ID.
				 * @param int $course_id Course ID.
				 * @param int $user_id   User ID.
				 */
				do_action( 'learndash-quiz-progression-after', $quiz_post->ID, $course_id, $user_id );

			}
		} else {
			$show_content = true;
		}
	endif;

	if ( $show_content ) :

		/**
		 * Content and/or tabs
		 */
		learndash_get_template_part(
			'modules/tabs.php',
			array(
				'course_id' => $course_id,
				'post_id'   => $quiz_post->ID,
				'user_id'   => $user_id,
				'content'   => $content,
				'materials' => $materials,
				'context'   => 'quiz',
			),
			true
		);

		if ( $attempts_left ) :

			/**
			 * Fires before the actual quiz content (not WP_Editor content).
			 *
			 * @since 3.0.0
			 *
			 * @param int $quiz_id   Quiz ID.
			 * @param int $course_id Course ID.
			 * @param int $user_id   User ID.
			 */
			do_action( 'learndash-quiz-actual-content-before', $quiz_post->ID, $course_id, $user_id );

			echo $quiz_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Post content

			/**
			 * Fires after the actual quiz content (not WP_Editor content).
			 *
			 * @since 3.0.0
			 *
			 * @param int $quiz_id   Quiz ID.
			 * @param int $course_id Course ID.
			 * @param int $user_id   User ID.
			 */
			do_action( 'learndash-quiz-actual-content-after', $quiz_post->ID, $course_id, $user_id );

		else :

			/**
			 * Display an alert
			 */

			/**
			 * Fires before the quiz attempts alert.
			 *
			 * @since 3.0.0
			 *
			 * @param int $quiz_id   Quiz ID.
			 * @param int $course_id Course ID.
			 * @param int $user_id   User ID.
			 */
			do_action( 'learndash-quiz-attempts-alert-before', $quiz_post->ID, $course_id, $user_id );

			learndash_get_template_part(
				'modules/alert.php',
				array(
					'type'    => 'warning',
					'icon'    => 'alert',
					'message' => sprintf(
						// translators: placeholders: quiz, attempts count.
						esc_html_x( 'You have already taken this %1$s %2$d time(s) and may not take it again.', 'placeholders: quiz, attempts count', 'learndash' ),
						learndash_get_custom_label_lower( 'quiz' ),
						$attempts_count
					),
				),
				true
			);

			/**
			 * Fires after the quiz attempts alert.
			 *
			 * @since 3.0.0
			 *
			 * @param int $quiz_id   Quiz ID.
			 * @param int $course_id Course ID.
			 * @param int $user_id   User ID.
			 */
			do_action( 'learndash-quiz-attempts-alert-after', $quiz_post->ID, $course_id, $user_id );

		endif;
	endif;

	/**
	 * Fires before the quiz content starts.
	 *
	 * @since 3.0.0
	 *
	 * @param int $quiz_id   Quiz ID.
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	do_action( 'learndash-quiz-after', $quiz_post->ID, $course_id, $user_id );
	learndash_load_login_modal_html();
	?>

</div> <!--/.learndash-wrapper-->
