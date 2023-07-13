<?php
/**
 * LearnDash LD30 Displays a quiz listing
 *
 * Available Variables:
 *
 * $course_id        : (int) ID of Course
 * $lesson_id        : (int) ID of Lesson
 * $user_id          : (int) ID of User
 * $quizzes			 : (array) Quizzes
 * $context		     : (string) Context of the usage. Either 'lesson' or 'topic'.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Extra sanity check that this lesson has quizzes.
if ( ! empty( $quizzes ) ) :

	/**
	 * Fires before the quiz list.
	 *
	 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
	 * such as `course`, `lesson`, `topic`, `quiz`, etc.
	 *
	 * @since 3.0.0
	 *
	 * @param int|false $post_id   Post ID.
	 * @param int       $course_id Course ID.
	 * @param int       $user_id   User ID.
	 */
	do_action( 'learndash-' . $context . '-quiz-list-before', get_the_ID(), $course_id, $user_id );
	$is_sample = false;
	if ( ( isset( $lesson_id ) ) && ( ! empty( $lesson_id ) ) ) {
		$is_sample = learndash_get_setting( $lesson_id, 'sample_lesson' );
	}
	$table_class = 'ld-table-list ld-topic-list ld-quiz-list'
					. ( isset( $is_sample ) && 'on' === $is_sample ? ' is_sample' : '' )
	?>
	<div class="<?php echo esc_attr( $table_class ); ?>">

		<div class="ld-table-list-header ld-primary-background">
			<?php
			/**
			 * Fires before the quiz listing header.
			 *
			 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
			 * such as `course`, `lesson`, `topic`, `quiz`, etc.
			 *
			 * @since 3.0.0
			 *
			 * @param int|false $post_id   Post ID.
			 * @param int       $course_id Course ID.
			 * @param int       $user_id   User ID.
			 */
			do_action( 'learndash-' . $context . '-quiz-list-heading-before', get_the_ID(), $course_id, $user_id );
			?>
			<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output?>
			<div class="ld-table-list-title"><?php echo LearnDash_Custom_Label::get_label( 'quizzes' ); ?></div>

			<?php
			/**
			 * Fires before the lesson progress stats.
			 *
			 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
			 * such as `course`, `lesson`, `topic`, `quiz`, etc.
			 *
			 * @since 3.0.0
			 *
			 * @param int|false $post_id   Post ID.
			 * @param int       $course_id Course ID.
			 * @param int       $user_id   User ID.
			 */
			do_action( 'learndash-' . $context . '-quiz-list-progress-before', get_the_ID(), $course_id, $user_id );
			?>

			<?php
			/**
			 * TODO @37designs - need to create a function to count quizes complete / incomplete
			 *
			 <span><?php sprintf( '%s% Complete', $lesson_progress['percentage'] ); ?></span>
			 <span><?php sprintf( '%s/%s Steps', $lesson_progress['complete'], $lesson_progress['total'] ); ?></span>
			 */
			?>

			<div class="ld-table-list-lesson-details"></div>

				<?php
				/**
				 * Fires after the lesson progress stats.
				 *
				 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
				 * such as `course`, `lesson`, `topic`, `quiz`, etc.
				 *
				 * @param int|false $post_id Post ID.
				 * @param int       $course_id Course ID.
				 * @param int       $user_id   User ID.
				 *
				 * @since 3.0.0
				 */
				do_action( 'learndash-' . $context . '-quiz-list-progress-after', get_the_ID(), $course_id, $user_id );
				?>

				<?php
				/**
				 * Fires after the topic listing header.
				 *
				 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
				 * such as `course`, `lesson`, `topic`, `quiz`, etc.
				 *
				 * @param int|false $post_id Post ID.
				 * @param int       $course_id Course ID.
				 * @param int       $user_id   User ID.
				 *
				 * @since 3.0.0
				 */
				do_action( 'learndash-' . $context . '-quiz-list-heading-after', get_the_ID(), $course_id, $user_id );
				?>

			</div> <!--/.ld-table-list-header-->

			<div class="ld-table-list-items">

				<?php
				// TODO @37designs Need to check pagination to see if we should show these - think there is a setting here too to disable quizzes in listing?

				foreach ( $quizzes as $quiz ) :
					learndash_get_template_part(
						'quiz/partials/row.php',
						array(
							'quiz'      => $quiz,
							'course_id' => $course_id,
							'user_id'   => $user_id,
							'context'   => $context,
						),
						true
					);
				endforeach;
				?>

			</div> <!--/.ld-table-list-items-->

			<div class="ld-table-list-footer"></div>

	</div>

	<?php
	/**
	 * Fires after the quiz list.
	 *
	 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
	 * such as `course`, `lesson`, `topic`, `quiz`, etc.
	 *
	 * @param int|false $post_id   Post ID.
	 * @param int       $course_id Course ID.
	 * @param int       $user_id   User ID.
	 *
	 * @since 3.0.0
	 */
	do_action( 'learndash-' . $context . '-quiz-list-after', get_the_ID(), $course_id, $user_id );
	?>

	<?php
endif;
