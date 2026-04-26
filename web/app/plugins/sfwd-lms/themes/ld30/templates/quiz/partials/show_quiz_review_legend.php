<?php
/**
 * Displays Quiz Review Box
 *
 * @since 4.25.4
 * @version 4.25.4
 *
 * @var WpProQuiz_View_FrontQuiz $quiz_view      WpProQuiz_View_FrontQuiz instance.
 * @var WpProQuiz_Model_Quiz     $quiz           WpProQuiz_Model_Quiz instance.
 * @var array<string,mixed>      $shortcode_atts Array of shortcode attributes to create the Quiz.
 * @var int                      $question_count Number of Question to display.
 *
 * @package LearnDash\Templates\LD30\Quiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Common.

if ( ( 2 === (int) $quiz->getQuizModus() ) && ( ! $quiz->isSkipQuestionDisabled() ) ) {
	$learndash_quiz_legend_review = esc_html__( 'Review / Skip', 'learndash' );
} else {
	$learndash_quiz_legend_review = esc_html__( 'Review', 'learndash' );
}
$learndash_quiz_legend_review_label = SFWD_LMS::get_template(
	'learndash_quiz_messages',
	array(
		'quiz_post_id' => $quiz->getID(),
		'context'      => 'quiz_quiz_review_message',
		'message'      => $learndash_quiz_legend_review,
	)
);

/** This filter is documented in themes/legacy/templates/quiz/partials/show_quiz_review_legend.php */
$learndash_quiz_legend_review_label = apply_filters( 'learndash_quiz_legend_review_label', $learndash_quiz_legend_review_label );

// Single Grading.
$learndash_quiz_legend_answered_label = SFWD_LMS::get_template(
	'learndash_quiz_messages',
	array(
		'quiz_post_id' => $quiz->getID(),
		'context'      => 'quiz_quiz_answered_message',
		'message'      => esc_html__( 'Answered', 'learndash' ),
	)
);

/** This filter is documented in themes/legacy/templates/quiz/partials/show_quiz_review_legend.php */
$learndash_quiz_legend_answered_label = apply_filters( 'learndash_quiz_legend_answered_label', $learndash_quiz_legend_answered_label );

$learndash_quiz_legend_correct_label = SFWD_LMS::get_template(
	'learndash_quiz_messages',
	array(
		'quiz_post_id' => $quiz->getID(),
		'context'      => 'quiz_quiz_answered_correct_message',
		'message'      => esc_html__( 'Correct', 'learndash' ),
	)
);

/** This filter is documented in themes/legacy/templates/quiz/partials/show_quiz_review_legend.php */
$learndash_quiz_legend_correct_label = apply_filters( 'learndash_quiz_legend_correct_label', $learndash_quiz_legend_correct_label );

$learndash_quiz_legend_incorrect_label = SFWD_LMS::get_template(
	'learndash_quiz_messages',
	array(
		'quiz_post_id' => $quiz->getID(),
		'context'      => 'quiz_quiz_answered_incorrect_message',
		'message'      => esc_html__( 'Incorrect', 'learndash' ),
	)
);

/** This filter is documented in themes/legacy/templates/quiz/partials/show_quiz_review_legend.php */
$learndash_quiz_legend_incorrect_label = apply_filters( 'learndash_quiz_legend_incorrect_label', $learndash_quiz_legend_incorrect_label );

?>
<div class="wpProQuiz_reviewLegend">
	<ol>
		<li class="learndash-quiz-review-legend-item-review">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewColor_Review"></span>
			<span class="wpProQuiz_reviewText"><?php echo wp_kses_post( $learndash_quiz_legend_review_label ); ?></span>
		</li>
		<li class="learndash-quiz-review-legend-item-answered">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewColor_Answer"></span>
			<span class="wpProQuiz_reviewText"><?php echo wp_kses_post( $learndash_quiz_legend_answered_label ); ?></span>
		</li>
		<li class="learndash-quiz-review-legend-item-correct">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewColor_AnswerCorrect"></span>
			<span class="wpProQuiz_reviewText"><?php echo wp_kses_post( $learndash_quiz_legend_correct_label ); ?></span>
		</li>
		<li class="learndash-quiz-review-legend-item-incorrect">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewColor_AnswerIncorrect"></span>
			<span class="wpProQuiz_reviewText"><?php echo wp_kses_post( $learndash_quiz_legend_incorrect_label ); ?></span>
		</li>
	</ol>
	<div style="clear: both;"></div>
</div>
