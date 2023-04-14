<?php

global $post, $wpdb;

$parent_course_data = learndash_get_setting( $post, 'course' );
if ( 0 === $parent_course_data ) {
	$parent_course_data = $course_id;
	if ( 0 === $parent_course_data ) {
		$course_id = buddyboss_theme()->learndash_helper()->ld_30_get_course_id( $post->ID );
	}
	$parent_course_data = learndash_get_setting( $course_id, 'course' );
}

$parent_course       = get_post( $parent_course_data );
$parent_course_link  = $parent_course->guid;
$parent_course_title = $parent_course->post_title;
$is_enrolled         = false;
$current_user_id     = get_current_user_id();
$get_course_groups   = learndash_get_course_groups( $parent_course->ID );
$course_id           = $parent_course->ID;
$admin_enrolled      = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' );
$members_count       = 0;

if ( buddyboss_theme_get_option( 'learndash_course_participants', null, true ) ) {
	$members_count = buddyboss_theme()->learndash_helper()->buddyboss_theme_ld_course_enrolled_users_list( $parent_course->ID );
	$members_arr   = learndash_get_users_for_course( $course_id, array( 'number' => 5 ), false );
	if ( ( $members_arr instanceof WP_User_Query ) && ( property_exists( $members_arr, 'results' ) ) && ( ! empty( $members_arr->results ) ) ) {
		$members = $members_arr->get_results();
	} else {
		$members = array();
	}
}

if ( isset( $get_course_groups ) && ! empty( $get_course_groups ) && ( function_exists( 'buddypress' ) && bp_is_active( 'groups' ) ) ) {
	foreach ( $get_course_groups as $k => $group ) {
		$bp_group_id = (int) get_post_meta( $group, '_sync_group_id', true );
		if ( ! groups_is_user_member( bp_loggedin_user_id(), $bp_group_id ) ) {
			if ( ( $key = array_search( $group, $get_course_groups ) ) !== false ) {
				unset( $get_course_groups[ $key ] );
			}
		}
	}
}

if ( sfwd_lms_has_access( $course_id, $current_user_id ) ) {
	$is_enrolled = true;
} else {
	$is_enrolled = false;
}

// if admins are enrolled.
if ( current_user_can( 'administrator' ) && 'yes' === $admin_enrolled ) {
	$is_enrolled = true;
}

// check if lesson sidebar is closed.
$side_panel_state_class = '';
if ( ( isset( $_COOKIE['lessonpanel'] ) && 'closed' === $_COOKIE['lessonpanel'] ) ) {
	$side_panel_state_class = 'lms-topic-sidebar-close';
}
?>

