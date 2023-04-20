<?php
/**
 * Displays a course
 *
 * Available Variables:
 * $course_id       : (int) ID of the course
 * $course      : (object) Post object of the course
 * $course_settings : (array) Settings specific to current course
 *
 * $courses_options : Options/Settings as configured on Course Options page
 * $lessons_options : Options/Settings as configured on Lessons Options page
 * $quizzes_options : Options/Settings as configured on Quiz Options page
 *
 * $user_id         : Current User ID
 * $logged_in       : User is logged in
 * $current_user    : (object) Currently logged in user object
 *
 * $course_status   : Course Status
 * $has_access  : User has access to course or is enrolled.
 * $materials       : Course Materials
 * $has_course_content      : Course has course content
 * $lessons         : Lessons Array
 * $quizzes         : Quizzes Array
 * $lesson_progression_enabled  : (true/false)
 * $has_topics      : (true/false)
 * $lesson_topics   : (array) lessons topics
 *
 * @since 2.1.0
 *
 * @package LearnDash\Templates\Legacy\Course
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $course_pager_results;

/**
 * Display course status
 */
?>
<?php if ( $logged_in ) : ?>
	<span id="learndash_course_status">
		<b>
		<?php
			// translators: Course Status Label.
			printf( esc_html_x( '%s Status:', 'Course Status Label', 'learndash' ), esc_attr( LearnDash_Custom_Label::get_label( 'course' ) ) );
		?>
			</b>
			<?php
			echo esc_attr( $course_status );
			?>
		<br />
	</span>
	<br />

	<?php
	/**
	 * Filters the content to be echoed after the course status section of the course template output.
	 *
	 * @since 2.3.0
	 * See https://developers.learndash.com/hook/ld_after_course_status_template_container/ for example use of this filter.
	 *
	 * @param string $content             Custom content showed after the course status section. Can be empty.
	 * @param string $course_status_index Course status index from the course status label
	 * @param int    $course_id           Course ID.
	 * @param int    $user_id             User ID.
	 */
	echo apply_filters( 'ld_after_course_status_template_container', '', learndash_course_status_idx( $course_status ), $course_id, $user_id );
	?>

	<?php if ( ! empty( $course_certficate_link ) ) : ?>
		<div id="learndash_course_certificate" class="learndash_course_certificate">
			<a href='<?php echo esc_url( $course_certficate_link ); ?>' class="btn-blue" target="_blank">
			<?php
			/** This filter is documented in includes/ld-certificates.php */
			echo apply_filters( 'ld_certificate_link_label', esc_html__( 'PRINT YOUR CERTIFICATE', 'learndash' ), $user_id, $post->ID );
			?>
			</a>
		</div>
		<br />
	<?php endif; ?>
<?php endif; ?>

<div class="learndash_content"><?php echo $content; ?></div>

