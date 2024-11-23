<?php
/**
 * LearnDash LD30 Displays a topic.
 *
 * Available Variables:
 *
 * $course_id                 : (int) ID of the course
 * $course                    : (object) Post object of the course
 * $course_settings           : (array) Settings specific to current course
 * $course_status             : Course Status
 * $has_access                : User has access to course or is enrolled.
 *
 * $courses_options            : Options/Settings as configured on Course Options page
 * $lessons_options            : Options/Settings as configured on Lessons Options page
 * $quizzes_options            : Options/Settings as configured on Quiz Options page
 *
 * $user_id                    : (object) Current User ID
 * $logged_in                  : (true/false) User is logged in
 * $current_user               : (object) Currently logged in user object
 * $quizzes                    : (array) Quizzes Array
 * $post                       : (object) The topic post object
 * $lesson_post                : (object) Lesson post object in which the topic exists
 * $topics                     : (array) Array of Topics in the current lesson
 * $all_quizzes_completed      : (true/false) User has completed all quizzes on the lesson Or, there are no quizzes.
 * $lesson_progression_enabled : (true/false)
 * $show_content               : (true/false) true if lesson progression is disabled or if previous lesson and topic is completed.
 * $previous_lesson_completed  : (true/false) true if previous lesson is completed
 * $previous_topic_completed   : (true/false) true if previous topic is completed
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lesson_data = $post;

if ( empty( $course_id ) ) {
	$course_id = learndash_get_course_id( $lesson_data->ID );

	if ( empty( $course_id ) ) {
		$course_id = buddyboss_theme()->learndash_helper()->ld_30_get_course_id( $lesson_data->ID );
	}
}

$lession_list    = learndash_get_course_lessons_list( $course_id, null, array( 'num' => - 1 ) );
$lession_list    = array_column( $lession_list, 'post' );
$content_urls    = buddyboss_theme()->learndash_helper()->buddyboss_theme_ld_custom_pagination( $course_id, $lession_list );
$pagination_urls = buddyboss_theme()->learndash_helper()->buddyboss_theme_custom_next_prev_url( $content_urls );

if ( empty( $course ) ) {
	if ( empty( $course_id ) ) {
		$course = learndash_get_course_id( $lesson_data->ID );
	} else {
		$course = get_post( $course_id );
	}
}
$lesson_id = learndash_get_lesson_id( $lesson_data->ID );
$topics    = learndash_get_topic_list( $lesson_id, $course_id );
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
						if ( $les->ID === (int) $lesson_id ) {
							break;
						}
						$lesson_no ++;
					}

					$topic_no = 1;
					foreach ( $topics as $topic ) {
						if ( $topic->ID === $post->ID ) {
							break;
						}
						$topic_no ++;
					}
					?>

					<div class="<?php echo esc_attr( learndash_the_wrapper_class() ); ?>">
						<?php
						/**
						 * Fires before the topic
						 *
						 * @since 3.0.0
						 * @param int $course_id Course ID.
						 * @param int $user_id   User ID.
						 */
						 do_action( 'learndash-topic-before', get_the_ID(), $course_id, $user_id );
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
											'context'   => 'topic',
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
									<span class="bb-pages">
										<?php echo esc_html( LearnDash_Custom_Label::get_label( 'lesson' ) . ' ' . $lesson_no ); ?>,
										<?php echo esc_html( LearnDash_Custom_Label::get_label( 'topic' ) . ' ' . $topic_no ); ?>
									</span>
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
										if (
											( apply_filters( 'learndash_show_next_link', learndash_is_topic_complete( $user_id, $post->ID ), $user_id, $post->ID ) && $pagination_urls['next'] != '' ) ||
											(
												isset( $course_settings['course_disable_lesson_progression'] ) &&
												$course_settings['course_disable_lesson_progression'] === 'on' &&
												isset( $pagination_urls['next'] ) &&
												$pagination_urls['next'] != ''
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
						learndash_get_template_part(
							'modules/progress.php',
							array(
								'context'   => 'topic',
								'user_id'   => $user_id,
								'course_id' => $course_id,
							),
							true
						);


						/**
						 * If the user needs to complete the previous lesson AND topic display an alert
						 */

						$sub_context = '';
						if ( ( $lesson_progression_enabled ) && ( ! learndash_user_progress_is_step_complete( $user_id, $course_id, $post->ID ) ) ) {
							$previous_item = learndash_get_previous( $post );
							if ( ( ! $previous_topic_completed ) || ( empty( $previous_item ) ) ) {
								if ( 'on' === learndash_get_setting( $lesson_post->ID, 'lesson_video_enabled' ) ) {
									if ( ! empty( learndash_get_setting( $lesson_post->ID, 'lesson_video_url' ) ) ) {
										if ( 'BEFORE' === learndash_get_setting( $lesson_post->ID, 'lesson_video_shown' ) ) {
											if ( ! learndash_video_complete_for_step( $lesson_post->ID, $course_id, $user_id ) ) {
												$sub_context = 'video_progression';
											}
										}
									}
								}
							}
						}

						if ( ( ! learndash_is_sample( $post ) ) && ( $lesson_progression_enabled ) && ( ! empty( $sub_context ) || ! $previous_topic_completed || ! $previous_lesson_completed ) ) :

							if ( 'video_progression' === $sub_context ) {
								$previous_item = $lesson_post;
							} else {
								$previous_item_id = learndash_user_progress_get_previous_incomplete_step( $user_id, $course_id, $post->ID );
								if ( ! empty( $previous_item_id ) ) {
									$previous_item = get_post( $previous_item_id );
								}
							}

							if ( ( isset( $previous_item ) ) && ( ! empty( $previous_item ) ) ) {
								$show_content = false;
								learndash_get_template_part(
									'modules/messages/lesson-progression.php',
									array(
										'previous_item' => $previous_item,
										'course_id'     => $course_id,
										'context'       => 'topic',
										'sub_context'   => $sub_context,
									),
									true
								);
							}
						endif;

						if ( $show_content ) :

							learndash_get_template_part(
								'modules/tabs.php',
								array(
									'course_id' => $course_id,
									'post_id'   => get_the_ID(),
									'user_id'   => $user_id,
									'content'   => $content,
									'materials' => $materials,
									'context'   => 'topic',
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
								if ( learndash_lesson_hasassignments( $post ) && ! empty( $user_id ) ) :
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
												'user_id' => $user_id,
												'course_step_post' => $post,
												'course_id' => $course_id,
												'context' => 'topic',
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
							}
						endif; // $show_content


						if ( ( defined( 'LEARNDASH_TEMPLATE_CONTENT_METHOD' ) ) && ( 'shortcode' === LEARNDASH_TEMPLATE_CONTENT_METHOD ) ) {
							$shown_content_key = 'learndash-shortcode-wrap-ld_navigation-' . absint( $course_id ) . '_' . (int) get_the_ID() . '_' . absint( $user_id );
							if ( false === strstr( $content, $shown_content_key ) ) {
								$shortcode_out = do_shortcode( '[ld_navigation course_id="' . $course_id . '" user_id="' . $user_id . '" post_id="' . get_the_ID() . '"]' );
								if ( ! empty( $shortcode_out ) ) {
									echo $shortcode_out;
								}
							}
						} else {
							$can_complete = false;

							if ( $all_quizzes_completed && $logged_in && ! empty( $course_id ) ) :
								/** This filter is documented in themes/ld30/templates/lesson.php */
								$can_complete = apply_filters( 'learndash-lesson-can-complete', true, get_the_ID(), $course_id, $user_id );
							endif;

							learndash_get_template_part(
								'modules/course-steps.php',
								array(
									'course_id'        => $course_id,
									'course_step_post' => $post,
									'all_quizzes_completed' => $all_quizzes_completed,
									'user_id'          => $user_id,
									'course_settings'  => isset( $course_settings ) ? $course_settings : array(),
									'context'          => 'topic',
									'can_complete'     => $can_complete,
								),
								true
							);
						}

						/**
						 * Fires after the topic.
						 *
						 * @since 3.0.0
						 *
						 * @param int $post_id   Current Post ID.
						 * @param int $course_id Course ID.
						 * @param int $user_id   User ID.
						 */
						do_action( 'learndash-topic-after', get_the_ID(), $course_id, $user_id );

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
