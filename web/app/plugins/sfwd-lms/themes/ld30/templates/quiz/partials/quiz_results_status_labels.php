<?php
/**
 * Displays Quiz Results Status Labels
 *
 * @since 4.21.4
 * @version 4.21.4
 *
 * @package LearnDash\Templates\LD30\Quiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ld-quiz-question-item__status">
	<span class="ld-quiz-question-item__status--correct">
		<?php echo esc_html__( 'Correct', 'learndash' ); ?>
	</span>
	<span class="ld-quiz-question-item__status--incorrect">
		<?php echo esc_html__( 'Incorrect', 'learndash' ); ?>
	</span>
	<span class="ld-quiz-question-item__status--missed">
		<?php echo esc_html__( 'Correct answer', 'learndash' ); ?>
	</span>
</div>
