<?php
/**
 * Learndash ProPanel Activity Template.
 *
 * @since 4.17.0
 * @version 4.20.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

use LearnDash\Core\Utilities\Cast;

if ( current_user_can( 'edit_user', $activity->user_id ) ) {
	$user_link = get_edit_user_link( $activity->user_id ) . '#ld_course_info';
} else {
	$user_link = '#';
}

if ( ( ! empty( $activity->activity_completed ) ) && ( ! empty( $activity->activity_started ) ) ) {
	$activity_diff_completed = learndash_get_activity_human_time_diff( $activity->activity_started, $activity->activity_completed, 1 );
} else {
	$activity_diff_completed = 0;
}

if ( ! empty( $activity_diff_completed ) ) {
	$activity_abbr_label_completed = __( 'Completed Date (Duration)', 'learndash' );
} else {
	$activity_abbr_label_completed = __( 'Completed Date', 'learndash' );
}

if ( ! empty( $activity->activity_started ) ) {
	$activity_diff_started = learndash_get_activity_human_time_diff( $activity->activity_started, time(), 1 );
} else {
	$activity_diff_started = 0;
}

if ( ! empty( $activity_diff_started ) ) {
	$activity_abbr_label_started = __( 'Started Date (Duration)', 'learndash' );
} else {
	$activity_abbr_label_started = __( 'Started Date', 'learndash' );
}

?>
<?php if ( 'quiz' == $activity->activity_type ) : ?>
	<div class="activity-item quiz">
		<div class="header">
			<span class="user"><a href="<?php echo esc_attr( $user_link ); ?>" title="<?php esc_attr_e( 'See User Progress', 'learndash' ); ?>"><?php echo esc_html( $activity->user_display_name ); ?></a></span>

			<?php if ( ! empty( $activity->activity_completed ) ) { ?>
				<abbr class="date" title="<?php echo esc_attr( $activity_abbr_label_completed ); ?>">
													<?php
														echo esc_html( $activity->activity_completed_formatted );
													if ( ! empty( $activity_diff_completed ) ) {
														?>
						(<i><?php echo esc_html( $activity_diff_completed ); ?></i>)
															<?php
													}
													?>
				</abbr>
			<?php } ?>
		</div>
		<div class="content">
			<strong>
			<?php
			printf(
				// translators: Quiz completed.
				esc_html_x( '%s Completed:', 'Quiz Completed:', 'learndash' ),
				esc_html( LearnDash_Custom_Label::get_label( 'quiz' ) )
			);
			?>
				</strong><strong><a href="<?php echo esc_attr( learndash_get_step_permalink( $activity->post_id, $activity->activity_course_id ) ); ?>" class="link"> <?php echo esc_html( $activity->post_title ); ?></a></strong>
				<?php

				edit_post_link(
					sprintf(
						// translators: placeholder: Title of current post
						_x( ' (edit<span class="screen-reader-text"> "%s"</span>)', 'placeholder: Title of current post', 'learndash' ),
						get_the_title( $activity->post_id )
					),
					'<span class="ld-propanel-edit-link edit-link">',
					'</span>',
					$activity->post_id
				);
				?>
			<br/>

			<?php $course = $this->get_activity_course( $activity ); ?>

			<?php if ( $course instanceof WP_Post ) : ?>
				<strong>
					<?php
					printf(
						// translators: Course.
						esc_html_x( '%s:', 'Course:', 'learndash' ),
						esc_html( LearnDash_Custom_Label::get_label( 'course' ) )
					);
					?>
				</strong>

				<strong>
					<a href="<?php echo esc_attr( Cast::to_string( get_permalink( $course->ID ) ) ); ?>" class="link">
						<?php echo esc_html( $course->post_title ); ?>
					</a>
				</strong>

				<?php
				edit_post_link(
					sprintf(
						// translators: placeholder: Title of the course.
						_x( ' (edit<span class="screen-reader-text"> "%s"</span>)', 'placeholder: Title of current post', 'learndash' ),
						get_the_title( $course->ID )
					),
					'<span class="ld-propanel-edit-link edit-link">',
					'</span>',
					$course->ID
				);
				?>
				<br/>
			<?php endif; ?>

			<?php if ( $this->quiz_activity_is_pending( $activity ) ) : ?>
				<strong><?php esc_html_e( 'Result:', 'learndash' ); ?> </strong><?php esc_html_e( 'Pending', 'learndash' ); ?><br/>
			<?php else : ?>
				<strong>
					<?php esc_html_e( 'Result:', 'learndash' ); ?>
				</strong>
				<?php
				$this->quiz_activity_is_passing( $activity )
					? esc_html_e( 'Passed', 'learndash' )
					: esc_html_e( 'Failed', 'learndash' );
				?>
				<br />

				<?php $quiz_statistics_link = $this->get_quiz_statistics_link( $activity ); ?>
				<?php if ( ! empty( $quiz_statistics_link ) ) : ?>
					<strong>
						<?php esc_html_e( 'Statistics:', 'learndash' ); ?>
					</strong>
					<?php echo ' ' . wp_kses_post( $quiz_statistics_link ); ?>
					<br />
				<?php endif; ?>
			<?php endif; ?>

			<?php
			/*
			?>
			<strong><?php esc_html_e( 'Score:', 'learndash' ); ?> </strong><?php printf( '%d%% (%d/%d)', $this->quiz_activity_score_percentage( $activity ), $this->quiz_activity_awarded_score( $activity ), $this->quiz_activity_total_score( $activity ) ); ?>
			<?php */
			?>
			<strong><?php esc_html_e( 'Points:', 'learndash' ); ?> </strong>
			<?php
			printf(
				'%d%% (%d/%d)',
				esc_html( $this->quiz_activity_points_percentage( $activity ) ),
				esc_html( $this->quiz_activity_awarded_points( $activity ) ),
				esc_html( $this->quiz_activity_total_points( $activity ) )
			);
			?>
		</div>
	</div>

