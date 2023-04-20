<?php
/**
 * LearnDash LD30 Displays the course navigation widget.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter to allow override of widget instance arguments.
 */
if ( ! isset( $widget_instance ) ) {
	$widget_instance = array();
}

global $course_pager_results;

$has_access         = sfwd_lms_has_access( $course_id );
$has_lesson_quizzes = learndash_30_has_lesson_quizzes( $course_id, $lessons );
$has_topics         = learndash_30_has_topics( $course_id, $lessons );

if ( isset( $widget_instance['show_lesson_quizzes'] ) && true !== (bool) $widget_instance['show_lesson_quizzes'] ) {
	$has_lesson_quizzes = false;
}

/**
 * Filters Course navigation widget arguments
 *
 * @since 2.3.3
 *
 * @param array $widget_instance The widget instance.
 * @param int   $course_id       Course ID.
 */
$widget_instance = apply_filters( 'learndash_course_navigation_widget_args', $widget_instance, $course_id );
$widget_data     = array(
	'course_id'       => $course_id,
	'widget_instance' => $widget_instance,
);

if ( ! isset( $user_id ) ) {
	$cuser   = wp_get_current_user();
	$user_id = $cuser->ID;
}

$widget_data_json = htmlspecialchars( wp_json_encode( $widget_data ) ); ?>

<div class="<?php echo esc_attr( learndash_the_wrapper_class() ); ?>">

	<?php if ( false !== (bool) $widget_instance['show_widget_wrapper'] ) : ?>
		<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above ?>
		<div class="ld-course-navigation <?php echo esc_attr( 'ld-course-nav-' . $course_id ); ?>" data-widget_instance="<?php echo $widget_data_json; ?>">
	<?php endif; ?>

		<div class="ld-course-navigation-heading">
			<div class="ld-course-navigation-actions">
				<a class="ld-home-link" href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>"><?php printf( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
					// translators: placeholder: Course Navigation Home Label.
					esc_html_x( '%s Home', 'Course Navigation Home Label', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'course' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
				); ?></a> <?php // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,Squiz.PHP.EmbeddedPhp.ContentAfterEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>
				<?php if ( $has_lesson_quizzes || $has_topics ) : ?>
					<span class="ld-expand-button ld-button-alternate" data-ld-expands="<?php echo esc_attr( 'ld-lesson-list-' . $course->ID ); ?>"  data-ld-expand-text="<?php esc_html_e( 'Expand All', 'learndash' ); ?>" data-ld-collapse-text="<?php esc_html_e( 'Collapse All', 'learndash' ); ?>">
						<span class="ld-icon-arrow-down ld-icon ld-primary-background"></span>
						<span class="ld-text ld-primary-color"><?php esc_html_e( 'Expand All', 'learndash' ); ?></span>
					</span>
				<?php endif; ?>
			</div> <!--/.ld-course-navigation-actions-->
		</div> <!--/.ld-course-navigation-heading-->

		<div class="ld-lesson-navigation">
			<div class="ld-lesson-items" data-ld-expand-list="true" id="<?php echo esc_attr( 'ld-lesson-list-' . $course->ID ); ?>">
				<?php
				learndash_get_template_part(
					'widgets/navigation/rows.php',
					array(
						'course_id'            => $course_id,
						'widget_instance'      => $widget_instance,
						'lessons'              => $lessons,
						'course_pager_results' => $course_pager_results,
						'has_access'           => $has_access,
						'user_id'              => $user_id,
					),
					true
				);

				if ( ! empty( $widget_instance['current_step_id'] ) && $widget_instance['current_step_id'] != $course->ID ) :
					?>
					<div class="widget_course_return">
						<?php esc_html_e( 'Return to', 'learndash' ); ?>
						<a href='<?php echo esc_url( get_permalink( $course_id ) ); ?>'><?php echo wp_kses_post( apply_filters( 'the_title', $course->post_title, $course->ID ) ); ?> </a> <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>
					</div>
				<?php endif; ?>

			</div> <!--/.ld-lesson-items-->
		</div> <!--/.ld-lesson-navigation-->

	<?php if ( false !== (bool) $widget_instance['show_widget_wrapper'] ) : ?>

		</div> <!-- Closing <div id='course_navigation'> -->
		<?php
		/** This filter is documented in themes/ld30/templates/course.php */
		if ( apply_filters( 'learndash_course_steps_expand_all', false, $course_id, 'course_navigation_widget' ) ) :
			?>
			<script>
				jQuery( function() {
					setTimeout(function(){
						jQuery(".course_navigation .list_arrow").trigger('click');
					}, 1000);
				});
			</script>
			<?php
			endif;
		endif;
	?>
</div>
