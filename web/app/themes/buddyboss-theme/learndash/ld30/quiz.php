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
 * @since   2.1.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! isset( $quiz_post ) ) || ( ! is_a( $quiz_post, 'WP_Post' ) ) ) {
	return;
}

global $post;
if ( empty( $course_id ) ) {
	$course_id = buddyboss_theme()->learndash_helper()->ld_30_get_course_id( $post->ID );
}
$lession_list        = learndash_get_course_lessons_list( $course_id, null, array( 'num' => - 1 ) );
$lession_list        = array_column( $lession_list, 'post' );
$course_quizzes_list = learndash_get_course_quiz_list( $course_id, $user_id );
$content_urls        = buddyboss_theme()->learndash_helper()->buddyboss_theme_ld_custom_pagination( $course_id, $lession_list, $course_quizzes_list );
$quiz_urls           = buddyboss_theme()->learndash_helper()->buddyboss_theme_ld_custom_quiz_count( $course_id, $lession_list, $course_quizzes_list );
$pagination_urls     = buddyboss_theme()->learndash_helper()->buddyboss_theme_custom_next_prev_url( $content_urls );
$current_quiz_ke     = buddyboss_theme()->learndash_helper()->buddyboss_theme_ld_custom_quiz_key( $quiz_urls );
$topics              = array();
$course              = get_post( $course_id );
$course_settings     = learndash_get_setting( $course );
if ( empty( $course ) ) {
	$course = get_post( $course_id );
}
?>

<div id="learndash-content" class="container-full">

	<div class="bb-grid grid">
		<?php
		if ( ! empty( $course ) ) :
			include locate_template( '/learndash/ld30/learndash-sidebar.php' );
		endif;
		?>

		<div id="learndash-page-content">
			<div class="learndash-content-body">
				<?php
				$buddyboss_content = apply_filters( 'buddyboss_learndash_content', '', $post );
				if ( ! empty( $buddyboss_content ) ) {
					echo $buddyboss_content;
				} else {
					?>
					<div class="<?php echo esc_attr( learndash_the_wrapper_class() ); ?>">

						<?php
						/**
						 * Fires before the quiz content starts.
						 *
						 * @since 3.0.0
						 *
						 * @param int $quiz_id   Quiz ID.
						 * @param int $course_id Course ID.
						 * @param int $user_id   User ID.
						 */
						do_action( 'learndash-quiz-before', $quiz_post->ID, $course_id, $user_id );
						?>
						<div id="learndash-course-header" class="bb-lms-header quiz-fix">
							<div class="flex bb-position">
								<div class="sfwd-course-position">
									<span class="bb-pages">
										<?php echo esc_html( LearnDash_Custom_Label::get_label( 'quiz' ) . ' ' . $current_quiz_ke ); ?> <span class="bb-total"><?php esc_html_e( 'of', 'buddyboss-theme' ); ?> <?php echo esc_html( count( $quiz_urls ) ); ?></span>
									</span>
								</div>
								<div class="sfwd-course-nav">
									<?php
									$expire_date_calc    = ld_course_access_expires_on( $course_id, $user_id );
									$courses_access_from = ld_course_access_from( $course_id, $user_id );
									$expire_access_days  = learndash_get_setting( $course_id, 'expire_access_days' );
									$date_format         = get_option( 'date_format' );
									$expire_date         = learndash_adjust_date_time_display( $expire_date_calc );
									$current             = time();
									$expire_string       = ( $expire_date_calc > $current ) ? __( 'Expires at', 'buddyboss-theme' ) : __( 'Expired at', 'buddyboss-theme' );
									if ( $expire_date_calc > 0 && abs( intval( $expire_access_days ) ) > 0 && ( ! empty( $user_id ) ) ) {
										?>
										<div class="sfwd-course-expire">
											<span data-balloon-pos="up" data-balloon="<?php echo esc_attr( $expire_string ); ?>"><i class="bb-icon-l bb-icon-alarm"></i><?php echo wp_kses_post( $expire_date ); ?></span>
										</div>
									<?php } ?>
									<div class="learndash_next_prev_link">
										<?php
										if ( $pagination_urls['prev'] != '' ) {
											echo $pagination_urls['prev'];
										} else {
											echo '<span class="prev-link empty-post"></span>';
										}
										?>
										<?php
										if ( $pagination_urls['next'] != '' || ( isset( $course_settings['course_disable_lesson_progression'] ) && $course_settings['course_disable_lesson_progression'] === 'on' && $pagination_urls['next'] != '' ) ) {
											echo $pagination_urls['next'];
										} else {
											echo '<span class="next-link empty-post"></span>';
										}
										?>
									</div>
								</div>
							</div>
							<div class="lms-header-title">
								<h1><?php echo $post->post_title; ?></h1>
							</div>
							<?php
							global $post;
							$course_post = learndash_get_setting( $post, 'course' );
							$course_data = get_post( $course_post );
							$author_id   = $course_data->post_author;
							learndash_get_template_part(
								'template-course-author.php',
								array(
									'user_id' => $author_id,
								),
								true
							);
							?>
						</div>

						<div class="learndash_content_wrap">

							<?php
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

									if ( ( learndash_is_sample( $quiz_post ) ) ) {
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
												'course_id' => $course_id,
												'user_id' => $user_id,
												'context' => 'quiz',
												'sub_context' => $sub_context,
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
									echo $quiz_content;
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
												esc_html_x( 'You have already taken this %1$s %2$d time(s) and may not take it again.', 'placeholders: quiz, attempts count', 'buddyboss-theme' ),
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

							$focus_mode         = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'focus_mode_enabled' );
							$post_type          = get_post_type( $post->ID );
							$post_type_comments = learndash_post_type_supports_comments( $post_type );
							if ( is_user_logged_in() && 'yes' === $focus_mode && comments_open() ) {
								learndash_get_template_part(
									'focus/comments.php',
									array(
										'course_id' => $course_id,
										'user_id'   => $user_id,
										'context'   => 'focus',
									),
									true
								);
							} elseif ( true === $post_type_comments ) {
								if ( comments_open() ) :
									comments_template();
								endif;
							}
							?>

						</div><?php /* .learndash_content_wrap */ ?>

					</div> <!--/.learndash-wrapper-->
				<?php } ?>
			</div><?php /* .learndash-content-body */ ?>
		</div><?php /* #learndash-page-content */ ?>
	</div>

</div>