<?php endif; ?>



<?php if ( 'course' == $activity->activity_type ) : ?>

	<div class="activity-item course">
		<div class="header">
			<span class="user"><a href="<?php echo esc_attr( $user_link ); ?>" title="<?php esc_attr_e( 'See User Progress', 'learndash' ); ?>"><?php echo esc_html( $activity->user_display_name ); ?></a></span>

			<?php if ( ! empty( $activity->activity_completed ) ) { ?>
				<abbr class="date" title="<?php echo esc_attr( $activity_abbr_label_completed ); ?>">
													<?php
														echo esc_html( $activity->activity_completed_formatted );
													if ( ! empty( $activity_diff_completed ) ) {
														?>
						(<i><?php echo esc_html( $activity_diff_completed ); ?></i>)
															<?php
													}
													?>
				</abbr>
			<?php } ?>
		</div>

		<div class="content">
			<strong>
			<?php
			printf(
				// translators: Course
				esc_html_x( '%s:', 'Course', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'course' )
			);
			?>
				</strong> <strong><a href="<?php echo esc_attr( learndash_get_step_permalink( $activity->post_id, $activity->activity_course_id ) ); ?>" class="link"><?php echo esc_html( $activity->post_title ); ?></a></strong>
				<?php

				edit_post_link(
					sprintf(
						// translators: placeholder: Title of current post
						_x( ' (edit<span class="screen-reader-text"> "%s"</span>)', 'placeholder: Title of current post', 'learndash' ),
						get_the_title( $activity->post_id )
					),
					'<span class="ld-propanel-edit-link edit-link">',
					'</span>',
					$activity->post_id
				);
				?>
			<br/>

			<?php
			/*
			?>
			<?php if ( !empty( $activity_steps_total ) ) { ?>
				<strong><?php esc_html_e( 'Result:', 'learndash' ); ?> </strong><?php printf(
					// translators: placeholders: steps completed, steps total.
					esc_html_x( 'Completed %1$d out of %2$d steps', 'placeholders: steps completed, steps total', 'learndash' ),
					$activity_steps_completed, $activity_steps_total
				); ?>
			<?php } ?>
			<?php */
			?>
		</div>
	</div>

<?php endif; ?>

<?php if ( 'access' == $activity->activity_type ) : ?>

	<div class="activity-item course">
		<div class="header">
			<span class="user"><a href="<?php echo esc_attr( $user_link ); ?>" title="<?php esc_attr_e( 'See User Progress', 'learndash' ); ?>"><?php echo esc_html( $activity->user_display_name ); ?></a></span>
			<?php if ( ! empty( $activity->activity_completed ) ) { ?>
				<abbr class="date" title="<?php echo esc_attr( $activity_abbr_label_completed ); ?>">
													<?php
														echo esc_html( $activity->activity_started_formatted );
													if ( ! empty( $activity_diff_completed ) ) {
														?>
						(<i><?php echo esc_html( $activity_diff_completed ); ?></i>)
															<?php
													}
													?>
				</abbr>
			<?php } elseif ( ! empty( $activity->activity_started ) ) { ?>
				<abbr class="date" title="<?php echo esc_attr( $activity_abbr_label_started ); ?>">
													<?php
														echo esc_html( $activity->activity_started_formatted );

													if ( ! empty( $activity_diff_started ) ) {
														?>
						(<i><?php echo esc_html( $activity_diff_started ); ?></i>)
															<?php
													}
													?>
				</abbr>
			<?php } ?>
		</div>

		<div class="content">
			<strong>
			<?php
			printf(
				// translators: Gained Course Access.
				esc_html_x( 'Gained %s Access:', 'Gained Course Access', 'learndash' ),
				esc_html( LearnDash_Custom_Label::get_label( 'course' ) )
			);
			?>
				</strong> <strong><a href="<?php echo esc_attr( learndash_get_step_permalink( $activity->post_id, $activity->activity_course_id ) ); ?>" class="link"><?php echo esc_html( $activity->post_title ); ?></a></strong><br/>
		</div>
	</div>

