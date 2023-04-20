<?php
/**
 * LearnDash LD30 Displays the course navigation widget topic row.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filters Navigation widget topic row wrapper CSS class.
 *
 * @since 3.0.0
 *
 * @param string $topic_wrapper_class List of row wrapper CSS classes.
 * @param object $topic               The Topic object
 */
$wrapper_class = apply_filters( 'learndash-topic-row-wrapper-class', 'ld-table-list-item' . ( $topic->completed ? ' learndash-complete' : ' learndash-incomplete' ), $topic );
$topic_class   = 'ld-table-list-item-preview ld-primary-color-hover ld-topic-row ' . ( get_the_ID() === absint( $topic->ID ) ? 'ld-is-current-item ' : '' );

/** This filter is documented in themes/ld30/templates/topic/partials/row.php */
$topic_class = apply_filters( 'learndash-topic-row-class', $topic_class, $topic );

/** This filter is documented in themes/ld30/templates/topic/partials/row.php */
$topic_status = apply_filters( 'learndash-topic-status', ( $topic->completed ? 'completed' : 'not-completed' ) );

$attributes = learndash_get_course_step_attributes( $topic->ID, $course_id, $user_id );

$learndash_available_date = learndash_course_step_available_date( $topic->ID, $course_id, $user_id, true );
if ( ! empty( $learndash_available_date ) ) {
	$wrapper_class .= ' learndash-not-available';
}
?>
<div class="<?php echo esc_attr( $wrapper_class ); ?>">
	<a class="<?php echo esc_attr( $topic_class ); ?>" href="<?php echo esc_url( learndash_get_step_permalink( $topic->ID, $course_id ) ); ?>">

		<?php learndash_status_icon( $topic_status, 'sfwd-topic', null, true ); ?>

		<div class="ld-topic-title">
		<?php
		echo wp_kses_post( apply_filters( 'the_title', $topic->post_title, $topic->ID ) );
		if ( ! empty( $attributes ) ) :
			foreach ( $attributes as $attribute ) :
				?>
			<span class="ld-status-icon <?php echo esc_attr( $attribute['class'] ); ?>" data-ld-tooltip="<?php echo esc_attr( $attribute['label'] ); ?>"><span class="ld-icon <?php echo esc_attr( $attribute['icon'] ); ?>"></span></span>
				<?php
			endforeach;
		endif;
		?></div>

	</a>
</div>

<?php
if ( isset( $widget_instance['show_topic_quizzes'] ) && true === (bool) $widget_instance['show_topic_quizzes'] ) :

	$quizzes = learndash_get_lesson_quiz_list( $topic, null, $course_id );

	if ( $quizzes && ! empty( $quizzes ) ) :
		echo '<div id="ld-nav-content-list-' . $topic->ID . '">';
		foreach ( $quizzes as $quiz ) :
			learndash_get_template_part(
				'widgets/navigation/quiz-row.php',
				array(
					'course_id' => $course_id,
					'user_id'   => $user_id,
					'context'   => 'lesson',
					'quiz'      => $quiz,
				),
				true
			);
		endforeach;
		echo '</div>';
	endif;

endif; ?>
