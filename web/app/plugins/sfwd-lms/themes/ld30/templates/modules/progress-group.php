<?php
/**
 * LearnDash LD30 Displays group progress
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action to add custom content before the progress bar
 *
 * @since 3.2.0
 */

$context = ( isset( $context ) ? $context : 'learndash' );

/**
 * Fires before the progress bar.
 *
 * @since 3.2.0
 *
 * @param int $group_id Group ID.
 * @param int $user_id  User ID.
 */
do_action( 'learndash-progress-bar-before', $group_id, $user_id );

/**
 * Fires before the progress bar for any context.
 *
 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
 * such as `course`, `lesson`, `topic`, `quiz`, etc.
 *
 * @since 3.2.0
 *
 * @param int $group_id Group ID.
 * @param int $user_id  User ID.
 */
do_action( 'learndash-' . $context . '-progress-bar-before', $group_id, $user_id );

/**
 * In the topic context we're measuring progress through a lesson, not the course itself
 */
if ( 'group' === $context ) {
	$progress = apply_filters( 'learndash-' . $context . '-progress-stats', learndash_get_user_group_progress( $group_id, $user_id ) );
	if ( $progress ) {
		/**
		 * This is just here for reference
		 */ ?>
		<div class="ld-progress ld-progress-inline">
			<div class="ld-progress-heading">
				<?php if ( 'topic' === $context ) : ?>
					<div class="ld-progress-label">
					<?php
					echo sprintf(
						// translators: placeholder: Group.
						esc_html_x( '%s Progress', 'Placeholder: Group', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'group' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					);
					?>
					</div>
				<?php endif; ?>
				<div class="ld-progress-stats">
					<div class="ld-progress-percentage ld-secondary-color">
					<?php
					echo sprintf(
						// translators: placeholder: Progress percentage.
						esc_html_x( '%s%% Complete', 'placeholder: Progress percentage', 'learndash' ),
						esc_html( $progress['percentage'] )
					);
					?>
					</div>
					<div class="ld-progress-steps">
						<?php
						echo sprintf(
							// translators: placeholder: completed steps, total steps, Courses.
							esc_html_x( '%1$d/%2$d %3$s', 'placeholder: completed steps, total steps, Courses', 'learndash' ),
							esc_html( $progress['completed'] ),
							esc_html( $progress['total'] ),
							LearnDash_Custom_Label::get_label( 'courses' )
						);
						?>
					</div>
				</div> <!--/.ld-progress-stats-->
			</div>

			<div class="ld-progress-bar">
				<div class="ld-progress-bar-percentage ld-secondary-background" style="<?php echo esc_attr( 'width:' . $progress['percentage'] . '%' ); ?>"></div>
			</div>
		</div> <!--/.ld-progress-->
		<?php
	}
}

/**
 * Action to add custom content before the course content progress bar
 *
 * @since 3.0.0
 */
do_action( 'learndash-' . $context . '-progress-bar-after', $group_id, $user_id );