<?php endif; ?>



<?php if ( 'lesson' == $activity->activity_type ) : ?>

		<div class="activity-item lesson">
			<div class="header">
				<span class="user"><a href="<?php echo esc_attr( $user_link ); ?>" title="<?php esc_attr_e( 'See User Progress', 'learndash' ); ?>"><?php echo esc_html( $activity->user_display_name ); ?></a></span>
				<?php if ( ! empty( $activity->activity_completed ) ) { ?>
					<abbr class="date" title="<?php echo esc_attr( $activity_abbr_label_completed ); ?>">
														<?php
															echo esc_html( $activity->activity_completed_formatted );
														if ( ! empty( $activity_diff_completed ) ) {
															?>
							(<i><?php echo esc_html( $activity_diff_completed ); ?></i>)
																<?php
														}
														?>
					</abbr>
				<?php } elseif ( ! empty( $activity->activity_started ) ) { ?>
					<abbr class="date" title="<?php echo esc_attr( $activity_abbr_label_started ); ?>">
														<?php
															echo esc_html( $activity->activity_started_formatted );

														if ( ! empty( $activity_diff_started ) ) {
															?>
							(<i><?php echo esc_html( $activity_diff_started ); ?></i>)
																<?php
														}
														?>
					</abbr>
				<?php } ?>
			</div>

			<div class="content">
				<strong>
				<?php
				printf(
					// translators: Lesson.
					esc_html_x( '%s:', 'Lesson:', 'learndash' ),
					esc_html( LearnDash_Custom_Label::get_label( 'lesson' ) )
				);
				?>
					</strong><strong><a href="<?php echo esc_attr( learndash_get_step_permalink( $activity->post_id, $activity->activity_course_id ) ); ?>" class="link"><?php echo esc_html( $activity->post_title ); ?></a></strong>
					<?php
					edit_post_link(
						sprintf(
							// translators: placeholder: Title of current post
							_x( ' (edit<span class="screen-reader-text"> "%s"</span>)', 'placeholder: Title of current post', 'learndash' ),
							get_the_title( $activity->post_id )
						),
						'<span class="ld-propanel-edit-link edit-link">',
						'</span>',
						$activity->post_id
					);
					?>
				</br>

				<?php $course = $this->get_activity_course( $activity ); ?>

				<?php if ( $course instanceof WP_Post ) : ?>
					<strong>
						<?php
						printf(
							// translators: Course.
							esc_html_x( '%s:', 'Course', 'learndash' ),
							esc_html( LearnDash_Custom_Label::get_label( 'course' ) )
						);
						?>
					</strong>

					<strong>
						<a href="<?php echo esc_attr( Cast::to_string( get_permalink( $course->ID ) ) ); ?>" class="link">
							<?php echo esc_html( $course->post_title ); ?>
						</a>
					</strong>

					<?php
					edit_post_link(
						sprintf(
							// translators: placeholder: Title of the course.
							_x( ' (edit<span class="screen-reader-text"> "%s"</span>)', 'placeholder: Title of current post', 'learndash' ),
							get_the_title( $course->ID )
						),
						'<span class="ld-propanel-edit-link edit-link">',
						'</span>',
						$course->ID
					);
					?>
					<br/>
				<?php endif; ?>

				<?php
				/*
				?>
				<?php if ( !empty( $activity_steps_total ) ) { ?>
					<strong><?php esc_html_e( 'Result:', 'learndash' ); ?> </strong><?php printf(
						// translators: placeholders: steps completed, steps total.
						esc_html_x( 'Completed %1$d out of %2$d steps', 'placeholders: steps completed, steps total', 'learndash' ),
						$activity_steps_completed, $activity_steps_total
					); ?>
				<?php } ?>
				<?php */
				?>
			</div>
		</div>

<?php endif; ?>



