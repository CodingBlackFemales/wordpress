<?php
/**
 * LearnDash LD30 Displays a user's profile course progress.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ld-progress">
	<div class="ld-progress-heading">
		<div class="ld-progress-label"><?php printf( //phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterOpen,Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
			// translators: Course Progress Overview Label.
			esc_html_x( '%s Progress', 'Course Progress Overview Label', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'course' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
		); ?> <?php //phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeEnd ?>
		</div>
		<div class="ld-progress-stats">
			<div class="ld-progress-percentage ld-secondary-color"><?php printf( //phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterOpen,Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
				// translators: Percentage of course completion.
				esc_html_x( '%s%% Complete', 'Percentage of course completion', 'learndash' ),
				esc_html( $progress['percentage'] )
			); ?> <?php //phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeEnd ?>
			</div> <!--/.ld-course-progress-percentage-->
			<div class="ld-progress-steps"> <?php printf( //phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterOpen,Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
				// translators: placeholders: completed steps, total steps.
				esc_html_x( '%1$d/%2$d Steps', 'placeholders: completed steps, total steps', 'learndash' ),
				esc_html( $progress['completed'] ),
				esc_html( $progress['total'] )
			); ?> <?php //phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeEnd ?>
			</div>
		</div> <!--/.ld-course-progress-stats-->
	</div> <!--/.ld-course-progress-heading-->

	<div class="ld-progress-bar">
		<div class="ld-progress-bar-percentage ld-secondary-background" style="width: <?php echo esc_attr( $progress['percentage'] ); ?>%;"></div>
	</div> <!--/.ld-course-progress-bar-->
</div> <!--/.ld-course-progress-->