<?php if ( ! $has_access ) : ?>
	<?php
	/**
	 * Fires before the Course Payment Button.
	 *
	 * @since 2.5.8
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	do_action( 'learndash-course-payment-buttons-before', $course_id, $user_id );
	?>
	<?php echo learndash_payment_buttons( $post ); ?>
	<?php
	/**
	 * Fires after the course payment button.
	 *
	 * @since 2.5.8
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	do_action( 'learndash-course-payment-buttons-after', $course_id, $user_id );
	?>
<?php endif; ?>

<?php if ( ( isset( $materials ) ) && ( ! empty( $materials ) ) ) : ?>
	<div id="learndash_course_materials" class="learndash_course_materials">
		<h4>
		<?php
			// translators: placeholder: Course.
			printf( esc_html_x( '%s Materials', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) );
		?>
		</h4>
		<p><?php echo $materials; ?></p>
	</div>
<?php endif; ?>

<?php if ( $has_course_content ) : ?>
	<?php
		$show_course_content = true;
	if ( ! $has_access ) :
		if ( 'on' === $course_meta['sfwd-courses_course_disable_content_table'] ) :
			$show_course_content = false;
			endif;
		endif;

	if ( $show_course_content ) :
		?>
	<div id="learndash_course_content" class="learndash_course_content">
		<h4 id="learndash_course_content_title">
			<?php
			// translators: placeholder: Course.
			printf( esc_html_x( '%s Content', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) );
			?>
		</h4>

		<?php
		/**
		 * Display lesson list
		 */
		?>
		<?php if ( ! empty( $lessons ) ) : ?>

			<?php if ( $has_topics ) : ?>
				<div class="expand_collapse">
					<a href="#" onClick='jQuery("#learndash_post_<?php echo $course_id; ?> .learndash_topic_dots").slideDown(); return false;'><?php esc_html_e( 'Expand All', 'learndash' ); ?></a> | <a href="#" onClick='jQuery("#learndash_post_<?php echo esc_attr( $course_id ); ?> .learndash_topic_dots").slideUp(); return false;'><?php esc_html_e( 'Collapse All', 'learndash' ); ?></a>
				</div>
				<?php
				/** This filter is documented in themes/ld30/templates/course.php */
				if ( apply_filters( 'learndash_course_steps_expand_all', false, $course_id, 'course_lessons_listing_main' ) ) {
					?>
					<script>
						jQuery( function(){
							setTimeout(function(){
								jQuery("#learndash_post_<?php echo $course_id; ?> .learndash_topic_dots").slideDown();
							}, 1000);
						});
					</script>
				<?php } ?>
			<?php endif; ?>

			<div id="learndash_lessons" class="learndash_lessons">

				<div id="lesson_heading">
						<span><?php echo LearnDash_Custom_Label::get_label( 'lessons' ); ?></span>
					<span class="right"><?php esc_html_e( 'Status', 'learndash' ); ?></span>
				</div>

				<div id="lessons_list" class="lessons_list">

					<?php foreach ( $lessons as $lesson ) : ?>
						<div class='post-<?php echo esc_attr( $lesson['post']->ID ); ?> <?php echo esc_attr( $lesson['sample'] ); ?>'>

							<div class="list-count">
								<?php echo $lesson['sno']; ?>
							</div>

							<h4>
								<a class="<?php echo esc_attr( $lesson['status'] ); ?>" href="<?php echo esc_url( learndash_get_step_permalink( $lesson['post']->ID, $course_id ) ); ?>"><?php echo apply_filters( 'the_title', $lesson['post']->post_title, $lesson['post']->ID ); ?></a>


								<?php
								/**
								 * Not available message for drip feeding lessons
								 */
								?>
								<?php if ( ! empty( $lesson['lesson_access_from'] ) ) : ?>
									<?php
										SFWD_LMS::get_template(
											'learndash_course_lesson_not_available',
											array(
												'user_id' => $user_id,
												'course_id' => learndash_get_course_id( $lesson['post']->ID ),
												'lesson_id' => $lesson['post']->ID,
												'lesson_access_from_int' => $lesson['lesson_access_from'],
												'lesson_access_from_date' => learndash_adjust_date_time_display( $lesson['lesson_access_from'] ),
												'context' => 'course',
											),
											true
										);
									?>
								<?php endif; ?>


								<?php
								/**
								 * Lesson Topics
								 */
								?>
								<?php
									$paged_values = learndash_get_lesson_topic_paged_values();
								?>
								<?php $topics = @$lesson_topics[ $lesson['post']->ID ]; ?>

								<?php if ( ! empty( $topics ) ) : ?>
									<div id='learndash_topic_dots-<?php echo esc_attr( $lesson['post']->ID ); ?>' class="learndash_topic_dots type-list"
																			 <?php
																				if ( $paged_values['lesson'] == $lesson['post']->ID ) {
																					echo ' style="display:block;"';
																				}
																				?>
									>
										<ul>
											<?php $odd_class = ''; ?>
											<?php foreach ( $topics as $key => $topic ) : ?>
												<?php $odd_class = empty( $odd_class ) ? 'nth-of-type-odd' : ''; ?>
												<?php $completed_class = empty( $topic->completed ) ? 'topic-notcompleted' : 'topic-completed'; ?>
												<li class='<?php echo esc_attr( $odd_class ); ?>'>
													<span class="topic_item">
														<a class='<?php echo esc_attr( $completed_class ); ?>' href='<?php echo esc_url( learndash_get_step_permalink( $topic->ID, $course_id ) ); ?>' title='<?php echo esc_html( $topic->post_title ); ?>'>
															<span><?php echo apply_filters( 'the_title', $topic->post_title, $topic->ID ); ?></span>
														</a>
													</span>
												</li>
											<?php endforeach; ?>
										</ul>
										<?php
										if ( isset( $course_pager_results[ $lesson['post']->ID ]['pager'] ) ) {
											echo SFWD_LMS::get_template(
												'learndash_pager.php',
												array(
													'pager_results' => $course_pager_results[ $lesson['post']->ID ]['pager'],
													'pager_context' => 'course_topics',
													'href_query_arg' => 'ld-topic-page',
													'href_val_prefix' => $lesson['post']->ID . '-',
												)
											);
										}
										?>
									</div>
								<?php endif; ?>

							</h4>
						</div>
					<?php endforeach; ?>

				</div>
			</div>
			<?php
			if ( isset( $course_pager_results['pager'] ) ) {
				echo SFWD_LMS::get_template(
					'learndash_pager.php',
					array(
						'pager_results' => $course_pager_results['pager'],
						'pager_context' => 'course_lessons',
					)
				);
			}
			?>
		<?php endif; ?>

		<?php
		if ( ! empty( $lessons ) ) {
			if ( ( isset( $course_pager_results['pager'] ) ) && ( ! empty( $course_pager_results['pager'] ) ) ) {
				if ( $course_pager_results['pager']['paged'] == $course_pager_results['pager']['total_pages'] ) {
					$show_course_quizzes = true;
				} else {
					$show_course_quizzes = false;
				}
			} else {
				$show_course_quizzes = true;
			}
		} else {
			$show_course_quizzes = true;
		}
		?>
		<?php
		/**
		 * Display quiz list
		 */
		?>
		<?php
		if ( $show_course_quizzes == true ) {
			if ( ! empty( $quizzes ) ) {
				?>
					<div id="learndash_quizzes" class="learndash_quizzes">
						<div id="quiz_heading">
								<span><?php echo LearnDash_Custom_Label::get_label( 'quizzes' ); ?></span><span class="right"><?php esc_html_e( 'Status', 'learndash' ); ?></span>
						</div>
						<div id="quiz_list" class=“quiz_list”>

							<?php foreach ( $quizzes as $quiz ) : ?>
								<div id='post-<?php echo esc_attr( $quiz['post']->ID ); ?>' class='<?php echo esc_attr( $quiz['sample'] ); ?>'>
									<div class="list-count"><?php echo $quiz['sno']; ?></div>
									<h4>
										<a class='<?php echo esc_attr( $quiz['status'] ); ?>' href='<?php echo esc_url( learndash_get_step_permalink( $quiz['post']->ID, $course_id ) ); ?>'><?php echo apply_filters( 'the_title', $quiz['post']->post_title, $quiz['post']->ID ); ?></a>
									</h4>
								</div>
							<?php endforeach; ?>

						</div>
					</div>
				<?php
			}
		}
		?>
	</div>
		<?php endif; ?>
<?php endif; ?>
