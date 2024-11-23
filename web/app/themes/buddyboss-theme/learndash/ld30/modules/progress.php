<?php
/**
 * LearnDash LD30 Displays course progress
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fires before the progress bar
 *
 * @since 3.0.0
 */

$context = ( isset( $context ) ? $context : 'learndash' );

/**
 * Fires before the progress bar.
 *
 * @since 3.0.0
 *
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-progress-bar-before', $course_id, $user_id );

/**
 * Fires before the progress bar for any context.
 *
 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
 * such as `course`, `lesson`, `topic`, `quiz`, etc.
 *
 * @since 3.0.0
 *
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-' . $context . '-progress-bar-before', $course_id, $user_id );

/**
 * In the topic context we're measuring progress through a lesson, not the course itself
 *
 * @var [type]
 */
if ( 'topic' !== $context ) {

	/**
	 * Filters LearnDash progress arguments.
	 * This filter will not be called if the context is `topic`.
	 *
	 * @since 3.0.0
	 *
	 * @param array $progress_args An array of progress arguments.
	 * @param int   $course_id     Course ID.
	 * @param int   $user_id       User ID.
	 */
	$progress_args = apply_filters(
		'learndash_progress_args',
		array(
			'array'     => true,
			'course_id' => $course_id,
			'user_id'   => $user_id,
		),
		$course_id,
		$user_id,
		$context
	);

	/**
	 * Filters the progress statistics.
	 *
	 * The dynamic portion of the hook name, `$context`, refers to the context of progress,
	 * such as `course`, `lesson`, `topic`, `quiz`, etc.
	 *
	 * @since 3.0.0
	 *
	 * @param string $progress_markup The HTML template of users course/lesson progress
	 */
	$progress = apply_filters( 'learndash-' . $context . '-progress-stats', learndash_course_progress( $progress_args ) );

	if ( empty( $progress ) ) {
		$progress = array(
			'percentage' => 0,
			'completed'  => 0,
			'total'      => 0,
		);
	}
} else {
	if ( ! isset( $post ) ) {
		global $post;
	}
	/** This filter is documented in themes/ld30/templates/modules/progress.php */
	$progress = apply_filters( 'learndash-' . $context . '-progress-stats', learndash_lesson_progress( $post, $course_id ) );
}

if ( $progress ) :
	/**
	 * This is just here for reference
	 */ ?>
	<div class="ld-progress <?php echo ( 'course' === $context ) ? esc_attr( 'ld-progress-inline' ) : ''; ?>">
		<?php if ( 'focus' === $context ) : ?>
			<div class="ld-progress-wrap">
		<?php endif; ?>
			<div class="ld-progress-heading">
				<?php if ( 'topic' === $context ) : ?>
					<div class="ld-progress-label">
						<?php
						echo sprintf(
							/* translators: placeholder: Lesson Progress. */
							esc_html_x( '%s Progress', 'Placeholder: Lesson Progress', 'buddyboss-theme' ),
							LearnDash_Custom_Label::get_label( 'lesson' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
						);
						?>
					</div>
				<?php endif; ?>
			</div>

			<div class="ld-progress-bar">
				<div class="ld-progress-bar-percentage ld-secondary-background" style="<?php echo esc_attr( 'width:' . $progress['percentage'] . '%' ); ?>"></div>
			</div>
			<div class="ld-progress-stats">
				<div class="ld-progress-percentage ld-secondary-color course-completion-rate">
					<?php
					echo sprintf(
						/* translators: placeholder: Progress percentage. */
						esc_html_x( '%s%% Complete', 'placeholder: Progress percentage', 'buddyboss-theme' ),
						esc_html( $progress['percentage'] )
					);

					?>
				</div>
				<div class="ld-progress-steps">
					<?php
					if ( 'course' === $context || 'focus' === $context ) :
						$course_args     = array(
							'course_id'     => $course_id,
							'user_id'       => $user_id,
							'post_id'       => $course_id,
							'activity_type' => 'course',
						);
						$course_activity = learndash_get_user_activity( $course_args );

						if ( ! empty( $course_activity->activity_updated ) && 'course' === $context ) :
							echo sprintf(
							    /* translators: Last activity date in infobar. */
								esc_html_x( 'Last activity on %s', 'Last activity date in infobar', 'buddyboss-theme' ),
								esc_html( learndash_adjust_date_time_display( $course_activity->activity_updated ) )
							);
						else :
							echo sprintf(
								/* translators: placeholders: completed steps, total steps. */
								esc_html_x( '%1$d/%2$d Steps', 'placeholders: completed steps, total steps', 'buddyboss-theme' ),
								esc_html( $progress['completed'] ),
								esc_html( $progress['total'] )
							);
						endif;
					endif;
					?>
				</div>
			</div> <!--/.ld-progress-stats-->
			<?php if ( 'focus' === $context ) : ?>
				</div> <!--/.ld-progress-wrap-->
			<?php endif; ?>
	</div> <!--/.ld-progress-->
	<?php
endif;

/**
 * Fires before the course content progress bar.
 *
 * @since 3.0.0
 *
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-progress-bar-after', $course_id, $user_id );

/**
 * Fires before the course steps for any context.
 *
 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
 * such as `course`, `lesson`, `topic`, `quiz`, etc.
 *
 * @since 3.0.0
 *
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-' . $context . '-progress-bar-after', $course_id, $user_id );