<div class="lms-topic-sidebar-wrapper <?php echo esc_attr( $side_panel_state_class ); ?>">
	<div class="lms-topic-sidebar-data">
		<?php
		$course_progress = learndash_course_progress(
			array(
				'user_id'   => get_current_user_id(),
				'course_id' => $parent_course->ID,
				'array'     => true,
			)
		);

		if ( empty( $course_progress ) ) {
			$course_progress = array(
				'percentage' => 0,
				'completed'  => 0,
				'total'      => 0,
			);
		}
		?>

		<div class="bb-elementor-header-items">
			<a href="#" id="bb-toggle-theme">
				<span class="sfwd-dark-mode" data-balloon-pos="down" data-balloon="<?php esc_attr_e( 'Dark Mode', 'buddyboss-theme' ); ?>"><i class="bb-icon-rl bb-icon-moon"></i></span>
				<span class="sfwd-light-mode" data-balloon-pos="down" data-balloon="<?php esc_attr_e( 'Light Mode', 'buddyboss-theme' ); ?>"><i class="bb-icon-l bb-icon-sun"></i></span>
			</a>
			<a href="#" class="header-maximize-link course-toggle-view" data-balloon-pos="down" data-balloon="<?php esc_attr_e( 'Maximize', 'buddyboss-theme' ); ?>"><i class="bb-icon-l bb-icon-expand"></i></a>
			<a href="#" class="header-minimize-link course-toggle-view" data-balloon-pos="down" data-balloon="<?php esc_attr_e( 'Minimize', 'buddyboss-theme' ); ?>"><i class="bb-icon-l bb-icon-merge"></i></a>
		</div>

		<div class="lms-topic-sidebar-course-navigation">
			<div class="ld-course-navigation">
				<a title="<?php echo esc_attr( $parent_course_title ); ?>" href="<?php echo esc_url( get_permalink( $parent_course->ID ) ); ?>" class="course-entry-link">
					<span>
						<i class="bb-icon-l bb-icon-angle-left"></i>
						<?php echo sprintf( esc_html_x( 'Back to %s', 'link: Back to Course', 'buddyboss-theme' ), LearnDash_Custom_Label::get_label( 'course' ) ); ?>
					</span>
				</a>
				<h2 class="course-entry-title"><?php echo esc_html( $parent_course_title ); ?></h2>
			</div>
		</div>

		<div class="lms-topic-sidebar-progress">
			<div class="course-progress-wrap">
				<?php
				learndash_get_template_part(
					'modules/progress.php',
					array(
						'context'   => 'course',
						'user_id'   => get_current_user_id(),
						'course_id' => $parent_course->ID,
					),
					true
				);
				?>
			</div>
		</div>

		<?php
		$course_progress = get_user_meta( get_current_user_id(), '_sfwd-course_progress', true );
		?>

		<div class="lms-lessions-list">
			<?php
			if ( ! empty( $lession_list ) ) :
				$sections = learndash_30_get_course_sections( $parent_course->ID );
				?>
				<ol class="bb-lessons-list">
					<?php
					foreach ( $lession_list as $lesson ) {


						$lesson_topics  = learndash_get_topic_list( $lesson->ID, $parent_course->ID );
						$lesson_quizzes = learndash_get_lesson_quiz_list( $lesson->ID, get_current_user_id(), $course_id );
						$lesson_sample  = learndash_get_setting( $lesson->ID, 'sample_lesson' ) == 'on' ? 'bb-lms-is-sample' : '';
						$attributes 	= learndash_get_course_step_attributes( $lesson->ID, $course_id, $user_id );

						$is_sample            = ( isset( $lesson->sample ) ? $lesson->sample : false );
						$bb_lesson_has_access = sfwd_lms_has_access( $lesson->ID, $user_id );
						$bb_available_date 	  = learndash_course_step_available_date( $lesson->ID, $course_id, $user_id, true );
						$atts                 = apply_filters( 'learndash_quiz_row_atts', ( ( isset( $bb_lesson_has_access ) && ! $bb_lesson_has_access && ! $is_sample ) || ( ! empty( $bb_available_date ) && ! $is_sample ) ? 'data-balloon-pos="up" data-balloon="' . __( "You don't currently have access to this content", 'buddyboss-theme' ) . '"' : '' ) );
						$atts_access_marker   = apply_filters( 'learndash_quiz_row_atts', ( ( isset( $bb_lesson_has_access ) && ! $bb_lesson_has_access && ! $is_sample ) || ( ! empty( $bb_available_date ) && ! $is_sample ) ? '<span class="lms-is-locked-ico" data-balloon-pos="left" data-balloon="' . esc_attr( $attributes[0]['label'] ) . '"><i class="bb-icon-f bb-icon-lock"></i></span>' : '' ) );
						$locked_class         = apply_filters( 'learndash_quiz_row_atts', ( ( isset( $bb_lesson_has_access ) && ! $bb_lesson_has_access && ! $is_sample ) || ( ! empty( $bb_available_date ) && ! $is_sample ) ? 'lms-is-locked' : 'lms-not-locked' ) );

						if ( $bb_lesson_has_access || ( ! $bb_lesson_has_access && apply_filters( 'bb_theme_ld_show_locked_lessons', true ) ) ) {
							?>
							<li class="lms-lesson-item <?php echo $lesson->ID === $post->ID ? esc_attr( 'current' ) : esc_attr( 'lms-lesson-turnover' ); ?> <?php echo esc_attr( $lesson_sample . ' ' . $locked_class ); ?> <?php echo ( ! empty( $lesson_topics ) || ! empty( $lesson_quizzes ) ) ? '' : esc_attr( 'bb-lesson-item-no-topics' ); ?>">

								<?php
								if ( isset( $sections[ $lesson->ID ] ) ) :
									learndash_get_template_part(
										'lesson/partials/section.php',
										array(
											'section'   => $sections[ $lesson->ID ],
											'course_id' => $course_id,
											'user_id'   => $user_id,
										),
										true
									);
								endif;

								if ( ! empty( $lesson_topics ) || ! empty( $lesson_quizzes ) ) :
									?>
									<span class="lms-toggle-lesson"><i class="bb-icon-f bb-icon-caret-down"></i></span>
									<?php
								endif;
								?>

								<a href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>" title="<?php echo esc_attr( $lesson->post_title ); ?>" class="bb-lesson-head flex">
									<?php
									$lesson_progress = buddyboss_theme()->learndash_helper()->learndash_get_lesson_progress( $lesson->ID, $course_id );
									$completed       = ! empty( $course_progress[ $course_id ]['lessons'][ $lesson->ID ] ) && 1 === $course_progress[ $course_id ]['lessons'][ $lesson->ID ];
									?>
									<div class="flex-1 push-left <?php echo $completed ? esc_attr( 'bb-completed-item' ) : esc_attr( 'bb-not-completed-item' ); ?>">
										<div class="bb-lesson-title"><?php echo $lesson->post_title; ?></div>
									</div>
									<?php
									if ( ! empty( $lesson_topics ) ) :
										?>
										<div class="bb-lesson-topics-count">
											<?php
											echo sprintf( esc_html__( '%s', 'buddyboss-theme' ), count( $lesson_topics ) ) . ' ' .
												_n(
													sprintf( esc_html__( '%s', 'buddyboss-theme' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
													sprintf( esc_html__( '%s', 'buddyboss-theme' ), LearnDash_Custom_Label::get_label( 'topics' ) ),
													count( $lesson_topics ),
													'buddyboss-theme'
												);
											?>
										</div>
										<?php
									endif;

									$ld_lesson     = array( 'post' => $lesson );
									$content_count = learndash_get_lesson_content_count( $ld_lesson, $course_id );

									if ( ! empty( $lesson_topics ) && count( $lesson_topics ) > 0 && $content_count['quizzes'] > 0 ) {
										?>
										<span class="bb-lesson-sidebar-ld-sep">| </span>
										<?php
									}
									if ( $content_count['quizzes'] > 0 ) :
										?>
										<div class="bb-lesson-quizzes-count">
											<?php
											echo sprintf( esc_html__( '%s', 'buddyboss-theme' ), $content_count['quizzes'] ) . ' ' .
												_n(
													sprintf( esc_html__( '%s', 'buddyboss-theme' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
													sprintf( esc_html__( '%s', 'buddyboss-theme' ), LearnDash_Custom_Label::get_label( 'quizzes' ) ),
													$content_count['quizzes'],
													'buddyboss-theme'
												);
											?>
										</div>
										<?php
									endif;
									echo $atts_access_marker;

									if ( 100 === (int) $lesson_progress['percentage'] ) {
										$lesson_progress_data_balloon = __( 'Completed', 'buddyboss-theme' );
									} elseif ( 0 === (int) $lesson_progress['percentage'] ) {
										$lesson_progress_data_balloon = __( 'Not Completed', 'buddyboss-theme' );
									} else {
										$lesson_progress_data_balloon = $lesson_progress['percentage'] . __( '% Completed', 'buddyboss-theme' );
									}
									?>

									<div class="flex align-items-center <?php echo '100' === (int) $lesson_progress['percentage'] ? esc_attr( 'bb-check-completed' ) : esc_attr( 'bb-check-not-completed' ); ?>">
										<div class="bb-lms-progress-wrap" data-balloon-pos="left" data-balloon="<?php echo esc_attr( $lesson_progress_data_balloon ); ?>">
											<?php
											if ( 100 === (int) $lesson_progress['percentage'] ) {
												?>
												<div class="i-progress i-progress-completed"><i class="bb-icon-l bb-icon-check"></i></div>
												<?php
											} else {
												?>
												<div class="bb-progress <?php echo $completed ? esc_attr( 'bb-completed' ) : esc_attr( 'bb-not-completed' ); ?>" data-percentage="<?php echo esc_attr( $lesson_progress['percentage'] ); ?>">
													<span class="bb-progress-left"><span class="bb-progress-circle"></span></span>
													<span class="bb-progress-right"><span class="bb-progress-circle"></span></span>
												</div>
												<?php
											}
											?>
										</div>
									</div>
								</a>

								<div class="lms-lesson-content" <?php echo $lesson->ID === $post->ID ? '' : 'style="display: none;"'; ?>>
									<?php
									if ( ! empty( $lesson_topics ) ) :
										?>
										<ol class="bb-type-list">
											<?php
											foreach ( $lesson_topics as $lesson_topic ) {
												$bb_topic_has_access = sfwd_lms_has_access( $lesson_topic->ID, $user_id );
												$learndash_available_date = learndash_course_step_available_date( $lesson_topic->ID, $course_id, $user_id, true );
												$attributes = learndash_get_course_step_attributes( $lesson_topic->ID, $course_id, $user_id );
												if ( $bb_topic_has_access || ( ! $bb_topic_has_access && apply_filters( 'bb_theme_ld_show_locked_topics', true ) ) ) {
													?>
													<li class="lms-topic-item <?php echo $lesson_topic->ID === $post->ID ? esc_attr( 'current' ) : ''; ?> <?php echo ( ! empty( $learndash_available_date ) ) ? 'lms-topic-is-locked' : 'lms-topic-not-locked'; ?>">
														<a class="flex bb-title bb-lms-title-wrap" href="<?php echo esc_url( get_permalink( $lesson_topic->ID ) ); ?>" title="<?php echo esc_attr( $lesson_topic->post_title ); ?>">
															<?php

															$topic_settings       = learndash_get_setting( $lesson_topic );
															$lesson_video_enabled = isset( $topic_settings['lesson_video_enabled'] ) ? $topic_settings['lesson_video_enabled'] : null;
															$completed            = ! empty( $course_progress[ $course_id ]['topics'][ $lesson->ID ][ $lesson_topic->ID ] ) && 1 === $course_progress[ $course_id ]['topics'][ $lesson->ID ][ $lesson_topic->ID ];

															if ( ! empty( $lesson_video_enabled ) ) {
																?>
																<span class="bb-lms-ico bb-lms-ico-topic"><i class="bb-icon-bl bb-icon-play"></i></span>
																<?php
															} else {
																?>
																<span class="bb-lms-ico bb-lms-ico-topic"><i class="bb-icon-l bb-icon-text"></i></span>
																<?php
															}
															?>
															<span class="flex-1 bb-lms-title <?php echo $completed ? esc_attr( 'bb-completed-item' ) : esc_attr( 'bb-not-completed-item' ); ?>"><?php echo $lesson_topic->post_title; ?></span>
															<?php
															if ( ! empty( $attributes ) ) :
																foreach ( $attributes as $attribute ) :
																	if ( $attribute['icon'] == 'ld-icon-calendar' ) :
																		?>
																		<span class="lms-topic-status-icon" data-balloon-pos="left" data-balloon="<?php echo esc_attr( $attribute['label'] ); ?>"><i class="bb-icon-f bb-icon-lock"></i></span>
																		<?php
																	endif;
																endforeach;
															endif;
															?>
															<?php
															if ( ( ! empty( $course_progress[ $course_id ]['topics'][ $lesson->ID ][ $lesson_topic->ID ] ) && 1 === $course_progress[ $course_id ]['topics'][ $lesson->ID ][ $lesson_topic->ID ] ) ) :
																?>
																<div class="bb-completed bb-lms-status" data-balloon-pos="left" data-balloon="<?php esc_attr_e( 'Completed', 'buddyboss-theme' ); ?>">
																	<div class="i-progress i-progress-completed"><i class="bb-icon-l bb-icon-check"></i></div>
																</div>
																<?php
															else :
																?>
																<div class="bb-not-completed bb-lms-status" data-balloon-pos="left" data-balloon="<?php esc_attr_e( 'Not Completed', 'buddyboss-theme' ); ?>">
																	<div class="i-progress i-progress-not-completed"><i class="bb-icon-l bb-icon-circle"></i></div>
																</div>
																<?php
															endif;
															?>
														</a>

														<?php
														$topic_quizzes = learndash_get_lesson_quiz_list( $lesson_topic->ID, get_current_user_id(), $course_id );
														if ( ! empty( $topic_quizzes ) ) :
															?>
															<ol class="lms-quiz-list">
																<?php
																foreach ( $topic_quizzes as $topic_quiz ) {
																	$bb_quiz_has_access = sfwd_lms_has_access( $topic_quiz['post']->ID, $user_id );
																	$attributes = learndash_get_course_step_attributes( $topic_quiz['post']->ID, $course_id, $user_id );
																	if ( ! empty( $attributes ) && empty( $atts ) ) :
																		foreach ( $attributes as $attribute ) :
																			$scheduled_class = $attribute['icon'] == 'ld-icon-calendar' ? 'lms-is-scheduled' : 'lms-not-scheduled';
																		endforeach;
																	endif;
																	if ( $bb_quiz_has_access || ( ! $bb_quiz_has_access && apply_filters( 'bb_theme_ld_show_locked_quizzes', true ) ) ) {
																		?>
																		<li class="lms-quiz-item <?php echo esc_attr( $topic_quiz['post']->ID == $post->ID ? esc_attr( 'current' ) : '' ); ?> <?php echo isset( $scheduled_class ) ? esc_attr( $scheduled_class ) : ''; ?>">
																			<a class="flex bb-title bb-lms-title-wrap" href="<?php echo esc_url( get_permalink( $topic_quiz['post']->ID ) ); ?>" title="<?php echo esc_attr( $topic_quiz['post']->post_title ); ?>">
																				<span class="bb-lms-ico bb-lms-ico-quiz"><i class="bb-icon-rl bb-icon-question"></i></span>
																				<span class="flex-1 bb-lms-title <?php echo learndash_is_quiz_complete( $user_id, $topic_quiz['post']->ID, $course_id ) ? esc_attr( 'bb-completed-item' ) : esc_attr( 'bb-not-completed-item' ); ?>">
																					<?php echo wp_kses_post( apply_filters( 'the_title', $topic_quiz['post']->post_title, $topic_quiz['post']->ID ) ); ?>
																				</span>
																				<?php
																				if ( isset( $scheduled_class ) && $scheduled_class == 'lms-is-scheduled' ) :
																					?>
																					<span class="lms-quiz-status-icon" data-balloon-pos="left" data-balloon="<?php echo esc_attr( $attribute['label'] ); ?>"><i class="bb-icon-f bb-icon-lock"></i></span>
																					<?php
																				endif;
																				?>
																				<?php
																				if ( learndash_is_quiz_complete( $user_id, $topic_quiz['post']->ID, $course_id ) ) :
																					?>
																					<div class="bb-completed bb-lms-status" data-balloon-pos="left" data-balloon="<?php esc_attr_e( 'Completed', 'buddyboss-theme' ); ?>">
																						<div class="i-progress i-progress-completed">
																							<i class="bb-icon-l bb-icon-check"></i>
																						</div>
																					</div>
																					<?php
																				else :
																					?>
																					<div class="bb-not-completed bb-lms-status" data-balloon-pos="left" data-balloon="<?php esc_attr_e( 'Not Completed', 'buddyboss-theme' ); ?>">
																						<div class="i-progress i-progress-not-completed">
																							<i class="bb-icon-l bb-icon-circle"></i>
																						</div>
																					</div>
																					<?php
																				endif;
																				?>
																			</a>
																		</li>
																		<?php
																	}
																}
																?>
															</ol>
															<?php
														endif;
														?>
													</li>
													<?php
												}
											}
											?>
										</ol>
										<?php
									endif;

									$lesson_quizzes = learndash_get_lesson_quiz_list( $lesson->ID, get_current_user_id(), $course_id );
									if ( ! empty( $lesson_quizzes ) ) :
										?>
										<ul class="lms-quiz-list">
											<?php
											foreach ( $lesson_quizzes as $lesson_quiz ) {
												$bb_quiz_has_access = sfwd_lms_has_access( $lesson_quiz['post']->ID, $user_id );
												$attributes = learndash_get_course_step_attributes( $lesson_quiz['post']->ID, $course_id, $user_id );
												if ( ! empty( $attributes ) && empty( $atts ) ) :
													foreach ( $attributes as $attribute ) :
														$scheduled_class = $attribute['icon'] == 'ld-icon-calendar' ? 'lms-is-scheduled' : 'lms-not-scheduled';
													endforeach;
												endif;
												if ( $bb_quiz_has_access || ( ! $bb_quiz_has_access && apply_filters( 'bb_theme_ld_show_locked_quizzes', true ) ) ) {
													?>
													<li class="lms-quiz-item <?php echo esc_attr( $lesson_quiz['post']->ID == $post->ID ? esc_attr( 'current' ) : '' ); ?> <?php echo isset( $scheduled_class ) ? esc_attr( $scheduled_class ) : ''; ?>">
														<a class="flex bb-title bb-lms-title-wrap" href="<?php echo esc_url( get_permalink( $lesson_quiz['post']->ID ) ); ?>" title="<?php echo esc_attr( $lesson_quiz['post']->post_title ); ?>">
															<span class="bb-lms-ico bb-lms-ico-quiz"><i class="bb-icon-rl bb-icon-question"></i></span>
															<span class="flex-1 bb-lms-title <?php echo learndash_is_quiz_complete( $user_id, $lesson_quiz['post']->ID, $course_id ) ? esc_attr( 'bb-completed-item' ) : esc_attr( 'bb-not-completed-item' ); ?>"><?php echo $lesson_quiz['post']->post_title; ?></span>
															<?php
															if ( isset( $scheduled_class ) && $scheduled_class == 'lms-is-scheduled' ) :
																?>
																<span class="lms-quiz-status-icon" data-balloon-pos="left" data-balloon="<?php echo esc_attr( $attribute['label'] ); ?>"><i class="bb-icon-f bb-icon-lock"></i></span>
																<?php
															endif;
															?>
															<?php
															if ( learndash_is_quiz_complete( $user_id, $lesson_quiz['post']->ID, $course_id ) ) :
																?>
																<div class="bb-completed bb-lms-status" data-balloon-pos="left" data-balloon="<?php esc_attr_e( 'Completed', 'buddyboss-theme' ); ?>">
																	<div class="i-progress i-progress-completed"><i class="bb-icon-l bb-icon-check"></i></div>
																</div>
																<?php
															else :
																?>
																<div class="bb-not-completed bb-lms-status" data-balloon-pos="left" data-balloon="<?php esc_attr_e( 'Not Completed', 'buddyboss-theme' ); ?>">
																	<div class="i-progress i-progress-not-completed"><i class="bb-icon-l bb-icon-circle"></i></div>
																</div>
																<?php
															endif;
															?>
														</a>
													</li>
													<?php
												}
											}
											?>
										</ul>
									<?php endif; ?>
								</div><?php /*lms-lesson-content*/ ?>
							</li>
							<?php
						}
					}
					?>
				</ol>
			<?php endif; ?>
		</div>

		<?php
		$course_quizzes = learndash_get_course_quiz_list( $course_id );
		if ( ! empty( $course_quizzes ) ) :
			?>
			<div class="lms-course-quizzes-list">
				<h4 class="lms-course-quizzes-heading"><?php echo LearnDash_Custom_Label::get_label( 'quizzes' ); ?></h4>
				<ul class="lms-quiz-list bb-type-list">
					<?php
					foreach ( $course_quizzes as $course_quiz ) {

						$is_sample          = ( isset( $lesson->sample ) ? $lesson->sample : false );
						$bb_quiz_has_access = sfwd_lms_has_access( $course_quiz['post']->ID, $user_id );
						$atts               = apply_filters( 'learndash_quiz_row_atts', ( isset( $bb_quiz_has_access ) && ! $bb_quiz_has_access && ! $is_sample ? 'data-balloon-pos="up" data-balloon="' . __( "You don't currently have access to this content", 'buddyboss-theme' ) . '"' : '' ) );
						$atts_access_marker = apply_filters( 'learndash_quiz_row_atts', ( isset( $bb_quiz_has_access ) && ! $bb_quiz_has_access && ! $is_sample ? '<span class="lms-is-locked-ico"><i class="bb-icon-f bb-icon-lock"></i></span>' : '' ) );
						$locked_class       = apply_filters( 'learndash_quiz_row_atts', ( isset( $bb_quiz_has_access ) && ! $bb_quiz_has_access && ! $is_sample ? 'lms-is-locked' : 'lms-not-locked' ) );
						$attributes         = learndash_get_course_step_attributes( $course_quiz['post']->ID, $course_id, $user_id );
						if ( ! empty( $attributes ) && empty( $atts ) ) :
							foreach ( $attributes as $attribute ) :
								$scheduled_class = $attribute['icon'] == 'ld-icon-calendar' ? 'lms-is-scheduled' : 'lms-not-scheduled';
							endforeach;
						endif;

						?>
						<li class="lms-quiz-item <?php echo $course_quiz['post']->ID == $post->ID ? esc_attr( 'current' ) : ''; ?> <?php echo esc_attr( $locked_class ); ?> <?php echo isset( $scheduled_class ) ? esc_attr( $scheduled_class ) : ''; ?>">
							<a class="flex bb-title bb-lms-title-wrap" href="<?php echo esc_url( get_permalink( $course_quiz['post']->ID ) ); ?>" title="<?php echo esc_attr( $course_quiz['post']->post_title ); ?>">
								<span class="bb-lms-ico bb-lms-ico-quiz"><i class="bb-icon-rl bb-icon-question"></i></span>
								<span class="flex-1 push-left bb-lms-title <?php echo learndash_is_quiz_complete( $user_id, $course_quiz['post']->ID, $course_id ) ? esc_attr( 'bb-completed-item' ) : esc_attr( 'bb-not-completed-item' ); ?>">
									<span class="bb-quiz-title"><?php echo $course_quiz['post']->post_title; ?></span>
									<?php echo $atts_access_marker; ?>
								</span>
								<?php								
								if ( isset( $scheduled_class ) && $scheduled_class == 'lms-is-scheduled' ) :
									?>
									<span class="lms-quiz-status-icon" data-balloon-pos="left" data-balloon="<?php echo esc_attr( $attribute['label'] ); ?>"><i class="bb-icon-f bb-icon-lock"></i></span>
									<?php
								endif;
								?>
								<?php
								if ( learndash_is_quiz_complete( $user_id, $course_quiz['post']->ID, $course_id ) ) :
									?>
									<div class="bb-completed bb-lms-status" data-balloon-pos="left" data-balloon="<?php esc_attr_e( 'Completed', 'buddyboss-theme' ); ?>">
										<div class="i-progress i-progress-completed"><i class="bb-icon-check"></i></div>
									</div>
									<?php
								else :
									?>
									<div class="bb-not-completed bb-lms-status" data-balloon-pos="left" data-balloon="<?php esc_attr_e( 'Not Completed', 'buddyboss-theme' ); ?>">
										<div class="i-progress i-progress-not-completed"><i class="bb-icon-l bb-icon-circle"></i>
										</div>
									</div>
									<?php
								endif;
								?>
							</a>
						</li>
					<?php } ?>
				</ul>
			</div>
			<?php
		endif;

		if ( buddyboss_theme_get_option( 'learndash_course_participants', null, true ) && ! empty( $members ) ) :
			?>
			<div class="lms-course-members-list">
				<h4 class="lms-course-sidebar-heading">
					<?php esc_html_e( 'Participants', 'buddyboss-theme' ); ?>
					<span class="lms-count"><?php echo esc_html( $members_count ); ?></span>
				</h4>
				<input type="hidden" name="buddyboss_theme_learndash_course_participants_course_id" id="buddyboss_theme_learndash_course_participants_course_id" value="<?php echo esc_attr( $course_id ); ?>">
				<div class="bb-course-member-wrap">

					<ul class="course-members-list">
						<?php
						$count = 0;
						foreach ( $members as $course_member ) :
							if ( $count > 4 ) {
								break;
							}
							?>
							<li>

								<?php if ( class_exists( 'BuddyPress' ) ) { ?>
								<a href="<?php echo esc_url( bp_core_get_user_domain( (int) $course_member ) ); ?>">
									<?php } ?>
									<img class="round" src="<?php echo esc_url( get_avatar_url( (int) $course_member, array( 'size' => 96 ) ) ); ?>" alt=""/>
									<?php
									if ( class_exists( 'BuddyPress' ) ) {
										?>
										<span><?php echo bp_core_get_user_displayname( (int) $course_member ); ?></span>
										<?php
									} else {
										$course_member = get_userdata( (int) $course_member );
										?>
										<span><?php echo $course_member->display_name; ?></span>
										<?php
									}
									if ( class_exists( 'BuddyPress' ) ) {
										?>
								</a>
										<?php
									}
									?>
							</li>
							<?php
							$count ++;
						endforeach;
						?>
					</ul>

					<ul class="course-members-list course-members-list-extra">
					</ul>
					<?php
					if ( $members_count > 5 ) {
						?>
						<a href="javascript:void(0);" class="list-members-extra lme-more"><span class="members-count-g"></span> <?php esc_html_e( 'Show more', 'buddyboss-theme' ); ?><i class="bb-icon-l bb-icon-angle-down"></i></a>
						<?php
					}
					?>
				</div>
			</div>
			<?php
		endif;

		if ( is_active_sidebar( 'learndash_lesson_sidebar' ) ) {
			?>
			<div class="ld-sidebar-widgets">
				<ul>
					<?php dynamic_sidebar( 'learndash_lesson_sidebar' ); ?>
				</ul>
			</div>
			<?php
		}
		?>
	</div>
</div>
