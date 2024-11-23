<?php
/**
 * LearnDash LD30 Displays a lesson.
 *
 * Available Variables:
 *
 * $course_id                  : (int) ID of the course
 * $course                     : (object) Post object of the course
 * $course_settings            : (array) Settings specific to current course
 * $course_status              : Course Status
 * $has_access                 : User has access to course or is enrolled.
 *
 * $courses_options            : Options/Settings as configured on Course Options page
 * $lessons_options            : Options/Settings as configured on Lessons Options page
 * $quizzes_options            : Options/Settings as configured on Quiz Options page
 *
 * $user_id                    : (object) Current User ID
 * $logged_in                  : (true/false) User is logged in
 * $current_user               : (object) Currently logged in user object
 *
 * $quizzes                    : (array) Quizzes Array
 * $post                       : (object) The lesson post object
 * $topics                     : (array) Array of Topics in the current lesson
 * $all_quizzes_completed      : (true/false) User has completed all quizzes on the lesson Or, there are no quizzes.
 * $lesson_progression_enabled : (true/false)
 * $show_content               : (true/false) true if lesson progression is disabled or if previous lesson is completed.
 * $previous_lesson_completed  : (true/false) true if previous lesson is completed
 * $lesson_settings            : Settings specific to the current lesson.
 *
 * @since   3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$in_focus_mode = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'focus_mode_enabled' );
add_filter( 'comments_array', 'learndash_remove_comments', 1, 2 );
$lesson_data = $post;
if ( empty( $course_id ) ) {
	$course_id = learndash_get_course_id( $lesson_data->ID );
	if ( empty( $course_id ) ) {
		$course_id = (int) buddyboss_theme()->learndash_helper()->ld_30_get_course_id( $lesson_data->ID );
	}
}
$lession_list            = learndash_get_course_lessons_list( $course_id, null, array( 'num' => - 1 ) );
$lession_list            = array_column( $lession_list, 'post' );
$lesson_topics_completed = learndash_lesson_topics_completed( $post->ID );
$content_urls            = buddyboss_theme()->learndash_helper()->buddyboss_theme_ld_custom_pagination( $course_id, $lession_list );
$pagination_urls         = buddyboss_theme()->learndash_helper()->buddyboss_theme_custom_next_prev_url( $content_urls );
if ( empty( $course ) ) {
	if ( empty( $course_id ) ) {
		$course = learndash_get_course_id( $lesson_data->ID );
	} else {
		$course = get_post( $course_id );
	}
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

					$lesson_no = 1;
					foreach ( $lession_list as $les ) {
						if ( $les->ID == $post->ID ) {
							break;
						}
						$lesson_no ++;
					}
					?>

					<div class="<?php echo esc_attr( learndash_the_wrapper_class() ); ?>">

						<?php
						/**
						 * Fires before the lesson.
						 *
						 * @since 3.0.0
						 *
						 * @param int $post_id   Post ID.
						 * @param int $course_id Course ID.
						 * @param int $user_id   User ID.
						 */
						do_action( 'learndash-lesson-before', get_the_ID(), $course_id, $user_id );
						?>
						<div id="learndash-course-header" class="bb-lms-header">
							<div class="bb-ld-info-bar">
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
											'context'   => 'lesson',
											'course_id' => $course_id,
											'user_id'   => $user_id,
										),
										true
									);
								}
								?>
							</div>
							<div class="flex bb-position">
								<div class="sfwd-course-position">
									<span class="bb-pages"><?php echo LearnDash_Custom_Label::get_label( 'lesson' ); ?> <?php echo $lesson_no; ?> <span
												class="bb-total"><?php esc_html_e( 'of', 'buddyboss-theme' ); ?> <?php echo count( $lession_list ); ?></span></span>
								</div>
								<div class="sfwd-course-nav">
									<div class="bb-ld-status">
										<?php
										$status = ( learndash_is_item_complete() ? 'complete' : 'incomplete' );
										learndash_status_bubble( $status );
										?>
									</div>
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
											<span data-balloon-pos="up" data-balloon="<?php echo $expire_string; ?>"><i
														class="bb-icon-l bb-icon-alarm"></i><?php echo $expire_date; ?></span>
										</div>
									<?php } ?>
									<div class="learndash_next_prev_link">
										<?php
										if ( isset( $pagination_urls['prev'] ) && $pagination_urls['prev'] != '' ) {
											echo $pagination_urls['prev'];
										} else {
											echo '<span class="prev-link empty-post"></span>';
										}
										?>
										<?php
										if (
											(
												isset( $pagination_urls['next'] ) &&
												apply_filters( 'learndash_show_next_link', learndash_is_lesson_complete( $user_id, $post->ID ), $user_id, $post->ID ) &&
												$pagination_urls['next'] != ''
											) ||
											(
												isset( $pagination_urls['next'] ) &&
												$pagination_urls['next'] != '' &&
												isset( $course_settings['course_disable_lesson_progression'] ) &&
												$course_settings['course_disable_lesson_progression'] === 'on'
											)
										) {
											echo $pagination_urls['next'];
										} else {
											echo '<span class="next-link empty-post"></span>';
										}
										?>
									</div>
								</div>
							</div>
							<div class="lms-header-title">
								<h1><?php the_title(); ?></h1>
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
							/**
							 * If the user needs to complete the previous lesson display an alert
							 */
							if ( ( isset( $lesson_progression_enabled ) ) && ( true === (bool) $lesson_progression_enabled ) && ( isset( $previous_lesson_completed ) ) && ( true !== $previous_lesson_completed ) ) {
								if ( ( ! learndash_is_sample( $post ) ) || ( learndash_is_sample( $post ) && true === ( bool) $has_access ) ) {
									$previous_item_id = learndash_user_progress_get_previous_incomplete_step( $user_id, $course_id, $post->ID );
									if ( ! empty( $previous_item_id ) ) {
										learndash_get_template_part(
											'modules/messages/lesson-progression.php',
											array(
												'previous_item' => get_post( $previous_item_id ),
												'course_id'     => $course_id,
												'context'       => 'lesson',
												'user_id'       => $user_id,
											),
											true
										);
									}
								}
							}
							if ( $show_content ) :

								/**
								 * Content and/or tabs
								 */
								learndash_get_template_part(
									'modules/tabs.php',
									array(
										'course_id' => $course_id,
										'post_id'   => get_the_ID(),
										'user_id'   => $user_id,
										'content'   => $content,
										'materials' => $materials,
										'context'   => 'lesson',
									),
									true
								);

								if ( ( defined( 'LEARNDASH_TEMPLATE_CONTENT_METHOD' ) ) && ( 'shortcode' === LEARNDASH_TEMPLATE_CONTENT_METHOD ) ) {
									$shown_content_key = 'learndash-shortcode-wrap-course_content-' . absint( $course_id ) . '_' . (int) get_the_ID() . '_' . absint( $user_id );
									if ( false === strstr( $content, $shown_content_key ) ) {
										$shortcode_out = do_shortcode( '[course_content course_id="' . $course_id . '" user_id="' . $user_id . '" post_id="' . get_the_ID() . '"]' );
										if ( ! empty( $shortcode_out ) ) {
											echo $shortcode_out;
										}
									}
								} else {
									/**
									 * Display Lesson Assignments
									 */
									if ( learndash_lesson_hasassignments( $post ) && ! empty( $user_id ) ) : // cspell:disable-line.
										$bypass_course_limits_admin_users = learndash_can_user_bypass( $user_id, 'learndash_lesson_assignment' );
										$course_children_steps_completed  = learndash_user_is_course_children_progress_complete( $user_id, $course_id, $post->ID );

										if ( ( learndash_lesson_progression_enabled() && $course_children_steps_completed ) || ! learndash_lesson_progression_enabled() || $bypass_course_limits_admin_users ) :

											/**
											 * Fires before the lesson assignment.
											 *
											 * @since 3.0.0
											 *
											 * @param int $post_id   Post ID.
											 * @param int $course_id Course ID.
											 * @param int $user_id   User ID.
											 */
											do_action( 'learndash-lesson-assignment-before', get_the_ID(), $course_id, $user_id );

											learndash_get_template_part(
												'assignment/listing.php',
												array(
													'course_step_post' => $post,
													'user_id'          => $user_id,
													'course_id'        => $course_id,
												),
												true
											);
											/**
											 * Fires after the lesson assignment.
											 *
											 * @since 3.0.0
											 *
											 * @param int $post_id   Post ID.
											 * @param int $course_id Course ID.
											 * @param int $user_id   User ID.
											 */
											do_action( 'learndash-lesson-assignment-after', get_the_ID(), $course_id, $user_id );
										endif;
									endif;

									/**
									 * Lesson Topics or Quizzes
									 */
									if ( ! empty( $topics ) || ! empty( $quizzes ) ) :
										/**
										 * Fires before the course certificate link
										 *
										 * @since 3.0.0
										 *
										 * @param int $post_id   Post ID.
										 * @param int $course_id Course ID.
										 * @param int $user_id   User ID.
										 */
										do_action( 'learndash-lesson-content-list-before', get_the_ID(), $course_id, $user_id );
										global $post;
										$lesson = array(
											'post' => $post,
										);
										learndash_get_template_part(
											'lesson/listing.php',
											array(
												'course_id' => $course_id,
												'lesson'    => $lesson,
												'topics'    => $topics,
												'quizzes'   => $quizzes,
												'user_id'   => $user_id,
											),
											true
										);
										/**
										 * Fires before the course certificate link
										 *
										 * @since 3.0.0
										 *
										 * @param int $post_id   Post ID.
										 * @param int $course_id Course ID.
										 * @param int $user_id   User ID.
										 */
										do_action( 'learndash-lesson-content-list-after', get_the_ID(), $course_id, $user_id );
									endif;
								}
							endif; // end $show_content.

							if ( ( defined( 'LEARNDASH_TEMPLATE_CONTENT_METHOD' ) ) && ( 'shortcode' === LEARNDASH_TEMPLATE_CONTENT_METHOD ) ) {
								$shown_content_key = 'learndash-shortcode-wrap-ld_navigation-' . absint( $course_id ) . '_' . (int) get_the_ID() . '_' . absint( $user_id );
								if ( false === strstr( $content, $shown_content_key ) ) {
									$shortcode_out = do_shortcode( '[ld_navigation course_id="' . $course_id . '" user_id="' . $user_id . '" post_id="' . get_the_ID() . '"]' );
									if ( ! empty( $shortcode_out ) ) {
										echo $shortcode_out;
									}
								}
							} else {
							
								/**
								 * Set a variable to switch the next button to complete button
								 *
								 * @var $can_complete [bool] - can the user complete this or not?
								 */
								$can_complete = false;
								if ( $all_quizzes_completed && $logged_in && ! empty( $course_id ) ) :
									$can_complete = $previous_lesson_completed;

									/**
									 * Filters whether a user can complete the lesson or not.
									 *
									 * @since 3.0.0
									 *
									 * @param boolean $can_complete Whether user can complete lesson or not.
									 * @param int     $post_id      Lesson ID/Topic ID.
									 * @param int     $course_id    Course ID.
									 * @param int     $user_id      User ID.
									 */
									$can_complete = apply_filters( 'learndash-lesson-can-complete', true, get_the_ID(), $course_id, $user_id );
								endif;
								learndash_get_template_part(
									'modules/course-steps.php',
									array(
										'course_id'        => $course_id,
										'course_step_post' => $post,
										'user_id'          => $user_id,
										'course_settings'  => isset( $course_settings ) ? $course_settings : array(),
										'can_complete'     => $can_complete,
										'context'          => 'lesson',
									),
									true
								);
							}

							/**
							 * Fires after the lesson
							 *
							 * @since 3.0.0
							 *
							 * @param int $post_id   Post ID.
							 * @param int $course_id Course ID.
							 * @param int $user_id   User ID.
							 */
							do_action( 'learndash-lesson-after', get_the_ID(), $course_id, $user_id );
							?>

							<?php
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
