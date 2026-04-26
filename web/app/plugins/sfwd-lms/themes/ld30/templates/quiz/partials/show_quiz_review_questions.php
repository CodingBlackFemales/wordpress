<?php
/**
 * Displays Quiz Review Box
 *
 * @since 4.25.4
 * @version 4.25.4
 *
 * @var WpProQuiz_View_FrontQuiz $quiz_view WpProQuiz_View_FrontQuiz instance.
 * @var WpProQuiz_Model_Quiz     $quiz      WpProQuiz_Model_Quiz instance.
 * @var array<string, mixed>     $shortcode_atts Array of shortcode attributes to create the Quiz.
 * @var int                      $question_count Number of Question to display.
 *
 * @package LearnDash\Templates\LD30\Quiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpProQuiz_reviewQuestion learndash-quiz-review">
	<ol class="learndash-quiz-review__list">
		<?php for ( $question_number = 1; $question_number <= $question_count; $question_number ++ ) : ?>
			<li class="learndash-quiz-review__item">
				<button
					type="button"
					class="learndash-quiz-review__button"
				>
					<span class="screen-reader-text">
						<?php echo esc_html(
							sprintf(
								/* translators: placeholder: Question label. */
								__( 'Show %s ', 'learndash' ),
								learndash_get_custom_label( 'question' )
							)
						); ?>
					</span>
					<?php echo esc_html( $question_number ); ?>

					<span class="learndash-quiz-review__item-status screen-reader-text"></span>
				</button>
			</li>
		<?php endfor; ?>
	</ol>
	<div style="display: none;"></div>
</div>