<?php if ( 'topic' == $activity->activity_type ) : ?>
	<div class="activity-item topic">
		<div class="header">
			<span class="user"><a href="<?php echo esc_attr( $user_link ); ?>" title="<?php esc_attr_e( 'See User Progress', 'learndash' ); ?>"><?php echo esc_html( $activity->user_display_name ); ?></a></span>
			<?php if ( ! empty( $activity->activity_completed ) ) { ?>
				<abbr class="date" title="<?php echo esc_attr( $activity_abbr_label_completed ); ?>">
													<?php
														echo esc_html( $activity->activity_completed_formatted );
													if ( ! empty( $activity_diff_completed ) ) {
														?>
						(<i><?php echo esc_html( $activity_diff_completed ); ?></i>)
															<?php
													}
													?>
				</abbr>
			<?php } elseif ( ! empty( $activity->activity_started ) ) { ?>
				<abbr class="date" title="<?php echo esc_attr( $activity_abbr_label_started ); ?>">
													<?php
														echo esc_html( $activity->activity_started_formatted );

													if ( ! empty( $activity_diff_started ) ) {
														?>
						(<i><?php echo esc_html( $activity_diff_started ); ?></i>)
															<?php
													}
													?>
				</abbr>
			<?php } ?>
		</div>

		<div class="content">
			<strong>
			<?php
			printf(
				// translators: Topic.
				esc_html_x( '%s:', 'Topic', 'learndash' ),
				esc_html( LearnDash_Custom_Label::get_label( 'topic' ) )
			);
			?>
				</strong><strong><a href="<?php echo esc_attr( learndash_get_step_permalink( $activity->post_id, $activity->activity_course_id ) ); ?>" class="link"><?php echo esc_html( $activity->post_title ); ?></a></strong>
				<?php
					edit_post_link(
						sprintf(
							// translators: placeholder: Title of current post
							_x( ' (edit<span class="screen-reader-text"> "%s"</span>)', 'placeholder: Title of current post', 'learndash' ),
							get_the_title( $activity->post_id )
						),
						'<span class="ld-propanel-edit-link edit-link">',
						'</span>',
						$activity->post_id
					);
				?>
				</br>

			<?php $course = $this->get_activity_course( $activity ); ?>

			<?php if ( $course instanceof WP_Post ) : ?>
				<strong>
					<?php
					printf(
						// translators: Course.
						esc_html_x( '%s:', 'Course:', 'learndash' ),
						esc_html( LearnDash_Custom_Label::get_label( 'course' ) )
					);
					?>
				</strong>

				<strong>
					<a href="<?php echo esc_attr( Cast::to_string( get_permalink( $course->ID ) ) ); ?>" class="link">
						<?php echo esc_html( $course->post_title ); ?>
					</a>
				</strong>

				<?php
				edit_post_link(
					sprintf(
						// translators: placeholder: Title of the course.
						_x( ' (edit<span class="screen-reader-text"> "%s"</span>)', 'placeholder: Title of current post', 'learndash' ),
						get_the_title( $course->ID )
					),
					'<span class="ld-propanel-edit-link edit-link">',
					'</span>',
					$course->ID
				);
				?>
				<br/>
			<?php endif; ?>

			<?php
			/*
			?>
			<?php if ( !empty( $activity_steps_total ) ) { ?>
				<strong><?php esc_html_e( 'Result:', 'learndash' ); ?> </strong><?php printf(
					// translators: placeholders: steps completed, steps total.
					esc_html_x( 'Completed %1$d out of %2$d steps', 'placeholders: steps completed, steps total', 'learndash' ),
					$activity_steps_completed, $activity_steps_total
					); ?>
			<?php } ?>
			<?php */
			?>
		</div>
	</div>

<?php endif; ?>


<?php if ( is_null( $activity->activity_type ) ) : ?>

	<div class="activity-item not-started">
		<div class="header">
			<span class="user"><a href="<?php echo esc_attr( $user_link ); ?>" title="<?php esc_attr_e( 'See User Progress', 'learndash' ); ?>"><?php echo esc_html( $activity->user_display_name ); ?></a></span>
		</div>

		<div class="content">
			<?php $course = $this->get_activity_course( $activity ); ?>

			<?php if ( $course instanceof WP_Post ) : ?>
				<strong>
					<?php
					printf(
						// Translators: Course.
						esc_html_x( '%s:', 'Course:', 'learndash' ),
						esc_html( LearnDash_Custom_Label::get_label( 'course' ) )
					);
					?>
				</strong>

				<strong>
					<a href="<?php echo esc_attr( Cast::to_string( get_permalink( $course->ID ) ) ); ?>" class="link">
						<?php echo esc_html( $course->post_title ); ?>
					</a>
				</strong>

				<?php
				edit_post_link(
					sprintf(
						// translators: placeholder: Title of the course.
						_x( ' (edit<span class="screen-reader-text"> "%s"</span>)', 'placeholder: Title of current post', 'learndash' ),
						get_the_title( $course->ID )
					),
					'<span class="ld-propanel-edit-link edit-link">',
					'</span>',
					$course->ID
				);
				?>
				<br/>
			<?php endif; ?>

			<strong><?php esc_html_e( 'Result:', 'learndash' ); ?> </strong><?php esc_html_e( 'Not Started', 'learndash' ); ?>
		</div>
	</div>

<?php endif; ?>
