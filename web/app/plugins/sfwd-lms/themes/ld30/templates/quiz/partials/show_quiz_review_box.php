<?php
/**
 * Displays Quiz Review Box
 *
 * @since 4.22.1
 * @version 4.25.4
 *
 * Available Variables:
 *
 * @var object $quiz_view WpProQuiz_View_FrontQuiz instance.
 * @var object $quiz      WpProQuiz_Model_Quiz instance.
 * @var array  $shortcode_atts Array of shortcode attributes to create the Quiz.
 * @var int    $question_count Number of Question to display.
 *
 * @package LearnDash\Templates\LD30\Quiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpProQuiz_reviewDiv" style="display: none;">
	<?php
		$quiz_view->showReviewQuestions( $question_count );
		$quiz_view->showReviewLegend();
		$quiz_view->showReviewButtons();
	?>
</div>
